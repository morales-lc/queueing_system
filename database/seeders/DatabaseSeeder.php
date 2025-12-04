<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Counter;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;


    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);


        /**
         * Seed the application's database. */
        // Seed counters Cashier 1-4 and Registrar 1-4
        foreach (range(1, 4) as $i) {
            Counter::firstOrCreate(['name' => (string)$i, 'type' => 'cashier'], ['claimed' => false]);
        }
        foreach (range(1, 4) as $i) {
            Counter::firstOrCreate(['name' => (string)$i, 'type' => 'registrar'], ['claimed' => false]);
        }
        foreach (range(1, 4) as $i) {
            Counter::firstOrCreate(['name' => (string)$i, 'type' => 'registrar'], ['claimed' => false]);
        }
    }
}
