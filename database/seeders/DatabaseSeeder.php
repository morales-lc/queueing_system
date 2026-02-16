<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MonitorSetting;
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
                ['username' => "cashier{$i}"],
                [
                    'name' => "Cashier Window {$i}",
                    'email' => "cashier{$i}@queue.local",
                    'password' => bcrypt('password'),
                    'role' => 'cashier',
                    'counter_id' => $counter->id
                ]
            );
        }

        foreach (range(1, 4) as $i) {
            $counter = Counter::where('type', 'registrar')->where('name', (string)$i)->first();
            User::firstOrCreate(
                ['username' => "registrar{$i}"],
                [
                    'name' => "Registrar Window {$i}",
                    'email' => "registrar{$i}@queue.local",
                    'password' => bcrypt('password'),
                    'role' => 'registrar',
                    'counter_id' => $counter->id
                ]
            );
        }

        // Default monitor marquee text
        MonitorSetting::firstOrCreate(
            ['id' => 1],
            ['marquee_text' => 'Welcome to Our Service Center! Please wait for your number to be called. Thank you for your patience and cooperation.']
        );
    }
}
