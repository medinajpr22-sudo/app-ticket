<?php

namespace Database\Factories;

use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => fn (): int => $this->roleId(RoleSlug::User),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'job_title' => fake()->jobTitle(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the user configures the whole platform.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => $this->roleId(RoleSlug::SuperAdmin),
        ]);
    }

    /**
     * Indicate that the user is IT staff who attends tickets.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => $this->roleId(RoleSlug::Admin),
        ]);
    }

    /**
     * Indicate that the user's account is disabled.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Roles are reference data with unique slugs, so reuse the row when it already exists.
     */
    private function roleId(RoleSlug $slug): int
    {
        return Role::query()->firstOrCreate(['slug' => $slug])->getKey();
    }
}
