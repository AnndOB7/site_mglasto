<?php

namespace Database\Factories;

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
        $name = 'Lençol de Borracha '.fake()->unique()->numerify('##.##').'x1000mm';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => fake()->unique()->numerify('MGL-######'),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->numberBetween(1_000, 60_000),
            'manage_stock' => true,
            'stock_quantity' => fake()->numberBetween(1, 100),
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_price' => (int) round($attributes['price'] * 0.9),
        ]);
    }

    /**
     * Indicate that the product is highlighted on the home page.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product has no stock left.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }
}
