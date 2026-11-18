import { Head } from '@inertiajs/react';
import { ListingPage } from '@/components/store/listing-page';
import type { Paginated, ProductSummary, SortOption } from '@/types/store';

type SearchProps = {
    term: string;
    products: Paginated<ProductSummary>;
    sort: string;
    sortOptions: SortOption[];
};

export default function Search({
    term,
    products,
    sort,
    sortOptions,
}: SearchProps) {
    const title = term ? `Busca: “${term}”` : 'Todos os produtos';

    return (
        <>
            <Head title={title}>
                <meta name="robots" content={term ? 'noindex' : 'index'} />
            </Head>
            <ListingPage
                breadcrumbs={[{ label: 'Início', url: '/' }, { label: title }]}
                title={title}
                products={products}
                sort={sort}
                sortOptions={sortOptions}
                empty={
                    <>
                        <p className="font-semibold text-graphite">
                            Nenhum produto encontrado para “{term}”.
                        </p>
                        <p className="text-sm">
                            Confira a ortografia, tente outra palavra ou busque
                            pelo código do produto.
                        </p>
                    </>
                }
            />
        </>
    );
}
