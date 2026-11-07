<?php

use App\Domain\Identity\Enums\PermissionSlug;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\Portal\TicketMessageController;
use App\Http\Controllers\Portal\TicketRatingController;
use App\Http\Controllers\Portal\TicketResolutionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal area
|--------------------------------------------------------------------------
|
| Where everyone opens tickets and follows their own requests.
|
*/

Route::middleware('auth')->prefix('portal')->name('portal.')->group(function () {
    Route::get('tickets', [TicketController::class, 'index'])
        ->middleware('can:'.PermissionSlug::ViewOwnTickets->value)
        ->name('tickets.index');

    Route::middleware('can:'.PermissionSlug::CreateTickets->value)->group(function () {
        Route::get('tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
    });

    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/messages', [TicketMessageController::class, 'store'])->name('tickets.messages.store');
    Route::post('tickets/{ticket}/resolution', [TicketResolutionController::class, 'store'])->name('tickets.resolution.store');
    Route::delete('tickets/{ticket}/resolution', [TicketResolutionController::class, 'destroy'])->name('tickets.resolution.destroy');
    Route::post('tickets/{ticket}/rating', [TicketRatingController::class, 'store'])->name('tickets.rating.store');
});
