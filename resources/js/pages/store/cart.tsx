import { Head, Link, router } from '@inertiajs/react';
import { ImageOff, Minus, Plus, ShoppingCart, Trash2 } from 'lucide-react';
import { ButtonLink } from '@/components/store/button';
import { Container } from '@/components/store/container';
import { formatCents } from '@/lib/money';
import type { CartLine, Cents } from '@/types/store';

type CartProps = {
    lines: CartLine[];
    subtotal: Cents;
    canCheckout: boolean;
    checkoutUrl: string;
};

export default function Cart({
    lines,
    subtotal,
    canCheckout,
    checkoutUrl,
}: CartProps) {
    return (
        <>
            <Head title="Seu orçamento" />
            <Container className="py-6 sm:py-8">
                <h1 className="mb-6 text-2xl font-extrabold sm:text-3xl">
                    Seu orçamento
                </h1>

                {lines.length === 0 ? (
                    <div className="flex flex-col items-center gap-4 rounded-card bg-surface px-6 py-12 text-center">
                        <ShoppingCart
                            className="size-10 text-graphite-soft"
                            aria-hidden
                        />
                        <p className="font-semibold">
                            Seu orçamento está vazio.
                        </p>
                        <ButtonLink href="/">Ver produtos</ButtonLink>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_20rem]">
                        <ul className="divide-y divide-line border-y border-line">
                            {lines.map((line) => (
                                <CartItem key={line.key} line={line} />
                            ))}
                        </ul>

                        <aside className="flex h-fit flex-col gap-4 rounded-card bg-surface p-5 lg:sticky lg:top-36">
                            <div className="flex items-baseline justify-between">
                                <span className="font-semibold">
                                    Total dos produtos
                                </span>
                                <span className="text-2xl font-extrabold">
                                    {formatCents(subtotal)}
                                </span>
                            </div>
                            <p className="text-sm text-graphite-soft">
                                Frete e prazo são confirmados pela equipe da
                                MGLASTO no WhatsApp.
                            </p>
                            {!canCheckout && (
                                <p
                                    className="text-sm font-semibold text-danger"
                                    role="alert"
                                >
                                    Ajuste os itens sem estoque suficiente para
                                    continuar.
                                </p>
                            )}
                            {canCheckout ? (
                                <ButtonLink href={checkoutUrl} size="lg" block>
                                    Continuar
                                </ButtonLink>
                            ) : (
                                <span
                                    aria-disabled
                                    className="inline-flex min-h-12 items-center justify-center rounded-control bg-brand/50 px-6 font-semibold text-graphite/60"
                                >
                                    Continuar
                                </span>
                            )}
                            <Link
                                href="/"
                                className="text-center text-sm font-semibold text-brand-ink hover:underline"
                            >
                                Continuar escolhendo produtos
                            </Link>
                        </aside>
                    </div>
                )}
            </Container>
        </>
    );
}

function CartItem({ line }: { line: CartLine }) {
    const url = line.item_url;

    const setQuantity = (quantity: number) =>
        router.patch(url, { quantity }, { preserveScroll: true });

    const remove = () => router.delete(url, { preserveScroll: true });

    return (
        <li className="flex gap-3 py-4 sm:gap-4">
            <Link
                href={line.url}
                className="size-20 shrink-0 overflow-hidden rounded-control bg-surface sm:size-24"
            >
                {line.image ? (
                    <img
                        src={line.image.url}
                        alt={line.image.alt}
                        className="size-full object-contain p-1 mix-blend-multiply"
                    />
                ) : (
                    <span className="flex size-full items-center justify-center text-graphite-soft">
                        <ImageOff className="size-6" aria-hidden />
                    </span>
                )}
            </Link>

            <div className="flex min-w-0 flex-1 flex-col gap-2">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <Link
                            href={line.url}
                            className="line-clamp-2 font-semibold hover:text-brand-ink"
                        >
                            {line.name}
                        </Link>
                        {line.variant && (
                            <p className="text-sm text-graphite-soft">
                                {line.variant}
                            </p>
                        )}
                        <p className="text-sm text-graphite-soft">
                            {formatCents(line.unit_price)} cada
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={remove}
                        className="-mt-2 -mr-2 inline-flex size-touch shrink-0 items-center justify-center rounded-control text-graphite-soft hover:bg-surface hover:text-danger"
                        aria-label={`Remover ${line.name}`}
                    >
                        <Trash2 className="size-5" />
                    </button>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex h-touch items-stretch overflow-hidden rounded-control border border-line">
                        <button
                            type="button"
                            onClick={() => setQuantity(line.quantity - 1)}
                            disabled={line.quantity <= 1}
                            className="flex w-11 items-center justify-center hover:bg-surface disabled:opacity-40"
                            aria-label="Diminuir quantidade"
                        >
                            <Minus className="size-4" />
                        </button>
                        <span
                            className="flex w-12 items-center justify-center border-x border-line font-semibold"
                            aria-live="polite"
                        >
                            {line.quantity}
                        </span>
                        <button
                            type="button"
                            onClick={() => setQuantity(line.quantity + 1)}
                            disabled={line.quantity >= line.max_quantity}
                            className="flex w-11 items-center justify-center hover:bg-surface disabled:opacity-40"
                            aria-label="Aumentar quantidade"
                        >
                            <Plus className="size-4" />
                        </button>
                    </div>
                    <span className="text-lg font-extrabold">
                        {formatCents(line.total)}
                    </span>
                </div>

                {!line.enough_stock && (
                    <p
                        className="text-sm font-semibold text-danger"
                        role="alert"
                    >
                        {line.in_stock
                            ? `Só temos ${line.max_quantity} em estoque.`
                            : 'Indisponível no momento. Remova este item.'}
                    </p>
                )}
            </div>
        </li>
    );
}
