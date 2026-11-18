import { Link } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';
import { Badge } from '@/components/store/badge';
import { Price } from '@/components/store/price';
import { cn } from '@/lib/utils';
import type { ProductSummary } from '@/types/store';

/**
 * Product in a grid: square photo on a light background, name in up to two
 * lines, price and an "Oferta" badge when there is a sale price.
 *
 * The whole card is one link, so it is an easy touch target on phones.
 */
export function ProductCard({
    product,
    className,
}: {
    product: ProductSummary;
    className?: string;
}) {
    const onSale =
        product.sale_price !== null && product.sale_price < product.price;

    return (
        <Link
            href={product.url}
            className={cn(
                'group flex flex-col overflow-hidden rounded-card border border-line bg-white shadow-card transition-shadow hover:shadow-card-hover focus-visible:ring-3 focus-visible:ring-brand/50 focus-visible:outline-none',
                className,
            )}
        >
            <div className="relative aspect-square bg-surface">
                {product.image ? (
                    <img
                        src={product.image.url}
                        alt={product.image.alt}
                        loading="lazy"
                        className="size-full object-contain p-3 mix-blend-multiply transition-transform duration-300 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex size-full items-center justify-center text-graphite-soft">
                        <ImageOff className="size-8" aria-hidden />
                        <span className="sr-only">Produto sem foto</span>
                    </div>
                )}
                <div className="absolute top-2 left-2 flex flex-col gap-1">
                    {onSale && <Badge variant="offer">Oferta</Badge>}
                    {!product.in_stock && (
                        <Badge variant="neutral">Esgotado</Badge>
                    )}
                </div>
            </div>
            <div className="flex flex-1 flex-col gap-2 p-3 sm:p-4">
                <h3 className="line-clamp-2 min-h-10 text-sm leading-5 font-medium text-graphite">
                    {product.name}
                </h3>
                <Price
                    price={product.price}
                    salePrice={product.sale_price}
                    className="mt-auto"
                />
                <span className="text-sm font-semibold text-brand-ink group-hover:underline">
                    Ver produto
                </span>
            </div>
        </Link>
    );
}
