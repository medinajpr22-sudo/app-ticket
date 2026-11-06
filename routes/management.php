<?php

use App\Domain\Identity\Enums\PermissionSlug;
use App\Http\Controllers\Management\BusinessCalendarController;
use App\Http\Controllers\Management\CategoryController;
use App\Http\Controllers\Management\ServiceLevelController;
use App\Http\Controllers\Management\SlaPolicyController;
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

    Route::resource('categories', CategoryController::class)
        ->except(['show', 'destroy'])
        ->middleware('can:'.PermissionSlug::ManageCategories->value);

    Route::middleware('can:'.PermissionSlug::ManageSlaPolicies->value)
        ->prefix('service-levels')
        ->name('service-levels.')
        ->group(function () {
            Route::get('/', [ServiceLevelController::class, 'index'])->name('index');

            Route::resource('policies', SlaPolicyController::class)
                ->except(['index', 'show', 'destroy'])
                ->parameters(['policies' => 'slaPolicy']);

            Route::resource('calendars', BusinessCalendarController::class)
                ->except(['index', 'show', 'destroy'])
                ->parameters(['calendars' => 'businessCalendar']);
        });
});
