<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the portfolio admin user.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'lawrenceobi2@gmail.com'],
            [
                'name' => 'Lorenzo Chukwuebuka Obi',
                'email_verified_at' => now(),
                'password' => Hash::make('changeMe123#!'),
            ],
        );
    }
}
