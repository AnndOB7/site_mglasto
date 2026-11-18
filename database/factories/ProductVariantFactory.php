<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $thickness = fake()->randomElement(['2 mm', '3 mm', '4 mm', '5 mm']);

        return [
            'product_id' => Product::factory(),
            'name' => "Preto / {$thickness}",
            'sku' => fake()->unique()->numerify('MGL-V-######'),
            'options' => ['Cor' => 'Preto', 'Espessura' => $thickness],
            'price' => null,
            'stock_quantity' => fake()->numberBetween(0, 50),
            'is_active' => true,
        ];
    }
}
