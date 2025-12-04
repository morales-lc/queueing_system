<?php

namespace Database\Factories;

use App\Models\Counter;
use Illuminate\Database\Eloquent\Factories\Factory;

class CounterFactory extends Factory
{
    protected $model = Counter::class;

    public function definition(): array
    {
        return [
            'name' => (string)$this->faker->numberBetween(1, 4),
            'type' => $this->faker->randomElement(['cashier', 'registrar']),
            'claimed' => false,
        ];
    }
}
