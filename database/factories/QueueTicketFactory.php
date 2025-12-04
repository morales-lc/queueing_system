<?php

namespace Database\Factories;

use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueTicketFactory extends Factory
{
    protected $model = QueueTicket::class;

    public function definition(): array
    {
        $service = $this->faker->randomElement(['cashier', 'registrar']);
        $priority = $this->faker->randomElement(['pwd_senior_pregnant', 'student', 'parent']);
        $prefix = strtoupper(substr($service, 0, 1)) . strtoupper(substr($priority, 0, 1));
        
        return [
            'code' => $prefix . '-' . str_pad((string)$this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'service_type' => $service,
            'priority' => $priority,
            'status' => 'pending',
            'counter_id' => null,
            'hold_count' => 0,
            'called_times' => 0,
        ];
    }
}
