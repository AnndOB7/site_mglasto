import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { Container } from '@/components/store/container';
import {
    EmptyState,
    Pagination,
    ProductGrid,
    SortSelect,
} from '@/components/store/product-grid';
import type { Paginated, ProductSummary, SortOption } from '@/types/store';

type Crumb = { label: string; url?: string };

/**
 * Shared structure of the category and search pages: breadcrumb, title,
 * result count, sort, product grid and pagination.
 */
export function ListingPage({
    breadcrumbs,
    title,
    intro,
    products,
    sort,
    sortOptions,
    empty,
}: {
    breadcrumbs: Crumb[];
    title: string;
    intro?: ReactNode;
    products: Paginated<ProductSummary>;
    sort: string;
    sortOptions: SortOption[];
    empty: ReactNode;
}) {
    return (
        <Container className="py-6 sm:py-8">
            <nav aria-label="Você está em" className="mb-4 text-sm">
                <ol className="flex flex-wrap items-center gap-1 text-graphite-soft">
                    {breadcrumbs.map((crumb, index) => (
                        <li key={index} className="flex items-center gap-1">
                            {index > 0 && (
                                <ChevronRight className="size-4" aria-hidden />
                            )}
                            {crumb.url ? (
                                <Link
                                    href={crumb.url}
                                    className="hover:text-brand-ink hover:underline"
                                >
                                    {crumb.label}
                                </Link>
                            ) : (
                                <span aria-current="page">{crumb.label}</span>
                            )}
                        </li>
                    ))}
                </ol>
            </nav>

            <h1 className="text-2xl font-extrabold sm:text-3xl">{title}</h1>
            {intro}

            <div className="my-5 flex flex-wrap items-center justify-between gap-3 border-y border-line py-3">
                <p className="text-sm text-graphite-soft" role="status">
                    {products.total}{' '}
                    {products.total === 1
                        ? 'produto encontrado'
                        : 'produtos encontrados'}
                </p>
                {products.total > 1 && (
                    <SortSelect value={sort} options={sortOptions} />
                )}
            </div>

            {products.data.length > 0 ? (
                <>
                    <ProductGrid products={products.data} />
                    <Pagination page={products} />
                </>
            ) : (
                <EmptyState>{empty}</EmptyState>
            )}
        </Container>
    );
}
