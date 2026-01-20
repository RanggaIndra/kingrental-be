<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => $this->faker->vehicle ?? 'Toyota Avanza ' . $this->faker->year,
            'type' => $this->faker->randomElement(['MPV', 'SUV', 'Sedan', 'Hatchback']),
            'license_plate' => $this->faker->unique()->bothify('? #### ??'), 
            'transmission' => $this->faker->randomElement(['Manual', 'Automatic']),
            'capacity' => $this->faker->numberBetween(4, 7),
            'price_per_day' => $this->faker->numberBetween(250000, 1500000),
            'is_available' => true,
            'image_url' => 'https://placehold.co/600x400',
            'description' => $this->faker->paragraph(),
        ];
    }
}