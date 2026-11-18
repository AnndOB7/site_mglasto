<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Quote = 'quote';
    case Confirmed = 'confirmed';
    case Picking = 'picking';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Orçamento',
            self::Confirmed => 'Confirmado',
            self::Picking => 'Em separação',
            self::Shipped => 'Enviado',
            self::Delivered => 'Entregue',
            self::Cancelled => 'Cancelado',
        };
    }
}
