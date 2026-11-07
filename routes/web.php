<?php

use App\Domain\Identity\Enums\PermissionSlug;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\OverviewController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketAttachmentPreviewController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', HomeController::class)->name('dashboard');

    Route::get('/attachments/{attachment}', TicketAttachmentController::class)->name('attachments.show');
    Route::get('/attachments/{attachment}/preview', TicketAttachmentPreviewController::class)->name('attachments.preview');

    Route::patch('/notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');
    Route::delete('/notifications', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/reports', OverviewController::class)
        ->middleware(['can:'.PermissionSlug::ViewReports->value, 'can:'.PermissionSlug::ViewAllTickets->value])
        ->name('reports.overview');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
require __DIR__.'/portal.php';
require __DIR__.'/desk.php';
require __DIR__.'/management.php';
