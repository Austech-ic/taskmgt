<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /** Used for local development (php artisan migrate --seed). The web installer runs the same seeders. */
    public function run(): void
    {
        $this->call([PlatformSeeder::class, DemoSeeder::class]);

        $admin = User::firstOrNew(['email' => 'admin@devhail.test']);
        $admin->forceFill(['name' => 'Devhail Admin', 'password' => Hash::make('password'), 'staff_role' => 'super_admin', 'is_hirer' => true, 'email_verified_at' => now(), 'terms_accepted_at' => now()])->save();
    }
}
