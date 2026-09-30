<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Worship;
use App\Models\WorshipAttendance;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WorshipAttendanceController extends Controller
{
    /**
     * Take attendance (core feature)
     */
    public function take(Request $request)
    {
        $worships = Worship::active()->get();
        $today = today()->toDateString();

        // Get today's attendance counts per worship
        $todayCounts = WorshipAttendance::whereDate('attendance_date', $today)
            ->select('worship_id', DB::raw('COUNT(*) as count'))
            ->groupBy('worship_id')
            ->pluck('count', 'worship_id');

        return view('worship.take', compact('worships', 'today', 'todayCounts'));
    }

    /**
     * Process attendance check-in via member code
     */
    public function checkin(Request $request)
    {
        $request->validate([
            'member_code' => 'required|string|max:10',
            'worship_id' => 'required|integer|exists:tbl_worship,id',
        ]);

        $member = Member::where('code', $request->member_code)
            ->where('state', Member::STATE_ACTIVE)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => '編號找不到，請確認會友編號',
            ], 404);
        }

        // Check if already checked in for this worship today
        $exists = WorshipAttendance::where('member_id', $member->id)
            ->where('worship_id', $request->worship_id)
            ->whereDate('attendance_date', today())
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "{$member->name} 今天已簽到",
            ], 409);
        }

        WorshipAttendance::create([
            'member_id' => $member->id,
            'worship_id' => $request->worship_id,
            'attendance_date' => now(),
        ]);

        // Build today's attendance list HTML
        $todayAttendances = WorshipAttendance::whereDate('attendance_date', today())
            ->where('worship_id', $request->worship_id)
            ->with('member')
            ->orderBy('attendance_date', 'desc')
            ->get();

        $html = '<table style="width:100%;border-collapse:collapse;">';
        foreach ($todayAttendances as $att) {
            $time = \Carbon\Carbon::parse($att->attendance_date)->format('H:i');
            $html .= '<tr>';
            $html .= '<td style="padding:2px;border-bottom:1px solid #eee;">' . e($att->member->name . ' (' . $att->member->code . ')') . '</td>';
            $html .= '<td style="padding:2px;border-bottom:1px solid #eee;">' . $time . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        return response()->json([
            'success' => true,
            'message' => "{$member->name} 簽到成功",
            'member' => [
                'name' => $member->name,
                'code' => $member->code,
            ],
            'html' => $html,
        ]);
    }

    /**
     * Admin bulk attendance page (backdated / spreadsheet paste)
     */
    public function adminTake(Request $request)
    {
        $worships = Worship::active()->orderBy('weekly')->orderBy('start_time')->get();

        return view('worship.admin_take', [
            'worships'   => $worships,
            'added'      => session('added', []),
            'duplicates' => session('duplicates', []),
            'notFound'   => session('notFound', []),
            'ambiguous'  => session('ambiguous', []),
            'submitted'  => session('submitted', false),
            'old'        => session('old', []),
        ]);
    }

    /**
     * Process bulk attendance submission (paste member codes / names, one per line)
     */
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'worship_id'      => 'required|integer|exists:tbl_worship,id',
            'attendance_date' => 'required|date|before_or_equal:today',
            'entries'         => 'required|string',
        ], [
            'attendance_date.before_or_equal' => '補簽日期只可以是今天或以前',
            'entries.required'                => '請貼上或輸入會友編號/姓名',
        ]);

        $worship = Worship::findOrFail($validated['worship_id']);
        $date = $validated['attendance_date'];
        // Backdated records keep the chosen date with the current time
        $timestamp = $date . ' ' . now()->format('H:i:s');

        // Parse lines; supports spreadsheet paste (tab/comma separated) — first cell is used
        $lines = collect(preg_split('/\r\n|\r|\n/', $validated['entries']))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '');

        $added = [];
        $duplicates = [];
        $notFound = [];
        $ambiguous = [];

        foreach ($lines as $line) {
            // Take the first cell if pasted from a spreadsheet (/u: multibyte-safe for Chinese names)
            $token = trim(preg_split("/[\t,，;；|]+/u", $line)[0] ?? $line);
            if ($token === '') {
                continue;
            }

            // Look up by member code first, then by exact name
            $member = Member::active()->where('code', $token)->first();

            if (!$member) {
                $matches = Member::active()->where('name', $token)->get();
                if ($matches->count() === 1) {
                    $member = $matches->first();
                } elseif ($matches->count() > 1) {
                    $ambiguous[] = [
                        'input'   => $token,
                        'options' => $matches->map(fn ($m) => "{$m->name} ({$m->code})")->implode('、'),
                    ];
                    continue;
                }
            }

            if (!$member) {
                $notFound[] = $token;
                continue;
            }

            // Skip if already recorded for this worship on this date
            $exists = WorshipAttendance::where('member_id', $member->id)
                ->where('worship_id', $worship->id)
                ->whereDate('attendance_date', $date)
                ->exists();

            if ($exists) {
                $duplicates[] = "{$member->name} ({$member->code})";
                continue;
            }

            WorshipAttendance::create([
                'member_id'       => $member->id,
                'worship_id'      => $worship->id,
                'attendance_date' => $timestamp,
            ]);

            $added[] = "{$member->name} ({$member->code})";
        }

        // Redirect back so the page can be refreshed safely (PRG pattern)
        return redirect()->route('worship.attendance.admin-take')->with([
            'added'      => $added,
            'duplicates' => $duplicates,
            'notFound'   => $notFound,
            'ambiguous'  => $ambiguous,
            'submitted'  => true,
            // Only repopulate the textarea when there is something to fix
            'old'        => (empty($notFound) && empty($ambiguous)) ? [] : [
                'entries'         => $validated['entries'],
                'worship_id'      => $worship->id,
                'attendance_date' => $date,
            ],
        ]);
    }

    /**
     * List attendance by member
     */
    public function listByMember(Request $request)
    {
        $query = Member::active()
            ->withCount([
                'worshipAttendances as last_date' => fn($q) => $q->select(DB::raw('MAX(attendance_date)')),
                'worshipAttendances as count_2m' => fn($q) => $q->where('attendance_date', '>=', now()->subMonths(2)),
                'worshipAttendances as count_6m' => fn($q) => $q->where('attendance_date', '>=', now()->subMonths(6)),
                'worshipAttendances as count_1y' => fn($q) => $q->where('attendance_date', '>=', now()->subMonths(12)),
            ])
            ->orderBy('code');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $members = $query->paginate(30);

        return view('worship.list_by_member', compact('members'));
    }

    /**
     * List attendance by worship (weekly summary)
     */
    public function listByWorship(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $worships = Worship::active()->orderBy('id')->get();

        // Build the weekly matrix in PHP so it works on any DB driver
        // (MySQL in production, SQLite in tests). One aggregated query only.
        $rows = WorshipAttendance::selectRaw('DATE(attendance_date) as d, worship_id, COUNT(*) as cnt')
            ->whereYear('attendance_date', $year)
            ->groupByRaw('DATE(attendance_date), worship_id')
            ->get();

        $weeks = [];
        foreach ($rows as $row) {
            $date = Carbon::parse($row->d);
            $key = $date->isoWeekYear . '-' . $date->isoWeek;

            if (!isset($weeks[$key])) {
                $weeks[$key] = [
                    'week_key'   => $key,
                    'week_no'    => $date->isoWeek,
                    'week_start' => $row->d,
                    'week_end'   => $row->d,
                    'counts'     => [],
                ];
            }

            $weeks[$key]['week_start'] = min($weeks[$key]['week_start'], $row->d);
            $weeks[$key]['week_end']   = max($weeks[$key]['week_end'], $row->d);
            $weeks[$key]['counts'][$row->worship_id] = ($weeks[$key]['counts'][$row->worship_id] ?? 0) + (int) $row->cnt;
        }

        // Newest week first
        usort($weeks, fn ($a, $b) => strcmp($b['week_start'], $a['week_start']));

        $perPage = 30;
        $page = max(1, (int) $request->get('page', 1));
        $weeklyData = new LengthAwarePaginator(
            array_map(fn ($w) => (object) $w, array_slice($weeks, ($page - 1) * $perPage, $perPage)),
            count($weeks),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('worship.list_by_worship', compact('weeklyData', 'year', 'worships'));
    }

    /**
     * Delete an attendance record
     */
    public function destroy($id)
    {
        $attendance = WorshipAttendance::findOrFail($id);
        $attendance->delete();

        return back()->with('success', '簽到記錄已刪除');
    }
}
