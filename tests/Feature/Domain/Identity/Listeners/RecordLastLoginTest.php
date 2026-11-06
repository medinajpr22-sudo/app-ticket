<?php

namespace Tests\Feature\Domain\Identity\Listeners;

use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordLastLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_signing_in_records_the_last_login_time(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create(['last_login_at' => null]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertEquals(now(), $user->refresh()->last_login_at);
    }
}
