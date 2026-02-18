<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Admin',
                'email' => 'admin@queue.local',
                'password' => bcrypt('admin12345'),
                'role' => 'admin',
                'counter_id' => null,
            ]
        );
    }
}
