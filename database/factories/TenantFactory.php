<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_name' => $this->faker->company(),
            
            'description' => $this->faker->sentence(),
            'phone' => $this->faker->phoneNumber(),
            'is_open' => true,
            'tenant_img' => null,
        ];
    }
}
