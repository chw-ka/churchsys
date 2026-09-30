<?php

namespace App\Http\Controllers;

use App\Models\GroupPeriod;
use App\Models\Worship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorshipReportController extends Controller
{
    /**
     * Main report index page - lists all available reports
     */
    public function index()
    {
        $groupPeriods = GroupPeriod::orderBy('id')->get();
        return view('worship.report-index', compact('groupPeriods'));
    }

    /**
     * 臨時會友簽到表 - New Member Attendance Form Report
     */
    public function newMemberAttendanceForm(Request $request)
    {
        $year = (int) date('Y');
        $week = (int) date('W');

        $query = "SELECT DISTINCT 
                    worship.name AS worship, 
                    member.code AS member_code, 
                    wa.worship_count AS sign_in_counts, 
                    member.name, member.remarks, 
                    (DATE_ADD(member.arrived_date, INTERVAL 8 DAY) > DATE(NOW())) AS is_new, 
                    (member.new_card = 2 OR member.new_card = 1) AS has_new_card, 
                    (wa.worship_count > 6) AS need_form 
                FROM tbl_member AS member 
                INNER JOIN (
                    SELECT 
                        SUM(DATE(attendance_date) > DATE_SUB(NOW(), INTERVAL 2 MONTH)) AS worship_count, 
                        worship_id, 
                        member_id 
                    FROM tbl_worship_attendance 
                    GROUP BY member_id, worship_id
                ) AS wa ON wa.member_id = member.id 
                INNER JOIN tbl_worship AS worship ON wa.worship_id = worship.id 
                WHERE (member.account_type = 0) OR member.new_card > 0 
                ORDER BY member.name";

        $data = DB::select($query);
        $worshipList = Worship::all();

        return view('worship.reports.new-member-attendance', compact('data', 'year', 'week', 'worshipList'));
    }

    /**
     * 新增會友列表 - New Membership Card Report
     */
    public function newMembershipCard(Request $request)
    {
        $year = (int) date('Y', time() - 7 * 24 * 3600);
        $week = (int) date('W', time() - 7 * 24 * 3600);

        $query = "SELECT period.name AS period, small_group.name AS small_group, 
                    member.code AS member_code, member.name 
                FROM tbl_member AS member 
                LEFT JOIN tbl_group_member AS group_member ON group_member.member_id = member.id 
                LEFT JOIN tbl_group AS small_group ON group_member.group_id = small_group.id 
                LEFT JOIN tbl_group_period AS period ON period.id = small_group.period_id 
                WHERE member.new_card > 0";

        $data = DB::select($query);

        return view('worship.reports.new-membership-card', compact('data', 'year', 'week'));
    }

    /**
     * 新朋友報告 - Weekly New Member Report
     */
    public function weeklyNewMember(Request $request)
    {
        $yearWeek = $request->get('week_no', '');
        $year = (int) date('Y');
        $week = (int) date('W');

        if ($yearWeek !== '' && strpos($yearWeek, '-') !== false) {
            $year = (int) substr($yearWeek, 0, 4);
            $week = (int) substr($yearWeek, -2);
        }

        $query = "SELECT DISTINCT worship.name AS worship, member.arrived_date, 
                    member.code AS member_code, member.name, member.remarks 
                FROM tbl_worship_attendance AS wa 
                INNER JOIN tbl_member AS member ON wa.member_id = member.id 
                INNER JOIN tbl_worship AS worship ON wa.worship_id = worship.id 
                WHERE YEAR(member.arrived_date) = ? 
                AND WEEKOFYEAR(member.arrived_date) = ? 
                ORDER BY arrived_date, member_code";

        $data = DB::select($query, [$year, $week]);

        return view('worship.reports.weekly-new-member', compact('data', 'year', 'week'));
    }

    /**
     * 出席報告 - Attendance Report
     */
    public function attendance(Request $request)
    {
        $memberType = $request->get('member_type', '');
        $yearWeek = $request->get('week_no', '');
        $year = (int) date('Y');
        $week = (int) date('W');
        $period = (int) $request->get('group_period', 0);

        if ($yearWeek !== '' && strpos($yearWeek, '-') !== false) {
            $year = (int) substr($yearWeek, 0, 4);
            $week = (int) substr($yearWeek, -2);
        }

        $periodSql = $period > 0 ? "period.id = " . $period : "1";
        $typeSql = $memberType === '' ? '1' : "member.account_type = " . (int) $memberType;

        $query = "SELECT last_worship.worship_name AS worship, 
                    last_worship.last_attendance_date, 
                    period.name AS period, 
                    small_group.name AS small_group, 
                    member.code AS member_code, 
                    member.name 
                FROM tbl_member AS member 
                INNER JOIN (
                    SELECT MAX(worship.name) AS worship_name, wa.member_id, MAX(attendance_date) AS last_attendance_date 
                    FROM tbl_worship_attendance AS wa 
                    INNER JOIN tbl_worship AS worship ON worship.id = wa.worship_id 
                    WHERE YEAR(wa.attendance_date) = ? 
                    AND WEEKOFYEAR(wa.attendance_date) = ? 
                    GROUP BY wa.member_id
                ) AS last_worship ON last_worship.member_id = member.id 
                LEFT JOIN tbl_group_member AS group_member ON group_member.member_id = member.id 
                LEFT JOIN tbl_group AS small_group ON group_member.group_id = small_group.id 
                LEFT JOIN tbl_group_period AS period ON period.id = small_group.period_id 
                WHERE {$periodSql} 
                AND {$typeSql} 
                GROUP BY member.code, member.name, last_worship.worship_name, last_worship.last_attendance_date, period.name, small_group.name";

        $data = DB::select($query, [$year, $week]);
        $groupPeriods = GroupPeriod::orderBy('id')->get();

        return view('worship.reports.attendance', compact('data', 'year', 'week', 'period', 'groupPeriods'));
    }

    /**
     * 缺席報告 - Absent Report
     */
    public function absent(Request $request)
    {
        $memberType = $request->get('member_type', '');
        $yearWeek = $request->get('week_no', '');
        $year = (int) date('Y');
        $week = (int) date('W');
        $period = (int) $request->get('group_period', 0);
        $boundary = $request->get('boundary', '');

        if ($yearWeek !== '' && strpos($yearWeek, '-') !== false) {
            $year = (int) substr($yearWeek, 0, 4);
            $week = (int) substr($yearWeek, -2);
        }

        $periodSql = $period > 0 ? "period.id = " . $period : "1";
        $typeSql = $memberType === '' ? '1' : "member.account_type = " . (int) $memberType;
        $boundarySql = '';
        if ($boundary !== '') {
            $boundaryDate = date('Y-m-d', strtotime("-" . (int) $boundary . " months"));
            $boundarySql = "AND DATE(last_worship.last_attendance_date) > '{$boundaryDate}'";
        }

        $query = "SELECT last_worship.worship_name AS worship, 
                    last_worship.last_attendance_date, 
                    period.name AS period, 
                    small_group.name AS small_group, 
                    member.code AS member_code, 
                    member.name 
                FROM tbl_member AS member 
                INNER JOIN (
                    SELECT MAX(worship.name) AS worship_name, wa.member_id, MAX(attendance_date) AS last_attendance_date 
                    FROM tbl_worship_attendance AS wa 
                    INNER JOIN tbl_worship AS worship ON worship.id = wa.worship_id 
                    WHERE YEAR(wa.attendance_date) <= ? 
                    AND WEEKOFYEAR(wa.attendance_date) <= ? 
                    GROUP BY wa.member_id
                ) AS last_worship ON last_worship.member_id = member.id 
                LEFT JOIN tbl_group_member AS group_member ON group_member.member_id = member.id 
                LEFT JOIN tbl_group AS small_group ON group_member.group_id = small_group.id 
                LEFT JOIN tbl_group_period AS period ON period.id = small_group.period_id 
                WHERE {$periodSql} 
                AND {$typeSql} 
                AND YEARWEEK(last_worship.last_attendance_date, 3) < ? 
                {$boundarySql}
                GROUP BY member.code, member.name, last_worship.worship_name, last_worship.last_attendance_date, period.name, small_group.name";

        $data = DB::select($query, [$year, $week, $year . $week]);
        $groupPeriods = GroupPeriod::orderBy('id')->get();

        return view('worship.reports.absent', compact('data', 'year', 'week', 'period', 'groupPeriods'));
    }

    /**
     * 全年崇拜出席報告 - Annual Report
     */
    public function annual(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $worshipList = Worship::all();

        $selectCols = "MAX(DATE(wa.attendance_date)) AS d, ";
        foreach ($worshipList as $worship) {
            $selectCols .= "SUM(wa.worship_id = " . $worship->id . ") AS w" . $worship->id . ", ";
        }
        $selectCols .= "COUNT(DISTINCT DATE(attendance_date)) AS total, ";
        $selectCols .= "COUNT(DISTINCT wa.member_id) AS total_p";

        $query = "SELECT stat.*, nmc.new_member_count
                FROM (
                    SELECT {$selectCols}
                    FROM tbl_worship_attendance AS wa 
                    WHERE YEAR(attendance_date) = ? 
                    GROUP BY WEEKOFYEAR(attendance_date) 
                    ORDER BY d
                ) AS stat 
                LEFT JOIN (
                    SELECT MAX(fc.first_come) AS dd, COUNT(*) AS new_member_count 
                    FROM (
                        SELECT MIN(DATE(attendance_date)) AS first_come, member_id 
                        FROM tbl_worship_attendance 
                        GROUP BY member_id 
                        ORDER BY first_come
                    ) AS fc 
                    WHERE YEAR(fc.first_come) = ? 
                    GROUP BY WEEKOFYEAR(fc.first_come)
                ) AS nmc ON nmc.dd = stat.d";

        $data = DB::select($query, [$year, $year]);

        return view('worship.reports.annual', compact('data', 'year', 'worshipList'));
    }

    /**
     * 原始數據 - Raw Data Report
     */
    public function raw(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));

        $query = "SELECT DISTINCT member.code, member.name, 
                    WEEKOFYEAR(wa.attendance_date) AS weekno, 
                    wa.attendance_date 
                FROM tbl_worship_attendance AS wa 
                INNER JOIN tbl_member AS member ON member.id = wa.member_id AND member.state = 1 
                WHERE YEAR(wa.attendance_date) = ? 
                ORDER BY member.code, weekno";

        $data = DB::select($query, [$year]);

        return view('worship.reports.raw', compact('year', 'data'));
    }

    /**
     * 生日查詢 - Birthday Report
     */
    public function birthday(Request $request)
    {
        $yearWeek = $request->get('week_no', '');
        $year = (int) date('Y');
        $week = (int) date('W');
        $boundary = $request->get('boundary', '');

        if ($yearWeek !== '' && strpos($yearWeek, '-') !== false) {
            $year = (int) substr($yearWeek, 0, 4);
            $week = (int) substr($yearWeek, -2);
        }

        $dayOfYear = (int) date('z', strtotime('+' . ($week - 1) . ' MONDAY ' . $year . '-01-01'));

        $boundarySql = '';
        if ($boundary !== '') {
            $boundaryDate = date('Y-m-d', strtotime("-" . (int) $boundary . " months"));
            $boundarySql = "AND DATE(last_worship.last_attendance_date) > '{$boundaryDate}'";
        }

        $query = "SELECT last_worship.worship_name AS worship, 
                    last_worship.last_attendance_date, 
                    period.name AS period, 
                    small_group.name AS small_group, 
                    member.code AS member_code, 
                    member.name, 
                    CONCAT(DAYOFMONTH(member.birthday), '/', MONTH(member.birthday)) AS birthday 
                FROM tbl_member AS member 
                INNER JOIN (
                    SELECT MAX(worship.name) AS worship_name, wa.member_id, MAX(attendance_date) AS last_attendance_date 
                    FROM tbl_worship_attendance AS wa 
                    INNER JOIN tbl_worship AS worship ON worship.id = wa.worship_id 
                    WHERE YEAR(wa.attendance_date) <= ? 
                    AND WEEKOFYEAR(wa.attendance_date) <= ? 
                    GROUP BY wa.member_id
                ) AS last_worship ON last_worship.member_id = member.id 
                LEFT JOIN tbl_group_member AS group_member ON group_member.member_id = member.id 
                LEFT JOIN tbl_group AS small_group ON group_member.group_id = small_group.id 
                LEFT JOIN tbl_group_period AS period ON period.id = small_group.period_id 
                WHERE DAYOFYEAR(member.birthday) <= ? AND DAYOFYEAR(member.birthday) >= ? 
                {$boundarySql}
                GROUP BY member.code, member.name, last_worship.worship_name, last_worship.last_attendance_date, period.name, small_group.name, member.birthday";

        $data = DB::select($query, [$year, $week, $dayOfYear + 7, $dayOfYear]);

        return view('worship.reports.birthday', compact('data', 'year', 'week'));
    }
}
