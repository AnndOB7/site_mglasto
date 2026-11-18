<?php

namespace App\Enums;

enum OrderChannel: string
{
    case Site = 'site';
    case MercadoLivre = 'mercado_livre';
    case Shopee = 'shopee';
    case TikTok = 'tiktok';
    case WhatsApp = 'whatsapp';

    /**
     * Get the display label for the channel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Site => 'Site',
            self::MercadoLivre => 'Mercado Livre',
            self::Shopee => 'Shopee',
            self::TikTok => 'TikTok',
            self::WhatsApp => 'WhatsApp',
        };
    }
}
