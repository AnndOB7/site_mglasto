<?php

namespace App\Models;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Amounts are integers in cents.
 *
 * @property int $id
 * @property string $number
 * @property int|null $user_id
 * @property OrderChannel $channel
 * @property string|null $external_id
 * @property OrderStatus $status
 * @property string $customer_name
 * @property string|null $customer_email
 * @property string|null $customer_phone
 * @property string|null $customer_document
 * @property string|null $shipping_zip
 * @property string|null $shipping_street
 * @property string|null $shipping_number
 * @property string|null $shipping_complement
 * @property string|null $shipping_district
 * @property string|null $shipping_city
 * @property string|null $shipping_state
 * @property int $subtotal
 * @property int $shipping_cost
 * @property int $discount
 * @property int $total
 * @property string|null $notes
 * @property Carbon $placed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, OrderItem> $items
 */
#[Fillable([
    'number', 'user_id', 'channel', 'external_id', 'status',
    'customer_name', 'customer_email', 'customer_phone', 'customer_document',
    'shipping_zip', 'shipping_street', 'shipping_number', 'shipping_complement',
    'shipping_district', 'shipping_city', 'shipping_state',
    'subtotal', 'shipping_cost', 'discount', 'total', 'notes', 'placed_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => OrderChannel::class,
            'status' => OrderStatus::class,
            'placed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
