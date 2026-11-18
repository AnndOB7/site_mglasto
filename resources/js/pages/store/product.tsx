import { Head, Link, useForm } from '@inertiajs/react';
import {
    ChevronRight,
    MessageCircle,
    Minus,
    Plus,
    ShoppingCart,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/store/badge';
import { Button, ButtonLink } from '@/components/store/button';
import { Container } from '@/components/store/container';
import { Price } from '@/components/store/price';
import { ProductGrid } from '@/components/store/product-grid';
import { cn } from '@/lib/utils';
import type { Cents, ProductImage, ProductSummary } from '@/types/store';

type Variant = {
    id: number;
    name: string;
    options: Record<string, string>;
    price: Cents | null;
    in_stock: boolean;
};

type OptionGroup = { name: string; values: string[] };

type ProductDetail = {
    id: number;
    name: string;
    sku: string | null;
    short_description: string | null;
    description_html: string | null;
    price: Cents;
    sale_price: Cents | null;
    in_stock: boolean;
    max_quantity: number | null;
    images: ProductImage[];
    variants: Variant[];
    option_groups: OptionGroup[];
};

type ProductPageProps = {
    product: ProductDetail;
    category: { name: string; url: string } | null;
    related: ProductSummary[];
    whatsappUrl: string | null;
    addToCartUrl: string | null;
};

const MAX_QUANTITY = 999;

export default function ProductPage({
    product,
    category,
    related,
    whatsappUrl,
    addToCartUrl,
}: ProductPageProps) {
    const hasVariants = product.variants.length > 0;
    // Options with a single value (e.g. "Espessura: 3 mm") start already chosen.
    const [selected, setSelected] = useState<Record<string, string>>(() =>
        Object.fromEntries(
            product.option_groups
                .filter((group) => group.values.length === 1)
                .map((group) => [group.name, group.values[0]]),
        ),
    );

    // The variant that matches every chosen option (null until all are chosen).
    const variant = useMemo(
        () =>
            product.variants.find((candidate) =>
                product.option_groups.every(
                    (group) =>
                        candidate.options[group.name] === selected[group.name],
                ),
            ) ?? null,
        [product, selected],
    );

    const inStock = variant ? variant.in_stock : product.in_stock;
    const needsChoice = hasVariants && variant === null;
    const maxQuantity = Math.min(
        product.max_quantity ?? MAX_QUANTITY,
        MAX_QUANTITY,
    );

    const form = useForm({
        product_id: product.id,
        variant_id: (variant?.id ?? null) as number | null,
        quantity: 1,
    });

    const choose = (group: string, value: string) => {
        const next = { ...selected, [group]: value };
        setSelected(next);

        const match = product.variants.find((candidate) =>
            product.option_groups.every(
                (g) => candidate.options[g.name] === next[g.name],
            ),
        );
        form.setData('variant_id', match?.id ?? null);
    };

    // A value is possible when some variant has it together with the other choices.
    const isPossible = (group: string, value: string) =>
        product.variants.some(
            (candidate) =>
                candidate.options[group] === value &&
                product.option_groups.every(
                    (g) =>
                        g.name === group ||
                        selected[g.name] === undefined ||
                        candidate.options[g.name] === selected[g.name],
                ),
        );

    const submit = () => {
        if (!addToCartUrl) {
            return;
        }

        form.post(addToCartUrl, { preserveScroll: true });
    };

    return (
        <>
            <Head title={product.name} />

            <Container className="py-6 sm:py-8">
                <nav aria-label="Você está em" className="mb-4 text-sm">
                    <ol className="flex flex-wrap items-center gap-1 text-graphite-soft">
                        <li>
                            <Link
                                href="/"
                                className="hover:text-brand-ink hover:underline"
                            >
                                Início
                            </Link>
                        </li>
                        {category && (
                            <li className="flex items-center gap-1">
                                <ChevronRight className="size-4" aria-hidden />
                                <Link
                                    href={category.url}
                                    className="hover:text-brand-ink hover:underline"
                                >
                                    {category.name}
                                </Link>
                            </li>
                        )}
                    </ol>
                </nav>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
                    <Gallery images={product.images} name={product.name} />

                    <div className="flex flex-col gap-5">
                        <div>
                            <h1 className="text-2xl leading-tight font-extrabold sm:text-3xl">
                                {product.name}
                            </h1>
                            {(variant?.name ?? product.sku) && (
                                <p className="mt-1 text-sm text-graphite-soft">
                                    {variant
                                        ? `Opção: ${variant.name}`
                                        : `Código: ${product.sku}`}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            {variant?.price != null ? (
                                <Price price={variant.price} size="lg" />
                            ) : (
                                <Price
                                    price={product.price}
                                    salePrice={product.sale_price}
                                    size="lg"
                                />
                            )}
                            {product.sale_price !== null &&
                                variant?.price == null && (
                                    <Badge variant="offer">Oferta</Badge>
                                )}
                        </div>

                        <p
                            className={cn(
                                'text-sm font-semibold',
                                inStock ? 'text-success' : 'text-danger',
                            )}
                            role="status"
                        >
                            {inStock
                                ? needsChoice
                                    ? 'Disponível. Escolha uma opção abaixo.'
                                    : 'Disponível'
                                : 'Indisponível no momento'}
                        </p>

                        {product.short_description && (
                            <p className="text-graphite-soft">
                                {product.short_description}
                            </p>
                        )}

                        {product.option_groups.map((group) => (
                            <fieldset key={group.name}>
                                <legend className="mb-2 text-sm font-bold">
                                    {group.name}
                                    {selected[group.name] && (
                                        <span className="font-normal text-graphite-soft">
                                            : {selected[group.name]}
                                        </span>
                                    )}
                                </legend>
                                <div className="flex flex-wrap gap-2">
                                    {group.values.map((value) => {
                                        const active =
                                            selected[group.name] === value;
                                        const possible = isPossible(
                                            group.name,
                                            value,
                                        );

                                        return (
                                            <button
                                                key={value}
                                                type="button"
                                                aria-pressed={active}
                                                disabled={!possible}
                                                onClick={() =>
                                                    choose(group.name, value)
                                                }
                                                className={cn(
                                                    'min-h-touch rounded-control border px-4 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40',
                                                    active
                                                        ? 'border-graphite bg-graphite text-white'
                                                        : 'border-line bg-white hover:border-graphite',
                                                )}
                                            >
                                                {value}
                                            </button>
                                        );
                                    })}
                                </div>
                            </fieldset>
                        ))}

                        <div className="flex flex-col gap-3 border-t border-line pt-5 sm:flex-row">
                            <QuantityStepper
                                value={form.data.quantity}
                                max={maxQuantity}
                                onChange={(quantity) =>
                                    form.setData('quantity', quantity)
                                }
                            />
                            <Button
                                size="lg"
                                className="flex-1"
                                onClick={submit}
                                disabled={
                                    !addToCartUrl ||
                                    !inStock ||
                                    needsChoice ||
                                    form.processing
                                }
                            >
                                <ShoppingCart />
                                {needsChoice
                                    ? 'Escolha uma opção'
                                    : 'Adicionar ao orçamento'}
                            </Button>
                        </div>
                        {Object.values(form.errors).map((error) => (
                            <p
                                key={error}
                                className="text-sm text-danger"
                                role="alert"
                            >
                                {error}
                            </p>
                        ))}

                        {whatsappUrl ? (
                            <ButtonLink
                                href={whatsappUrl}
                                external
                                variant="whatsapp"
                                size="lg"
                            >
                                <MessageCircle /> Tirar dúvidas no WhatsApp
                            </ButtonLink>
                        ) : (
                            <p className="rounded-control bg-brand-soft px-4 py-3 text-sm font-semibold text-brand-ink">
                                [A DEFINIR: número do WhatsApp]
                            </p>
                        )}
                    </div>
                </div>

                {product.description_html && (
                    <section className="mt-12 max-w-3xl">
                        <h2 className="mb-4 text-xl font-extrabold">
                            Descrição
                        </h2>
                        <div
                            className="text-graphite-soft [&_h2]:mt-6 [&_h2]:mb-2 [&_h2]:text-lg [&_h2]:font-bold [&_h2]:text-graphite [&_h3]:mt-5 [&_h3]:mb-2 [&_h3]:font-bold [&_h3]:text-graphite [&_h4]:mt-4 [&_h4]:font-bold [&_li]:mb-1 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mb-4 [&_strong]:text-graphite [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6"
                            // Safe: the server sanitizes this HTML (App\Support\HtmlSanitizer).
                            dangerouslySetInnerHTML={{
                                __html: product.description_html,
                            }}
                        />
                    </section>
                )}

                {related.length > 0 && (
                    <section className="mt-12">
                        <h2 className="mb-5 text-xl font-extrabold">
                            Produtos relacionados
                        </h2>
                        <ProductGrid products={related} />
                    </section>
                )}
            </Container>
        </>
    );
}

function Gallery({ images, name }: { images: ProductImage[]; name: string }) {
    const [current, setCurrent] = useState(0);
    const image = images[current];

    if (!image) {
        return (
            <div className="flex aspect-square items-center justify-center rounded-card bg-surface text-graphite-soft">
                Produto sem foto
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-3">
            <div className="aspect-square overflow-hidden rounded-card bg-surface">
                <img
                    src={image.url}
                    alt={image.alt}
                    className="size-full object-contain p-4 mix-blend-multiply"
                />
            </div>
            {images.length > 1 && (
                <ul
                    className="flex gap-2 overflow-x-auto pb-1"
                    aria-label={`Fotos de ${name}`}
                >
                    {images.map((thumb, index) => (
                        <li key={thumb.url} className="shrink-0">
                            <button
                                type="button"
                                onClick={() => setCurrent(index)}
                                aria-label={`Ver foto ${index + 1} de ${images.length}`}
                                aria-current={index === current}
                                className={cn(
                                    'size-16 overflow-hidden rounded-control border-2 bg-surface sm:size-20',
                                    index === current
                                        ? 'border-brand'
                                        : 'border-transparent hover:border-line',
                                )}
                            >
                                <img
                                    src={thumb.url}
                                    alt=""
                                    loading="lazy"
                                    className="size-full object-contain p-1 mix-blend-multiply"
                                />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function QuantityStepper({
    value,
    max,
    onChange,
}: {
    value: number;
    max: number;
    onChange: (value: number) => void;
}) {
    const clamp = (next: number) =>
        Math.max(
            1,
            Math.min(max, Number.isFinite(next) ? Math.trunc(next) : 1),
        );

    return (
        <div className="flex h-12 items-stretch overflow-hidden rounded-control border border-line">
            <button
                type="button"
                onClick={() => onChange(clamp(value - 1))}
                disabled={value <= 1}
                className="flex w-12 items-center justify-center hover:bg-surface disabled:opacity-40"
                aria-label="Diminuir quantidade"
            >
                <Minus className="size-4" />
            </button>
            <label className="sr-only" htmlFor="quantidade">
                Quantidade
            </label>
            <input
                id="quantidade"
                type="number"
                inputMode="numeric"
                min={1}
                max={max}
                value={value}
                onChange={(event) =>
                    onChange(clamp(Number(event.target.value)))
                }
                className="w-16 [appearance:textfield] border-x border-line text-center text-base font-semibold focus:outline-none [&::-webkit-inner-spin-button]:appearance-none"
            />
            <button
                type="button"
                onClick={() => onChange(clamp(value + 1))}
                disabled={value >= max}
                className="flex w-12 items-center justify-center hover:bg-surface disabled:opacity-40"
                aria-label="Aumentar quantidade"
            >
                <Plus className="size-4" />
            </button>
        </div>
    );
}
