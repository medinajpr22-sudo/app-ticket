<?php

namespace App\Domain\Identity\Listeners;

use App\Domain\Identity\Models\User;
use Illuminate\Auth\Events\Login;

class RecordLastLogin
{
    /**
     * Store when the user last signed in, whatever the sign-in method was.
     */
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
    }
}
