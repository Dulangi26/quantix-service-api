<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_number' => 'FD-'.Str::upper(Str::random(8)),
            'employee_id' => User::factory(),
            'assigned_to' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement([
                'low',
                'medium',
                'high',
            ]),
            'status' => RequestStatus::DRAFT,
            'submitted_at' => null,
            'approved_at' => null,
            'completed_at' => null,
        ];
    }
}
