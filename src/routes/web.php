<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminRequestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', function () {
        return view('admin.auth.login');
    })->name('admin.login');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'create'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');

    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('attendance.index');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', '認証メールを再送しました。');
    })->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'show'])
        ->name('attendance.detail');

    Route::post('/attendance/detail/{id}', [AttendanceDetailController::class, 'requestCorrection'])
        ->name('attendance.requestCorrection');

    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.breakStart');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.breakEnd');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');

    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])
        ->name('stamp_correction_request.list');

    Route::post('/stamp_correction_request', [StampCorrectionRequestController::class, 'store'])
        ->name('stamp_correction_request.store');
});



Route::middleware('guest')->group(function () {
    Route::get('/admin/login', function () {
        return view('admin.auth.login');
    })->name('admin.login');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('attendance.list');

    Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])
        ->name('attendance.show');

    Route::post('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])
        ->name('attendance.update');

    Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])
        ->name('staff.list');

    Route::get('/staff/{user}/attendance', [AdminAttendanceController::class, 'staffAttendance'])
        ->name('staff.attendance');

    Route::get('/request/list', [AdminRequestController::class, 'index'])
        ->name('request.list');

    Route::get('/request/{correctionRequest}', [AdminRequestController::class, 'show'])
        ->name('request.show');

    Route::post('/request/{correctionRequest}/approve', [AdminRequestController::class, 'approve'])
        ->name('request.approve');

    Route::get('/staff/{user}/attendance/csv', [AdminAttendanceController::class, 'exportStaffAttendanceCsv'])
        ->name('staff.attendance.csv');
});