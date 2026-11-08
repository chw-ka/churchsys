<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Models\Worship;
use App\Models\WorshipAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The check-in screen can remove a wrongly entered record.
 *
 * tbl_worship_attendance has no `id` column — the key is
 * (worship_id, member_id, attendance_date) — so deletion must go through the
 * composite key and must only affect the date given (today by default).
 */
class WorshipAttendanceDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Worship $worship;
    private Member $member;

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
            'name'       => '主日第一堂',
            'start_time' => '08:00',
            'end_time'   => '09:25',
            'weekly'     => 0,
            'remarks'    => '',
        ]);

        $this->member = Member::create([
            'state'        => 1,
            'code'         => '1001',
            'name'         => '陳大文',
            'account_type' => Member::ACCOUNT_TYPE_MEMBER,
        ]);
    }

    public function test_deletes_todays_record_and_returns_refreshed_list(): void
    {
        WorshipAttendance::create([
            'worship_id'      => $this->worship->id,
            'member_id'       => $this->member->id,
            'attendance_date' => now(),
        ]);

        $res = $this->actingAs($this->user)
            ->deleteJson(route('worship.attendance.destroy'), [
                'worship_id' => $this->worship->id,
                'member_id'  => $this->member->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.' . $this->worship->id, 0)
            ->json();

        $this->assertStringContainsString('暫無簽到記錄', $res['html']);
        $this->assertSame(0, WorshipAttendance::count());
    }

    public function test_history_is_never_deleted_by_the_default_call(): void
    {
        $yesterday = today()->subDay();

        WorshipAttendance::create([
            'worship_id'      => $this->worship->id,
            'member_id'       => $this->member->id,
            'attendance_date' => $yesterday->format('Y-m-d') . ' 10:00:00',
        ]);

        $this->actingAs($this->user)
            ->deleteJson(route('worship.attendance.destroy'), [
                'worship_id' => $this->worship->id,
                'member_id'  => $this->member->id,
            ])
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->assertSame(1, WorshipAttendance::count());
    }

    public function test_explicit_date_deletes_that_day_only(): void
    {
        $yesterday = today()->subDay()->toDateString();

        WorshipAttendance::create([
            'worship_id'      => $this->worship->id,
            'member_id'       => $this->member->id,
            'attendance_date' => $yesterday . ' 10:00:00',
        ]);
        WorshipAttendance::create([
            'worship_id'      => $this->worship->id,
            'member_id'       => $this->member->id,
            'attendance_date' => now(),
        ]);

        $this->actingAs($this->user)
            ->deleteJson(route('worship.attendance.destroy'), [
                'worship_id' => $this->worship->id,
                'member_id'  => $this->member->id,
                'date'       => $yesterday,
            ])
            ->assertOk();

        $this->assertSame(1, WorshipAttendance::count());
        $this->assertSame(
            1,
            WorshipAttendance::whereDate('attendance_date', today())->count()
        );
    }

    public function test_requires_authentication(): void
    {
        $this->deleteJson(route('worship.attendance.destroy'), [
            'worship_id' => $this->worship->id,
            'member_id'  => $this->member->id,
        ])->assertUnauthorized();
    }

    public function test_unknown_member_returns_validation_error(): void
    {
        $this->actingAs($this->user)
            ->deleteJson(route('worship.attendance.destroy'), [
                'worship_id' => $this->worship->id,
                'member_id'  => 999999,
            ])
            ->assertStatus(422);
    }

    public function test_take_page_renders_delete_buttons(): void
    {
        WorshipAttendance::create([
            'worship_id'      => $this->worship->id,
            'member_id'       => $this->member->id,
            'attendance_date' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('worship.take'))
            ->assertOk()
            ->assertSee('js-delete-attendance', false)
            ->assertSee('data-member-id="' . $this->member->id . '"', false);
    }
}
