<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Models\Worship;
use App\Models\WorshipAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WorshipAdminTakeTest extends TestCase
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
        // create_time/update_time are not mass-assignable on this model
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

        Worship::create([
            'state'      => 1,
            'name'       => '主日崇拜（午堂）',
            'start_time' => '11:30',
            'end_time'   => '13:00',
            'weekly'     => 0,
            'remarks'    => '',
        ]);

        // Active members
        Member::create(['state' => 1, 'code' => '1001', 'name' => '陳大文', 'account_type' => 1]);
        Member::create(['state' => 1, 'code' => '1002', 'name' => '李四',   'account_type' => 1]);
        Member::create(['state' => 1, 'code' => '1003', 'name' => '張三',   'account_type' => 1]);
        // Duplicate name (for ambiguous test)
        Member::create(['state' => 1, 'code' => '1004', 'name' => '張三',   'account_type' => 1]);
        // Inactive member (should not be matched)
        Member::create(['state' => 0, 'code' => '1005', 'name' => '已刪除', 'account_type' => 1]);
    }

    public function test_admin_take_page_loads(): void
    {
        $this->actingAs($this->user)
            ->get(route('worship.attendance.admin-take'))
            ->assertOk()
            ->assertSee('補加出席')
            ->assertSee('主日崇拜（早堂）');
    }

    public function test_requires_login(): void
    {
        $this->get(route('worship.attendance.admin-take'))
            ->assertRedirect(route('login'));
    }

    public function test_bulk_add_by_code_with_spreadsheet_tab_paste(): void
    {
        $backdate = Carbon::yesterday()->toDateString();

        // Simulate pasting from a spreadsheet: "code\tname" rows, blank lines
        $entries = "1001\t陳大文\n\n1002\t李四\n   \n";

        $response = $this->actingAs($this->user)
            ->post(route('worship.attendance.admin-store'), [
                'worship_id'      => $this->worship->id,
                'attendance_date' => $backdate,
                'entries'         => $entries,
            ]);

        $response->assertRedirect(route('worship.attendance.admin-take'));

        $records = WorshipAttendance::where('worship_id', $this->worship->id)->get();
        $this->assertCount(2, $records);

        // Backdated: correct date, time = now
        foreach ($records as $record) {
            $this->assertTrue(Carbon::parse($record->attendance_date)->isSameDay($backdate));
        }

        // Follow-up redirect shows result summary
        $this->actingAs($this->user)
            ->get(route('worship.attendance.admin-take'))
            ->assertSee('成功加入')
            ->assertSee('陳大文 (1001)')
            ->assertSee('李四 (1002)');
    }

    public function test_add_by_name_and_skip_duplicates(): void
    {
        $today = Carbon::today()->toDateString();

        // Pre-existing attendance for 1001
        WorshipAttendance::create([
            'worship_id'      => $this->worship->id,
            'member_id'       => Member::where('code', '1001')->first()->id,
            'attendance_date' => $today . ' 09:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('worship.attendance.admin-store'), [
                'worship_id'      => $this->worship->id,
                'attendance_date' => $today,
                'entries'         => "1001\n李四",
            ]);

        $response->assertRedirect(route('worship.attendance.admin-take'));

        // 1001 duplicate skipped, 李四 added — total still 2 records for 1001+1002 = 2
        $this->assertSame(2, WorshipAttendance::whereDate('attendance_date', $today)->count());

        // Flash summary is shown on the page right after the redirect (PRG)
        $this->followingRedirects()->actingAs($this->user)
            ->post(route('worship.attendance.admin-store'), [
                'worship_id'      => $this->worship->id,
                'attendance_date' => $today,
                'entries'         => "1001",
            ])
            ->assertSee('已有記錄')
            ->assertSee('陳大文 (1001)');
    }

    public function test_duplicate_only_within_same_worship(): void
    {
        $today = Carbon::today()->toDateString();
        $afternoonWorship = Worship::where('name', '主日崇拜（午堂）')->first();

        WorshipAttendance::create([
            'worship_id'      => $this->worship->id,
            'member_id'       => Member::where('code', '1001')->first()->id,
            'attendance_date' => $today . ' 09:00:00',
        ]);

        $this->actingAs($this->user)
            ->post(route('worship.attendance.admin-store'), [
                'worship_id'      => $afternoonWorship->id,
                'attendance_date' => $today,
                'entries'         => "1001",
            ]);

        // Same member, different worship on same date → allowed
        $this->assertTrue(
            WorshipAttendance::where('member_id', Member::where('code', '1001')->first()->id)
                ->where('worship_id', $afternoonWorship->id)
                ->whereDate('attendance_date', $today)
                ->exists()
        );
    }

    public function test_not_found_and_ambiguous_names_reported(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('worship.attendance.admin-store'), [
                'worship_id'      => $this->worship->id,
                'attendance_date' => Carbon::today()->toDateString(),
                'entries'         => "9999\n張三\n已刪除",
            ]);

        $response->assertRedirect(route('worship.attendance.admin-take'));

        // Nothing inserted
        $this->assertSame(0, WorshipAttendance::count());

        $this->followingRedirects()->actingAs($this->user)
            ->post(route('worship.attendance.admin-store'), [
                'worship_id'      => $this->worship->id,
                'attendance_date' => Carbon::today()->toDateString(),
                'entries'         => "9999\n張三\n已刪除",
            ])
            ->assertSee('9999')      // not found
            ->assertSee('多個符合')   // 張三 ambiguous (1003 + 1004)
            ->assertSee('張三 (1003)')
            ->assertSee('張三 (1004)')
            // textarea repopulated for fixing
            ->assertSee('張三');
    }

    public function test_future_date_rejected()
    {
        $this->actingAs($this->user)
            ->from(route('worship.attendance.admin-take'))
            ->post(route('worship.attendance.admin-store'), [
                'worship_id'      => $this->worship->id,
                'attendance_date' => Carbon::tomorrow()->toDateString(),
                'entries'         => "1001",
            ])
            ->assertSessionHasErrors('attendance_date');

        $this->assertSame(0, WorshipAttendance::count());
    }

    public function test_inactive_member_not_matched(): void
    {
        $this->actingAs($this->user)
            ->post(route('worship.attendance.admin-store'), [
                'worship_id'      => $this->worship->id,
                'attendance_date' => Carbon::today()->toDateString(),
                'entries'         => "1005",
            ]);

        $this->assertSame(0, WorshipAttendance::count());
    }

    public function test_past_date_beyond_today_allowed_for_backfill(): void
    {
        $lastMonth = Carbon::today()->subMonth()->toDateString();

        $this->actingAs($this->user)
            ->post(route('worship.attendance.admin-store'), [
                'worship_id'      => $this->worship->id,
                'attendance_date' => $lastMonth,
                'entries'         => "1001\n1002\n1003",
            ]);

        $this->assertSame(3, WorshipAttendance::whereDate('attendance_date', $lastMonth)->count());
    }
}
