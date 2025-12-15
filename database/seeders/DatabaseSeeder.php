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
        /**
         * Seed the application's database. */
        // Seed counters first
        foreach (range(1, 4) as $i) {
            Counter::firstOrCreate(['name' => (string)$i, 'type' => 'cashier'], ['claimed' => false]);
        }
        foreach (range(1, 4) as $i) {
            Counter::firstOrCreate(['name' => (string)$i, 'type' => 'registrar'], ['claimed' => false]);
        }

        // Create users linked to specific counters
        foreach (range(1, 4) as $i) {
            $counter = Counter::where('type', 'cashier')->where('name', (string)$i)->first();
            User::firstOrCreate(
                ['email' => "cashier{$i}"],
                [
                    'name' => "Cashier Window {$i}",
                    'password' => bcrypt('password123'),
                    'role' => 'cashier',
                    'counter_id' => $counter->id
                ]
            );
        }

        foreach (range(1, 4) as $i) {
            $counter = Counter::where('type', 'registrar')->where('name', (string)$i)->first();
            User::firstOrCreate(
                ['email' => "registrar{$i}@queue.local"],
                [
                    'name' => "Registrar Window {$i}",
                    'password' => bcrypt('password'),
                    'role' => 'registrar',
                    'counter_id' => $counter->id
                ]
            );
        }
    }
}
