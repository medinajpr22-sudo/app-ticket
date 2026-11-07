<?php

use App\Domain\Identity\Enums\PermissionSlug;
use App\Http\Controllers\Desk\TakeTicketController;
use App\Http\Controllers\Desk\TeamController;
use App\Http\Controllers\Desk\TeamMemberController;
use App\Http\Controllers\Desk\TicketAssignmentController;
use App\Http\Controllers\Desk\TicketController;
use App\Http\Controllers\Desk\TicketHistoryController;
use App\Http\Controllers\Desk\TicketMessageController;
use App\Http\Controllers\Desk\TicketPriorityController;
use App\Http\Controllers\Desk\TicketStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Desk area
|--------------------------------------------------------------------------
|
| Where IT staff attend the tickets of their teams. Every action on a
| ticket is also checked against the TicketPolicy.
|
*/

Route::middleware('auth')->prefix('desk')->name('desk.')->group(function () {
    Route::get('tickets', [TicketController::class, 'index'])
        ->middleware('can:'.PermissionSlug::ViewTeamTickets->value)
        ->name('tickets.index');

    Route::get('history', TicketHistoryController::class)
        ->middleware('can:'.PermissionSlug::ViewTeamTickets->value)
        ->name('history.index');

    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/take', TakeTicketController::class)->name('tickets.take');
    Route::post('tickets/{ticket}/messages', [TicketMessageController::class, 'store'])->name('tickets.messages.store');
    Route::put('tickets/{ticket}/status', [TicketStatusController::class, 'update'])->name('tickets.status.update');
    Route::put('tickets/{ticket}/priority', [TicketPriorityController::class, 'update'])->name('tickets.priority.update');
    Route::put('tickets/{ticket}/assignment', [TicketAssignmentController::class, 'update'])->name('tickets.assignment.update');
    Route::post('tickets/{ticket}/transfer', [TicketAssignmentController::class, 'transfer'])->name('tickets.transfer');

    Route::get('teams', [TeamController::class, 'index'])
        ->middleware('can:'.PermissionSlug::LeadTeams->value)
        ->name('teams.index');
    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::get('teams/{team}/members/create', [TeamMemberController::class, 'create'])->name('teams.members.create');
    Route::post('teams/{team}/members', [TeamMemberController::class, 'store'])->name('teams.members.store');
});
