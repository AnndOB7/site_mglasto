import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';

type PriceProps = {
    /** Regular price in cents. */
    price: number;
    /** Sale price in cents, when the product is on sale. */
    salePrice?: number | null;
    size?: 'md' | 'lg';
    className?: string;
};

/**
 * Shows the price the customer pays. When there is a sale price, the
 * regular price appears struck through next to it.
 */
export function Price({
    price,
    salePrice,
    size = 'md',
    className,
}: PriceProps) {
    const onSale = salePrice != null && salePrice < price;
    const current = onSale ? salePrice : price;

    return (
        <p className={cn('flex flex-wrap items-baseline gap-x-2', className)}>
            {onSale && (
                <s
                    className={cn(
                        'text-graphite-soft',
                        size === 'lg' ? 'text-base' : 'text-sm',
                    )}
                >
                    <span className="sr-only">De </span>
                    {formatCents(price)}
                </s>
            )}
            <span
                className={cn(
                    'font-extrabold text-graphite',
                    size === 'lg' ? 'text-3xl' : 'text-lg',
                )}
            >
                {onSale && <span className="sr-only">por </span>}
                {formatCents(current)}
            </span>
        </p>
    );
}
