import { Head } from '@inertiajs/react';
import { Container } from '@/components/store/container';
import { Price } from '@/components/store/price';
import type { ProductSummary } from '@/types/store';

/** Placeholder: the full product page comes in the next task. */
export default function Product({ product }: { product: ProductSummary }) {
    return (
        <>
            <Head title={product.name} />
            <Container className="py-8">
                <h1 className="text-2xl font-extrabold">{product.name}</h1>
                <Price price={product.price} salePrice={product.sale_price} />
            </Container>
        </>
    );
}
