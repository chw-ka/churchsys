<?php

namespace App\Http\Controllers;

use App\Models\Worship;
use App\Models\WorshipAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorshipController extends Controller
{
    public function admin()
    {
        $worships = Worship::orderBy('weekly')->get();
        return view('worship.admin', compact('worships'));
    }

    public function create()
    {
        return view('worship.form', [
            'worship' => null,
            'weeklyList' => Worship::weeklyList(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weekly' => 'required|integer|between:0,6',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'state' => 'required|integer',
        ]);

        Worship::create($validated);

        return redirect()->route('worship.admin')->with('success', '崇拜已新增');
    }

    public function edit(Worship $worship)
    {
        return view('worship.form', [
            'worship' => $worship,
            'weeklyList' => Worship::weeklyList(),
        ]);
    }

    public function update(Request $request, Worship $worship)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weekly' => 'required|integer|between:0,6',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'state' => 'required|integer',
        ]);

        $worship->update($validated);

        return redirect()->route('worship.admin')->with('success', '崇拜已更新');
    }

    public function report(Request $request)
    {
        $start = $request->get('start', now()->startOfMonth()->toDateString());
        $end = $request->get('end', now()->toDateString());
        $worships = Worship::active()->get();

        $attendances = WorshipAttendance::whereBetween('attendance_date', [$start, $end])
            ->with('worship')
            ->get();

        // Group by date then by worship
        $grouped = $attendances->groupBy(function ($item) {
            return $item->attendance_date->format('Y-m-d');
        });

        $report = [];
        foreach ($grouped as $date => $items) {
            $counts = $items->groupBy('worship_id')->map->count();
            $report[] = [
                'date' => $date,
                'counts' => $counts->toArray(),
                'total' => $items->count(),
            ];
        }

        ksort($report);

        return view('worship.report', compact('worships', 'start', 'end', 'report'));
    }
}
