<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'category_id' => Categorie::inRandomOrder()->first()->id,
            'name' => ucfirst($name),
            'slug' => Str::slug($name . '-' . uniqid()), // biar unik
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(5000, 50000),
            'is_available' => true,
            'product_img' => null,
        ];
    }
}
