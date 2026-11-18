<?php

namespace Database\Factories;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(5_000, 100_000);

        return [
            'number' => fake()->unique()->numerify('MGL-######'),
            'channel' => OrderChannel::Site,
            'status' => OrderStatus::Quote,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('119########'),
            'subtotal' => $subtotal,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => $subtotal,
            'placed_at' => now(),
        ];
    }

    /**
     * Indicate that the order came from a marketplace.
     */
    public function fromChannel(OrderChannel $channel): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => $channel,
            'external_id' => fake()->unique()->numerify('##########'),
        ]);
    }
}
