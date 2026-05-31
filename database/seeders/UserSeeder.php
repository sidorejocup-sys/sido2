<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table with a default super admin.
     */
    public function run(): void
    {
        User::create([
            'username' => 'zolvirm',
            'name' => 'zolvirm',
            'email' => 'zolvirm@test.com',
            'password' => Hash::make('zolvirm123'),
            'role' => 'super_admin',
        ]);
    }
}
