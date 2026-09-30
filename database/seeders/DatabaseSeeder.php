<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@fixnest.test',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole($adminRole);
    }
}
