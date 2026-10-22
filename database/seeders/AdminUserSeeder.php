<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the portfolio admin user.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (app()->environment('production') && blank($password)) {
            throw new RuntimeException('ADMIN_PASSWORD must be set before seeding an administrator in production.');
        }

        if (blank($email) || blank($password)) {
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Lorenzo Chukwuebuka Obi',
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );
    }
}
