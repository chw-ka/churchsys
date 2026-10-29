<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Models\Worship;
use App\Models\WorshipAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the two "wish items" reported by the church (2026-10-29):
 *
 * 1. 會友出席資料 must be filterable by 類別 so that 新朋友 (temporary members)
 *    do not bury the real membership behind 120+ pages.
 * 2. 崇拜出席資料 must let staff drill down from a weekly count to the actual
 *    member list (the old Yii listByWorshipWeek screen), instead of showing
 *    bare numbers with no member information.
 */
class WorshipAttendanceMemberFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Worship $sunday;
    private Worship $saturday;
    private Member $member;
    private Member $coworker;
    private Member $newFriend;

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

        $this->sunday = Worship::create([
            'state' => 1, 'name' => '主日第一堂', 'start_time' => '08:00', 'end_time' => '09:25', 'weekly' => 0, 'remarks' => '',
        ]);
        $this->saturday = Worship::create([
            'state' => 1, 'name' => '週六崇拜', 'start_time' => '16:00', 'end_time' => '18:00', 'weekly' => 6, 'remarks' => '',
        ]);

        $this->member = Member::create([
            'state' => 1, 'code' => '1001', 'name' => '陳會友', 'account_type' => Member::ACCOUNT_TYPE_MEMBER,
        ]);
        $this->coworker = Member::create([
            'state' => 1, 'code' => '1002', 'name' => '李同工', 'account_type' => Member::ACCOUNT_TYPE_CO_MEMBER,
        ]);
        $this->newFriend = Member::create([
            'state' => 1, 'code' => '2001', 'name' => '王新朋友', 'account_type' => Member::ACCOUNT_TYPE_NEW_MEMBER,
        ]);

        // Week 2026-10-19 (Mon) ~ 2026-10-25 (Sun)
        $this->attend($this->sunday->id, $this->member->id, '2026-10-25 08:10:00');
        $this->attend($this->sunday->id, $this->newFriend->id, '2026-10-25 08:15:00');
        $this->attend($this->saturday->id, $this->coworker->id, '2026-10-24 16:05:00');
        // Outside the tested week
        $this->attend($this->sunday->id, $this->member->id, '2026-10-11 08:10:00');
    }

    private function attend(int $worshipId, int $memberId, string $when): void
    {
        WorshipAttendance::create([
            'member_id'       => $memberId,
            'worship_id'      => $worshipId,
            'attendance_date' => $when,
        ]);
    }

    // ---- Wish item 1: 類別 filter on 會友出席資料 ----

    public function test_by_member_defaults_to_members_and_coworkers_only(): void
    {
        $response = $this->actingAs($this->user)->get(route('worship.attendance.by-member'));

        $response->assertOk();
        $response->assertSee('陳會友');
        $response->assertSee('李同工');
        $response->assertDontSee('王新朋友');
    }

    public function test_by_member_new_friend_filter_shows_only_new_friends(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('worship.attendance.by-member', ['type' => 'new']));

        $response->assertOk();
        $response->assertSee('王新朋友');
        $response->assertDontSee('陳會友');
    }

    public function test_by_member_all_filter_restores_the_old_everything_view(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('worship.attendance.by-member', ['type' => 'all']));

        $response->assertOk();
        $response->assertSee('陳會友');
        $response->assertSee('王新朋友');
    }

    public function test_by_member_member_only_filter_excludes_coworkers(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('worship.attendance.by-member', ['type' => 'member_only']));

        $response->assertOk();
        $response->assertSee('陳會友');
        $response->assertDontSee('李同工');
        $response->assertDontSee('王新朋友');
    }

    public function test_by_member_category_filter_is_kept_in_pagination_links(): void
    {
        // More than one page (30 per page) so that pagination links are rendered
        foreach (range(1, 35) as $i) {
            Member::create([
                'state' => 1, 'code' => '30' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'name' => '新朋友' . $i, 'account_type' => Member::ACCOUNT_TYPE_NEW_MEMBER,
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('worship.attendance.by-member', ['type' => 'new']));

        $response->assertOk();
        $response->assertSee('type=new', false);
    }

    // ---- Wish item 2: weekly count drill-down ----

    public function test_weekly_matrix_links_each_worship_count_to_the_member_list(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('worship.attendance.by-worship', ['year' => 2026]))
            ->getContent();

        $this->assertStringContainsString('by-worship/detail', $html);
        $this->assertStringContainsString('worship_id=' . $this->sunday->id, $html);
    }

    public function test_detail_lists_the_members_who_attended_that_worship_in_that_week(): void
    {
        $response = $this->actingAs($this->user)->get(route('worship.attendance.by-worship-detail', [
            'start'      => '2026-10-19',
            'end'        => '2026-10-25',
            'worship_id' => $this->sunday->id,
        ]));

        $response->assertOk();
        $response->assertSee('陳會友');
        $response->assertSee('王新朋友');       // detail defaults to all categories
        $response->assertDontSee('李同工');     // attended 週六崇拜, not 主日第一堂
        $response->assertDontSee('2026-10-11'); // outside the week
    }

    public function test_detail_without_worship_filter_covers_the_whole_week(): void
    {
        $response = $this->actingAs($this->user)->get(route('worship.attendance.by-worship-detail', [
            'start' => '2026-10-19',
            'end'   => '2026-10-25',
        ]));

        $response->assertOk();
        $response->assertSee('陳會友');
        $response->assertSee('李同工');
        $response->assertSee('主日第一堂');
        $response->assertSee('週六崇拜');
    }

    public function test_detail_can_be_searched_by_member_name(): void
    {
        $response = $this->actingAs($this->user)->get(route('worship.attendance.by-worship-detail', [
            'start' => '2026-10-19',
            'end'   => '2026-10-25',
            'search' => '陳',
        ]));

        $response->assertOk();
        $response->assertSee('陳會友');
        $response->assertDontSee('王新朋友');
        $response->assertDontSee('李同工');
    }

    public function test_detail_can_be_filtered_to_members_only(): void
    {
        $response = $this->actingAs($this->user)->get(route('worship.attendance.by-worship-detail', [
            'start' => '2026-10-19',
            'end'   => '2026-10-25',
            'type'  => 'member',
        ]));

        $response->assertOk();
        $response->assertSee('陳會友');
        $response->assertSee('李同工');
        $response->assertDontSee('王新朋友');
    }

    public function test_detail_requires_a_valid_date_range(): void
    {
        $this->actingAs($this->user)
            ->get(route('worship.attendance.by-worship-detail', ['start' => '2026-10-25', 'end' => '2026-10-19']))
            ->assertSessionHasErrors('end');
    }
}
