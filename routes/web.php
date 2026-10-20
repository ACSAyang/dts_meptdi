<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DutyController;
use App\Http\Controllers\IncomingController;
use App\Http\Controllers\OutgoingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [
        PasswordResetLinkController::class,
        'create'
    ])->name('password.request');

    Route::post('/forgot-password', [
        PasswordResetLinkController::class,
        'store'
    ])->name('password.email');

    Route::get('/reset-password/{token}', [
        NewPasswordController::class,
        'create'
    ])->name('password.reset');

    Route::post('/reset-password', [
        NewPasswordController::class,
        'store'
    ])->name('password.update_user');
});

// One user level: every signed-in, active account can use every part of the system.
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account/password', [AuthController::class, 'editPassword'])->name('password.edit');
    Route::put('/account/password', [AuthController::class, 'updatePassword'])->name('password.update');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');

    // Document tracking calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

    // Duty Office Watch
    Route::get('/duty', [DutyController::class, 'index'])->name('duty.index');
    Route::post('/duty/start', [DutyController::class, 'start'])->name('duty.start');
    Route::get('/duty/{duty}', [DutyController::class, 'show'])->name('duty.show');
    Route::get('/duty/{duty}/turnover', [DutyController::class, 'turnoverForm'])->name('duty.turnover');
    Route::post('/duty/{duty}/turnover', [DutyController::class, 'turnover'])->name('duty.turnover.store');

    // Viewing is always open; recording (register, edit, any action) needs a watch on duty.

    // Incoming
    Route::get('/incoming', [IncomingController::class, 'index'])->name('incoming.index');
    Route::get('/incoming/create', [IncomingController::class, 'create'])->name('incoming.create')->middleware('on.duty');
    Route::post('/incoming', [IncomingController::class, 'store'])->name('incoming.store')->middleware('on.duty');
    Route::get('/incoming/{incoming}', [IncomingController::class, 'show'])->name('incoming.show');
    Route::get('/incoming/{incoming}/edit', [IncomingController::class, 'edit'])->name('incoming.edit')->middleware('on.duty');
    Route::put('/incoming/{incoming}', [IncomingController::class, 'update'])->name('incoming.update')->middleware('on.duty');
    Route::post('/incoming/{incoming}/route', [IncomingController::class, 'route'])->name('incoming.route')->middleware('on.duty');
    Route::post('/incoming/{incoming}/action', [IncomingController::class, 'actionUpdate'])->name('incoming.action')->middleware('on.duty');
    Route::post('/incoming/{incoming}/close', [IncomingController::class, 'close'])->name('incoming.close')->middleware('on.duty');
    Route::post('/incoming/{incoming}/cancel', [IncomingController::class, 'cancel'])->name('incoming.cancel')->middleware('on.duty');
    Route::post('/incoming/{incoming}/reopen', [IncomingController::class, 'reopen'])->name('incoming.reopen')->middleware('on.duty');
    Route::post('/incoming/{incoming}/attachments', [IncomingController::class, 'attach'])->name('incoming.attach')->middleware('on.duty');

    // Outgoing
    Route::get('/outgoing', [OutgoingController::class, 'index'])->name('outgoing.index');
    Route::get('/outgoing/create', [OutgoingController::class, 'create'])->name('outgoing.create')->middleware('on.duty');
    Route::post('/outgoing', [OutgoingController::class, 'store'])->name('outgoing.store')->middleware('on.duty');
    Route::get('/outgoing/{outgoing}', [OutgoingController::class, 'show'])->name('outgoing.show');
    Route::get('/outgoing/{outgoing}/edit', [OutgoingController::class, 'edit'])->name('outgoing.edit')->middleware('on.duty');
    Route::put('/outgoing/{outgoing}', [OutgoingController::class, 'update'])->name('outgoing.update')->middleware('on.duty');
    Route::post('/outgoing/{outgoing}/dispatch', [OutgoingController::class, 'markDispatched'])->name('outgoing.dispatch')->middleware('on.duty');
    Route::post('/outgoing/{outgoing}/attempt', [OutgoingController::class, 'attempt'])->name('outgoing.attempt')->middleware('on.duty');
    Route::post('/outgoing/{outgoing}/receive', [OutgoingController::class, 'receive'])->name('outgoing.receive')->middleware('on.duty');
    Route::post('/outgoing/{outgoing}/verify', [OutgoingController::class, 'verify'])->name('outgoing.verify')->middleware('on.duty');
    Route::post('/outgoing/{outgoing}/cancel', [OutgoingController::class, 'cancel'])->name('outgoing.cancel')->middleware('on.duty');
    Route::post('/outgoing/{outgoing}/reopen', [OutgoingController::class, 'reopen'])->name('outgoing.reopen')->middleware('on.duty');
    Route::post('/outgoing/{outgoing}/attachments', [OutgoingController::class, 'attach'])->name('outgoing.attach')->middleware('on.duty');

    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('/reports/pending', [ReportController::class, 'pending'])->name('reports.pending');
    Route::get('/reports/delivery', [ReportController::class, 'delivery'])->name('reports.delivery');
    Route::get('/reports/duty', [ReportController::class, 'duty'])->name('reports.duty');

    // System: user accounts, audit trail, backups
    Route::prefix('system')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
        Route::post('/backups/restore-test', [BackupController::class, 'restoreTest'])->name('backups.restore-test');
        Route::get('/backups/download', [BackupController::class, 'download'])->name('backups.download');
    });

    //Email Test Route
    // // Send a test email
    // Route::post('/admin/test-email', function (Request $request) {
    //     $validated = $request->validate([
    //         'email' => ['required', 'email'],
    //     ]);

    //     Mail::to($validated['email'])->send(
    //         new TestEmail(
    //             username: 'Test User',
    //             loginUrl: route('login')
    //         )
    //     );

    //     return back()->with(
    //         'success',
    //         'Test email sent successfully.'
    //     );
    // })->name('admin.test-email');
});
