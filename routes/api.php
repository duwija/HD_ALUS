<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProbeController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\CustomerApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group.
|
*/

// route default bawaan Laravel
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// ========================
// Tambahkan route probe di luar blok di atas
// ========================

Route::get ('/probe/push',  [ProbeController::class, 'push']);     // Mikrotik (GET)
Route::post('/probe/push',  [ProbeController::class, 'pushPost']); // JSON probe (POST)
Route::get ('/probe/status',[ProbeController::class, 'status']);   // API status
Route::get ('/probe/alerts',[ProbeController::class, 'alerts']);   // API alert log
Route::post('/wa/{session}/send-media', [WhatsappController::class, 'sendMedia']);

// ========================
// Customer Mobile API
// ========================
Route::prefix('customer')->group(function () {
    // Public
    Route::post('/login',  [CustomerApiController::class, 'login']);

    // Protected (Bearer token)
    Route::post('/register-token',        [CustomerApiController::class, 'registerToken']);
    Route::get ('/dashboard/{customerId}', [CustomerApiController::class, 'dashboard']);
    Route::get ('/tickets/{customerId}',   [CustomerApiController::class, 'tickets']);
    Route::post('/logout',                [CustomerApiController::class, 'logout']);
});

// ========================
// Employee Attendance API (Mobile App Karyawan)
// ========================
Route::prefix('employee')->namespace('Api')->group(function () {
    // Public
    Route::post('/login', 'EmployeeAttendanceController@login');

    // Protected (Sanctum Bearer token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout',                    'EmployeeAttendanceController@logout');
        Route::get ('/profile',                   'EmployeeAttendanceController@profile');

        // Lokasi
        Route::get ('/locations',                 'EmployeeAttendanceController@locations');
        Route::post('/location/check',            'EmployeeAttendanceController@checkLocation');

        // Shift & Jadwal
        Route::get ('/shift/today',               'EmployeeAttendanceController@shiftToday');
        Route::get ('/schedule',                  'EmployeeAttendanceController@schedule');

        // Absensi
        Route::get ('/attendance/today',          'EmployeeAttendanceController@today');
        Route::post('/attendance/clock-in',       'EmployeeAttendanceController@clockIn');
        Route::post('/attendance/clock-out',      'EmployeeAttendanceController@clockOut');
        Route::get ('/attendance/history',        'EmployeeAttendanceController@history');

        // Tiket
        Route::get ('/tickets',                   'TicketApiController@index');
        Route::get ('/tickets/summary',           'TicketApiController@summary');
        Route::get ('/tickets/{id}',              'TicketApiController@show');
        Route::post('/tickets/{id}/update',       'TicketApiController@addUpdate');
        Route::patch('/tickets/{id}/status',      'TicketApiController@updateStatus');

        // Izin / Cuti
        Route::get ('/leaves',                       'LeaveOvertimeController@leaveIndex');
        Route::post('/leaves',                       'LeaveOvertimeController@leaveStore');
        Route::get ('/leaves/{id}',                  'LeaveOvertimeController@leaveShow');
        Route::post('/leaves/{id}/approve',          'LeaveOvertimeController@leaveApprove');

        // Lembur
        Route::get ('/overtimes',                    'LeaveOvertimeController@overtimeIndex');
        Route::post('/overtimes',                    'LeaveOvertimeController@overtimeStore');
        Route::get ('/overtimes/{id}',               'LeaveOvertimeController@overtimeShow');
        Route::post('/overtimes/{id}/approve',       'LeaveOvertimeController@overtimeApprove');

        // Supervisor: daftar pending approval
        Route::get ('/supervisor/leaves',            'LeaveOvertimeController@supervisorLeaves');
        Route::get ('/supervisor/overtimes',         'LeaveOvertimeController@supervisorOvertimes');

        // FCM Token
        Route::post('/fcm-token',                    'LeaveOvertimeController@updateFcmToken');
    });
});

