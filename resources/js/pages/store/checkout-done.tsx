import { Head } from '@inertiajs/react';
import { CheckCircle2, MessageCircle } from 'lucide-react';
import { ButtonLink } from '@/components/store/button';
import { Container } from '@/components/store/container';
import { formatCents } from '@/lib/money';
import type { Cents } from '@/types/store';

type DoneProps = {
    order: {
        number: string;
        customer_name: string;
        total: Cents;
        items: {
            name: string;
            variant: string | null;
            quantity: number;
            total: Cents;
        }[];
    };
    whatsappUrl: string | null;
    homeUrl: string;
};

export default function CheckoutDone({
    order,
    whatsappUrl,
    homeUrl,
}: DoneProps) {
    return (
        <>
            <Head title={`Pedido ${order.number}`} />
            <Container className="flex max-w-2xl flex-col items-center gap-6 py-10 text-center sm:py-14">
                <CheckCircle2 className="size-14 text-success" aria-hidden />
                <div>
                    <h1 className="text-2xl font-extrabold sm:text-3xl">
                        Pedido {order.number} registrado
                    </h1>
                    <p className="mt-2 text-graphite-soft">
                        {whatsappUrl
                            ? 'Falta só um passo: envie o pedido pelo WhatsApp para a equipe da MGLASTO confirmar o preço final, o frete e o prazo.'
                            : 'Guardamos seu pedido. A equipe da MGLASTO vai confirmar o preço final, o frete e o prazo.'}
                    </p>
                </div>

                {whatsappUrl ? (
                    <ButtonLink
                        href={whatsappUrl}
                        external
                        variant="whatsapp"
                        size="lg"
                        block
                        className="sm:w-auto"
                    >
                        <MessageCircle /> Enviar pedido pelo WhatsApp
                    </ButtonLink>
                ) : (
                    <p className="w-full rounded-control bg-brand-soft px-4 py-3 font-semibold text-brand-ink">
                        [A DEFINIR: número do WhatsApp]
                    </p>
                )}

                <section className="w-full rounded-card border border-line p-5 text-left">
                    <h2 className="mb-3 font-bold">Resumo do pedido</h2>
                    <ul className="flex flex-col gap-2 text-sm">
                        {order.items.map((item, index) => (
                            <li
                                key={index}
                                className="flex justify-between gap-3"
                            >
                                <span>
                                    {item.quantity}x {item.name}
                                    {item.variant && (
                                        <span className="block text-graphite-soft">
                                            {item.variant}
                                        </span>
                                    )}
                                </span>
                                <span className="shrink-0 font-semibold">
                                    {formatCents(item.total)}
                                </span>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-3 flex justify-between border-t border-line pt-3 font-bold">
                        <span>Total dos produtos</span>
                        <span>{formatCents(order.total)}</span>
                    </p>
                </section>

                <ButtonLink href={homeUrl} variant="outline">
                    Voltar para a loja
                </ButtonLink>
            </Container>
        </>
    );
}
