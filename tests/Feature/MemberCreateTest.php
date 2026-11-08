<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the 2026-11-07 production incident:
 * adding a 新朋友 (no member code) failed with
 * "SQLSTATE 23000: Column 'code' cannot be null".
 *
 * tbl_member.code / new_card / arrived_date / creator_id / modifier_id are
 * NOT NULL without a default in the production schema, and MySQL runs with
 * STRICT_TRANS_TABLES there.
 */
class MemberCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User([
            'username'    => 'tester',
            'password'    => bcrypt('secret123'),
            'email'       => 'tester@test.local',
            'member_code' => '0001',
        ]);
        $this->user->create_time = now();
        $this->user->update_time = now();
        $this->user->save();
    }

    public function test_new_friend_can_be_created_without_member_code(): void
    {
        $this->actingAs($this->user)
            ->post(route('members.store'), [
                'name'         => 'Winnie',
                'code'         => '',
                'account_type' => Member::ACCOUNT_TYPE_NEW_MEMBER,
            ])
            ->assertRedirect();

        $member = Member::where('name', 'Winnie')->firstOrFail();

        // Never NULL: the column is NOT NULL in production
        $this->assertSame('', $member->code);
        $this->assertSame(Member::ACCOUNT_TYPE_NEW_MEMBER, (int) $member->account_type);
        $this->assertSame(Member::STATE_ACTIVE, (int) $member->state);
        $this->assertSame(0, (int) $member->new_card);
        $this->assertNotNull($member->arrived_date);
        $this->assertSame($this->user->id, (int) $member->creator_id);
        $this->assertSame($this->user->id, (int) $member->modifier_id);
    }

    public function test_new_friend_can_be_created_with_member_code(): void
    {
        $this->actingAs($this->user)
            ->post(route('members.store'), [
                'name'         => '陳大文',
                'code'         => '2345',
                'account_type' => Member::ACCOUNT_TYPE_MEMBER,
                'arrived_date' => '2026-01-15',
            ])
            ->assertRedirect();

        $member = Member::where('name', '陳大文')->firstOrFail();

        $this->assertSame('2345', $member->code);
        $this->assertSame('2026-01-15', $member->arrived_date);
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->user)
            ->from(route('members.create'))
            ->post(route('members.store'), ['name' => '', 'code' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, Member::count());
    }

    public function test_clearing_the_code_on_edit_does_not_null_it(): void
    {
        $member = Member::create([
            'state'        => 1,
            'code'         => '1001',
            'name'         => '李四',
            'account_type' => Member::ACCOUNT_TYPE_MEMBER,
        ]);

        $this->actingAs($this->user)
            ->put(route('members.update', $member->id), [
                'name'         => '李四',
                'code'         => '',
                'account_type' => Member::ACCOUNT_TYPE_MEMBER,
            ])
            ->assertRedirect();

        $this->assertSame('', $member->fresh()->code);
    }
}
