import { Head, Link } from '@inertiajs/react';
import { ListingPage } from '@/components/store/listing-page';
import type { Paginated, ProductSummary, SortOption } from '@/types/store';

type CategoryProps = {
    category: { name: string; description: string | null; url: string };
    subcategories: { name: string; url: string }[];
    products: Paginated<ProductSummary>;
    sort: string;
    sortOptions: SortOption[];
};

export default function Category({
    category,
    subcategories,
    products,
    sort,
    sortOptions,
}: CategoryProps) {
    return (
        <>
            <Head title={category.name}>
                <meta
                    name="description"
                    content={
                        category.description ??
                        `${category.name} na loja MGLASTO. Monte seu orçamento e envie pelo WhatsApp.`
                    }
                />
            </Head>
            <ListingPage
                breadcrumbs={[
                    { label: 'Início', url: '/' },
                    { label: category.name },
                ]}
                title={category.name}
                intro={
                    <>
                        {category.description && (
                            <p className="mt-2 max-w-3xl text-graphite-soft">
                                {category.description}
                            </p>
                        )}
                        {subcategories.length > 0 && (
                            <ul className="mt-4 flex flex-wrap gap-2">
                                {subcategories.map((sub) => (
                                    <li key={sub.url}>
                                        <Link
                                            href={sub.url}
                                            className="inline-flex min-h-touch items-center rounded-full border border-line px-4 text-sm font-medium hover:border-graphite"
                                        >
                                            {sub.name}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </>
                }
                products={products}
                sort={sort}
                sortOptions={sortOptions}
                empty={<p>Ainda não há produtos nesta categoria.</p>}
            />
        </>
    );
}
