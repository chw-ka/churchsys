<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 新增會友的編號規則（2026-11-07 生產事故後定案）。
 *
 * tbl_member.code / new_card / arrived_date / creator_id / modifier_id 在生產是
 * NOT NULL 無預設值，MySQL 亦為 STRICT_TRANS_TABLES，空編號若寫成 NULL 會
 * SQLSTATE 23000。現行規則：編號留空 = 由系統自動分配
 * （新朋友 9000 起、會友／同工 1001 起），手動輸入則以手動為準。
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

    public function test_new_friend_gets_an_automatic_9xxx_code(): void
    {
        $this->actingAs($this->user)
            ->post(route('members.store'), [
                'name'         => 'Winnie',
                'code'         => '',
                'account_type' => Member::ACCOUNT_TYPE_NEW_MEMBER,
            ])
            ->assertRedirect();

        $member = Member::where('name', 'Winnie')->firstOrFail();

        // 新朋友（臨時會友）由 9000 起順序編號，永不為 NULL
        $this->assertSame('9000', $member->code);
        $this->assertSame(Member::ACCOUNT_TYPE_NEW_MEMBER, (int) $member->account_type);
        $this->assertSame(Member::STATE_ACTIVE, (int) $member->state);
        $this->assertSame(0, (int) $member->new_card);
        $this->assertNotNull($member->arrived_date);
        $this->assertSame($this->user->id, (int) $member->creator_id);
        $this->assertSame($this->user->id, (int) $member->modifier_id);
    }

    public function test_consecutive_new_friends_get_consecutive_codes(): void
    {
        foreach (['甲', '乙'] as $name) {
            $this->actingAs($this->user)->post(route('members.store'), [
                'name'         => $name,
                'account_type' => Member::ACCOUNT_TYPE_NEW_MEMBER,
            ]);
        }

        $this->assertSame(['9000', '9001'], Member::orderBy('id')->pluck('code')->all());
    }

    public function test_new_member_gets_the_next_normal_code(): void
    {
        Member::create([
            'state'        => 1,
            'code'         => '3430',
            'name'         => '現有會友',
            'account_type' => Member::ACCOUNT_TYPE_MEMBER,
        ]);

        $this->actingAs($this->user)
            ->post(route('members.store'), [
                'name'         => '新會友',
                'account_type' => Member::ACCOUNT_TYPE_MEMBER,
            ])
            ->assertRedirect();

        $this->assertSame('3431', Member::where('name', '新會友')->value('code'));
    }

    public function test_manual_code_is_kept(): void
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

    public function test_duplicate_manual_code_is_rejected(): void
    {
        Member::create([
            'state'        => 1,
            'code'         => '2345',
            'name'         => '原有會友',
            'account_type' => Member::ACCOUNT_TYPE_MEMBER,
        ]);

        $this->actingAs($this->user)
            ->from(route('members.create'))
            ->post(route('members.store'), [
                'name'         => '重複編號',
                'code'         => '2345',
                'account_type' => Member::ACCOUNT_TYPE_MEMBER,
            ])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Member::count());
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
