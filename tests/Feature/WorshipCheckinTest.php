<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Models\Worship;
use App\Models\WorshipAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorshipCheckinTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Worship $worship;

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

        $this->worship = Worship::create([
            'state'      => 1,
            'name'       => '主日崇拜（早堂）',
            'start_time' => '09:30',
            'end_time'   => '11:00',
            'weekly'     => 0,
            'remarks'    => '',
        ]);

        Member::create(['state' => 1, 'code' => '1001', 'name' => '陳大文', 'account_type' => 1]);
        Member::create(['state' => 1, 'code' => '1002', 'name' => '李四',   'account_type' => 1]);
        // Duplicate name (for the ambiguous-name flow)
        Member::create(['state' => 1, 'code' => '1003', 'name' => '張三',   'account_type' => 1]);
        Member::create(['state' => 1, 'code' => '1004', 'name' => '張三',   'account_type' => 1]);
        // Inactive member must never match
        Member::create(['state' => 0, 'code' => '1005', 'name' => '已刪除', 'account_type' => 1]);
    }

    public function test_checkin_by_code(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('worship.attendance.checkin'), [
                'member_code' => '1001',
                'worship_id'  => $this->worship->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('member.name', '陳大文')
            ->assertJsonPath('member.code', '1001');

        $this->assertSame(1, WorshipAttendance::whereDate('attendance_date', today())->count());
    }

    public function test_checkin_by_exact_unique_name(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('worship.attendance.checkin'), [
                'member_code' => '李四',
                'worship_id'  => $this->worship->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('member.code', '1002');
    }

    public function test_checkin_ambiguous_name_lists_candidates(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('worship.attendance.checkin'), [
                'member_code' => '張三',
                'worship_id'  => $this->worship->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('ambiguous', true)
            ->assertJsonCount(2, 'candidates');

        $this->assertSame(0, WorshipAttendance::count());
    }

    public function test_checkin_by_member_id_after_candidate_pick(): void
    {
        $member = Member::where('code', '1004')->first();

        $this->actingAs($this->user)
            ->postJson(route('worship.attendance.checkin'), [
                'member_id'   => $member->id,
                'member_code' => '張三',
                'worship_id'  => $this->worship->id,
            ])
            ->assertOk()
            ->assertJsonPath('member.code', '1004');
    }

    public function test_checkin_duplicate_returns_409(): void
    {
        $payload = ['member_code' => '1001', 'worship_id' => $this->worship->id];

        $this->actingAs($this->user)
            ->postJson(route('worship.attendance.checkin'), $payload)
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('worship.attendance.checkin'), $payload)
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertSame(1, WorshipAttendance::count());
    }

    public function test_checkin_unknown_input_returns_404(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('worship.attendance.checkin'), [
                'member_code' => '9999',
                'worship_id'  => $this->worship->id,
            ])
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_checkin_ignores_inactive_member(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('worship.attendance.checkin'), [
                'member_code' => '1005',
                'worship_id'  => $this->worship->id,
            ])
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->postJson(route('worship.attendance.checkin'), [
                'member_code' => '已刪除',
                'worship_id'  => $this->worship->id,
            ])
            ->assertStatus(404);

        $this->assertSame(0, WorshipAttendance::count());
    }

    public function test_take_data_returns_list_and_counts(): void
    {
        WorshipAttendance::create([
            'worship_id'      => $this->worship->id,
            'member_id'       => Member::where('code', '1001')->first()->id,
            'attendance_date' => now()->format('Y-m-d') . ' 09:05:00',
        ]);

        $res = $this->actingAs($this->user)
            ->getJson(route('worship.attendance.take-data', ['worship_id' => $this->worship->id]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.' . $this->worship->id, 1)
            ->json();

        $this->assertStringContainsString('陳大文 (1001)', $res['html']);
        // datetime cast: the time part must survive, not collapse to 00:00
        $this->assertStringContainsString('09:05', $res['html']);
    }

    public function test_take_page_renders_existing_list_on_load(): void
    {
        WorshipAttendance::create([
            'worship_id'      => $this->worship->id,
            'member_id'       => Member::where('code', '1001')->first()->id,
            'attendance_date' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('worship.take'))
            ->assertOk()
            ->assertSee('陳大文 (1001)', false)
            ->assertSee('id="attendance-list"', false);
    }
}
