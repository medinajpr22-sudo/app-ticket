<?php

namespace Database\Seeders;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ServiceLevelSeeder::class,
            MessageTemplateSeeder::class,
        ]);

        if (! app()->isLocal()) {
            return;
        }

        User::factory()->superAdmin()->create([
            'name' => 'Dirección TIC',
            'email' => 'superadmin@example.com',
            'job_title' => 'Director de TI',
        ]);

        $this->call(DemoSeeder::class);
    }
}
