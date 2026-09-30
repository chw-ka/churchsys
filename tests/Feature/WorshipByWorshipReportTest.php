<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Models\Worship;
use App\Models\WorshipAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the weekly "崇拜出席資料" matrix.
 *
 * Bug (fixed): the page counted attendance only for the single earliest
 * attendance date of each week. Because 週六崇拜 (Saturday) attendance is the
 * earliest date of the week, every Sunday worship (主日第一堂 / 主日第二堂 /
 * 宣道園崇拜) displayed 0 — reported by the church as "missing attendance records".
 */
class WorshipByWorshipReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Worship $saturday;
    private Worship $sunday1;
    private Worship $sunday2;

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

        $this->saturday = Worship::create([
            'state' => 1, 'name' => '週六崇拜', 'start_time' => '16:00', 'end_time' => '17:30', 'weekly' => 6, 'remarks' => '',
        ]);
        $this->sunday1 = Worship::create([
            'state' => 1, 'name' => '主日第一堂', 'start_time' => '08:00', 'end_time' => '09:30', 'weekly' => 0, 'remarks' => '',
        ]);
        $this->sunday2 = Worship::create([
            'state' => 1, 'name' => '宣道園崇拜', 'start_time' => '09:30', 'end_time' => '11:00', 'weekly' => 0, 'remarks' => '',
        ]);

        // Members
        foreach (range(1, 8) as $i) {
            Member::create([
                'state' => 1, 'code' => '100' . $i, 'name' => '會友' . $i, 'account_type' => 1,
            ]);
        }

        $members = Member::orderBy('id')->pluck('id')->values();

        // Week of 2026-09-21 (Mon) ~ 2026-09-27 (Sun)
        // Saturday worship: 2 records on Sat 2026-09-26
        $this->attend($this->saturday->id, $members[0], '2026-09-26 16:05:00');
        $this->attend($this->saturday->id, $members[1], '2026-09-26 16:06:00');
        // Sunday worship #1: 3 records on Sun 2026-09-27 + 1 backfilled record on Sat 2026-09-26
        $this->attend($this->sunday1->id, $members[0], '2026-09-27 08:10:00');
        $this->attend($this->sunday1->id, $members[2], '2026-09-27 08:11:00');
        $this->attend($this->sunday1->id, $members[3], '2026-09-27 08:12:00');
        $this->attend($this->sunday1->id, $members[4], '2026-09-26 10:00:00'); // backfill, same week
        // Sunday worship #2: 1 record on Sun 2026-09-27
        $this->attend($this->sunday2->id, $members[5], '2026-09-27 09:35:00');
        // Out of scope: previous year (2025-12-28)
        $this->attend($this->sunday1->id, $members[6], '2025-12-28 08:10:00');
    }

    private function attend(int $worshipId, int $memberId, string $when): void
    {
        WorshipAttendance::create([
            'member_id'       => $memberId,
            'worship_id'      => $worshipId,
            'attendance_date' => $when,
        ]);
    }

    /** Extract the table row that contains the given date string, as an array of cell texts. */
    private function rowCells(string $html, string $needle): array
    {
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $html, $rows);
        foreach ($rows[1] as $row) {
            if (!str_contains($row, $needle)) {
                continue;
            }
            preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cells);
            return array_map(fn ($c) => trim(preg_replace('/\s+/', ' ', strip_tags($c))), $cells[1]);
        }
        return [];
    }

    public function test_sunday_worships_are_counted_in_the_same_week_as_saturday_worship(): void
    {
        $response = $this->actingAs($this->user)->get(route('worship.attendance.by-worship', ['year' => 2026]));
        $response->assertOk();

        $cells = $this->rowCells($response->getContent(), '2026-09-26');
        $this->assertNotEmpty($cells, 'Week row for 2026-09-26 not found');

        // cells: [week no, date range, 週六崇拜, 主日第一堂, 宣道園崇拜, total]
        $this->assertSame('2', $cells[2], '週六崇拜 should be 2');
        $this->assertSame('4', $cells[3], '主日第一堂 should be 4 (3 on Sunday + 1 backfilled Saturday)');
        $this->assertSame('1', $cells[4], '宣道園崇拜 should be 1');
        $this->assertSame('7', $cells[5], 'Week total should be 7');
    }

    public function test_sunday_worship_count_is_not_zero(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('worship.attendance.by-worship', ['year' => 2026]))
            ->getContent();

        $cells = $this->rowCells($html, '2026-09-26');
        $this->assertNotSame('0', $cells[3], 'Regression: 主日第一堂 must not show 0');
    }

    public function test_week_row_shows_the_week_date_range(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('worship.attendance.by-worship', ['year' => 2026]))
            ->getContent();

        $cells = $this->rowCells($html, '2026-09-26');
        $this->assertSame('2026-09-26 ~ 2026-09-27', $cells[1]);
    }

    public function test_other_years_are_excluded(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('worship.attendance.by-worship', ['year' => 2026]))
            ->getContent();

        $this->assertStringNotContainsString('2025-12-28', $html);
    }

    public function test_year_filter_can_show_previous_year(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('worship.attendance.by-worship', ['year' => 2025]))
            ->getContent();

        $this->assertStringContainsString('2025-12-28', $html);
        $cells = $this->rowCells($html, '2025-12-28');
        $this->assertSame('1', $cells[3], '主日第一堂 in 2025 week should be 1');
    }
}
