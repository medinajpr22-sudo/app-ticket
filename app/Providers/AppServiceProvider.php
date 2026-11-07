<?php

namespace App\Providers;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Listeners\RecordLastLogin;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Events\TicketCreated;
use App\Domain\Ticketing\Events\TicketMessagePosted;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use App\Domain\Ticketing\Events\TicketTaken;
use App\Domain\Ticketing\Events\TicketTransferred;
use App\Domain\Ticketing\Listeners\NotifyAboutNewMessage;
use App\Domain\Ticketing\Listeners\NotifyRequesterOfStatusChange;
use App\Domain\Ticketing\Listeners\NotifyRequesterOfTransfer;
use App\Domain\Ticketing\Listeners\SendAcknowledgementMessage;
use App\Domain\Ticketing\Listeners\SendGreetingMessage;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        JsonResource::withoutWrapping();

        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(12)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        $this->configureAuthorization();

        Event::listen(Login::class, RecordLastLogin::class);
        Event::listen(TicketCreated::class, SendAcknowledgementMessage::class);
        Event::listen(TicketTaken::class, SendGreetingMessage::class);
        Event::listen(TicketMessagePosted::class, NotifyAboutNewMessage::class);
        Event::listen(TicketStatusChanged::class, NotifyRequesterOfStatusChange::class);
        Event::listen(TicketTransferred::class, NotifyRequesterOfTransfer::class);
    }

    /**
     * Every permission slug becomes a gate, so routes and policies can use
     * `can:users.manage`. Super admins are always allowed.
     */
    private function configureAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        foreach (PermissionSlug::cases() as $permission) {
            Gate::define($permission->value, fn (User $user): bool => $user->hasPermission($permission));
        }
    }
}
