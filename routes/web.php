<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\WorshipAttendanceController;
use App\Http\Controllers\WorshipReportController;
use App\Http\Controllers\WorshipController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Password update
    Route::post('/password/update', [AuthController::class, 'updatePassword'])->name('password.update');

    // Members (static routes BEFORE {member} to avoid route conflicts)
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
    Route::get('/members/duplicate', [MemberController::class, 'duplicate'])->name('members.duplicate');
    Route::get('/members/update-account', [MemberController::class, 'updateAccount'])->name('members.update-account');
    Route::get('/members/autocomplete', [MemberController::class, 'autocomplete'])->name('members.autocomplete');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');

    // Member dynamic routes (with {member} parameter)
    Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    // Worship (static routes BEFORE {worship})
    Route::get('/worship', [WorshipController::class, 'admin'])->name('worship.admin');
    Route::get('/worship/create', [WorshipController::class, 'create'])->name('worship.create');
    Route::post('/worship', [WorshipController::class, 'store'])->name('worship.store');
    Route::get('/worship/{worship}/edit', [WorshipController::class, 'edit'])->name('worship.edit');
    Route::put('/worship/{worship}', [WorshipController::class, 'update'])->name('worship.update');

    // Worship Reports (static routes BEFORE {worship})
    Route::get('/worship/report', [WorshipReportController::class, 'index'])->name('worship.report');
    Route::get('/worship/report/new-member-attendance', [WorshipReportController::class, 'newMemberAttendanceForm'])->name('worship.report.new-member-attendance');
    Route::get('/worship/report/new-membership-card', [WorshipReportController::class, 'newMembershipCard'])->name('worship.report.new-membership-card');
    Route::get('/worship/report/weekly-new-member', [WorshipReportController::class, 'weeklyNewMember'])->name('worship.report.weekly-new-member');
    Route::get('/worship/report/attendance', [WorshipReportController::class, 'attendance'])->name('worship.report.attendance');
    Route::get('/worship/report/absent', [WorshipReportController::class, 'absent'])->name('worship.report.absent');
    Route::get('/worship/report/annual', [WorshipReportController::class, 'annual'])->name('worship.report.annual');
    Route::get('/worship/report/raw', [WorshipReportController::class, 'raw'])->name('worship.report.raw');
    Route::get('/worship/report/birthday', [WorshipReportController::class, 'birthday'])->name('worship.report.birthday');

    // Worship Attendance
    Route::get('/worship/attendance/take', [WorshipAttendanceController::class, 'take'])->name('worship.take');
    Route::post('/worship/attendance/checkin', [WorshipAttendanceController::class, 'checkin'])->name('worship.attendance.checkin');
    Route::get('/worship/attendance/admin-take', [WorshipAttendanceController::class, 'adminTake'])->name('worship.attendance.admin-take');
    Route::post('/worship/attendance/admin-take', [WorshipAttendanceController::class, 'adminStore'])->name('worship.attendance.admin-store');
    Route::get('/worship/attendance/by-member', [WorshipAttendanceController::class, 'listByMember'])->name('worship.attendance.by-member');
    Route::get('/worship/attendance/by-worship', [WorshipAttendanceController::class, 'listByWorship'])->name('worship.attendance.by-worship');
    Route::delete('/worship/attendance/{id}', [WorshipAttendanceController::class, 'destroy'])->name('worship.attendance.destroy');
});
