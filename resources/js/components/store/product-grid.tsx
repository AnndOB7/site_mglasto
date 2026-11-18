import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, PackageSearch } from 'lucide-react';
import { useId } from 'react';
import type { ReactNode } from 'react';
import { ProductCard } from '@/components/store/product-card';
import { cn } from '@/lib/utils';
import type { Paginated, ProductSummary, SortOption } from '@/types/store';

/** Two columns on phones, up to four on large screens. */
export function ProductGrid({
    products,
    className,
}: {
    products: ProductSummary[];
    className?: string;
}) {
    return (
        <ul
            className={cn(
                'grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4',
                className,
            )}
        >
            {products.map((product) => (
                <li key={product.id} className="flex">
                    <ProductCard product={product} className="w-full" />
                </li>
            ))}
        </ul>
    );
}

export function EmptyState({ children }: { children: ReactNode }) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-card bg-surface px-6 py-12 text-center text-graphite-soft">
            <PackageSearch className="size-10" aria-hidden />
            {children}
        </div>
    );
}

/**
 * Sort dropdown. Changing it reloads the list keeping the other filters
 * (e.g. the search term) and going back to page 1.
 */
export function SortSelect({
    value,
    options,
}: {
    value: string;
    options: SortOption[];
}) {
    const id = useId();

    const change = (ordem: string) => {
        const params = new URLSearchParams(window.location.search);
        params.set('ordem', ordem);
        params.delete('page');

        router.get(
            `${window.location.pathname}?${params.toString()}`,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <div className="flex items-center gap-2">
            <label
                htmlFor={id}
                className="text-sm whitespace-nowrap text-graphite-soft"
            >
                Ordenar por
            </label>
            <select
                id={id}
                value={value}
                onChange={(event) => change(event.target.value)}
                className="h-touch rounded-control border border-line bg-white px-3 text-sm font-medium focus:border-brand focus:outline-none"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </div>
    );
}

export function Pagination({ page }: { page: Paginated<unknown> }) {
    if (page.last_page <= 1) {
        return null;
    }

    const linkClass =
        'inline-flex min-h-touch items-center gap-1 rounded-control border border-line px-4 text-sm font-semibold hover:border-graphite';

    return (
        <nav
            aria-label="Páginas"
            className="mt-8 flex items-center justify-between gap-4"
        >
            {page.prev_page_url ? (
                <Link
                    href={page.prev_page_url}
                    className={linkClass}
                    preserveScroll={false}
                >
                    <ChevronLeft className="size-4" aria-hidden /> Anterior
                </Link>
            ) : (
                <span />
            )}
            <span className="text-sm text-graphite-soft">
                Página {page.current_page} de {page.last_page}
            </span>
            {page.next_page_url ? (
                <Link href={page.next_page_url} className={linkClass}>
                    Próxima <ChevronRight className="size-4" aria-hidden />
                </Link>
            ) : (
                <span />
            )}
        </nav>
    );
}
