<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Worship;
use App\Models\WorshipAttendance;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_members' => Member::active()->count(),
            'today_attendance' => WorshipAttendance::forDate(today())->count(),
            'worship_services' => Worship::active()->count(),
            'total_attendance_records' => WorshipAttendance::count(),
        ];

        $worships = Worship::active()->get();

        return view('dashboard', compact('stats', 'worships'));
    }
}
