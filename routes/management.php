<?php

use App\Domain\Identity\Enums\PermissionSlug;
use App\Http\Controllers\Management\TeamController;
use App\Http\Controllers\Management\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Management area
|--------------------------------------------------------------------------
|
| Platform configuration. Each resource is guarded by its own permission,
| so access can later be delegated without moving routes around.
|
*/

Route::middleware('auth')->prefix('management')->name('management.')->group(function () {
    Route::resource('users', UserController::class)
        ->except(['show', 'destroy'])
        ->middleware('can:'.PermissionSlug::ManageUsers->value);

    Route::resource('teams', TeamController::class)
        ->except(['show', 'destroy'])
        ->middleware('can:'.PermissionSlug::ManageTeams->value);
});
