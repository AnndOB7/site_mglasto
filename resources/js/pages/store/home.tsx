import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ClipboardList, MessageCircle, Ruler } from 'lucide-react';
import type { ReactNode } from 'react';
import { ButtonLink, buttonVariants } from '@/components/store/button';
import { Container } from '@/components/store/container';
import { ProductGrid } from '@/components/store/product-grid';
import type { ProductSummary } from '@/types/store';

type HomeCategory = {
    name: string;
    url: string;
    products_count: number;
    image: string | null;
};

type HomeProps = {
    categories: HomeCategory[];
    featured: ProductSummary[];
    products: ProductSummary[];
    productsTotal: number;
    allProductsUrl: string;
};

export default function Home({
    categories,
    featured,
    products,
    productsTotal,
    allProductsUrl,
}: HomeProps) {
    return (
        <>
            <Head title="Pisos, revestimentos e artefatos de borracha">
                <meta
                    name="description"
                    content="Pisos, tapetes, lençóis e artefatos de borracha da MGLASTO. Monte seu orçamento e envie pelo WhatsApp."
                />
            </Head>

            <section className="bg-graphite text-white">
                <Container className="flex flex-col gap-6 py-10 sm:py-14 lg:py-20">
                    <p className="text-sm font-bold tracking-wide text-brand uppercase">
                        Artefatos de borracha
                    </p>
                    <h1 className="max-w-2xl text-3xl leading-tight font-extrabold text-balance sm:text-4xl lg:text-5xl">
                        Pisos, tapetes e revestimentos de borracha para cada
                        necessidade
                    </h1>
                    <p className="max-w-xl text-white/80 sm:text-lg">
                        Escolha os produtos, monte seu orçamento e envie pelo
                        WhatsApp. A equipe da MGLASTO confirma o preço final, o
                        frete e o prazo.
                    </p>
                    <div className="flex flex-col gap-3 sm:flex-row">
                        {featured.length > 0 && (
                            <a
                                href="#ofertas"
                                className={buttonVariants({ size: 'lg' })}
                            >
                                Ver ofertas
                            </a>
                        )}
                        <ButtonLink
                            href={allProductsUrl}
                            size="lg"
                            variant="outline"
                            className="border-white/30 bg-transparent text-white hover:border-white"
                        >
                            Todos os produtos
                        </ButtonLink>
                    </div>
                </Container>
            </section>

            <section className="border-b border-line bg-surface">
                <Container className="grid gap-4 py-5 text-sm sm:grid-cols-3">
                    <Highlight icon={<ClipboardList />}>
                        Monte o orçamento sem precisar de cadastro
                    </Highlight>
                    <Highlight icon={<MessageCircle />}>
                        Envie o pedido pronto pelo WhatsApp
                    </Highlight>
                    <Highlight icon={<Ruler />}>
                        Fotos e descrição de cada produto
                    </Highlight>
                </Container>
            </section>

            {categories.length > 0 && (
                <section>
                    <Container className="py-10">
                        <h2 className="mb-5 text-2xl font-extrabold">
                            Categorias
                        </h2>
                        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                            {categories.map((category) => (
                                <li key={category.url}>
                                    <Link
                                        href={category.url}
                                        className="group flex h-full flex-col items-start gap-3 rounded-card border border-line bg-white p-3 transition-shadow hover:shadow-card-hover sm:flex-row sm:items-center"
                                    >
                                        <span className="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-control bg-surface sm:size-16">
                                            {category.image && (
                                                <img
                                                    src={category.image}
                                                    alt=""
                                                    loading="lazy"
                                                    className="size-full object-contain p-1 mix-blend-multiply"
                                                />
                                            )}
                                        </span>
                                        <span className="flex min-w-0 flex-col">
                                            <span className="block text-sm leading-5 font-semibold break-words hyphens-auto sm:line-clamp-2">
                                                {category.name}
                                            </span>
                                            <span className="text-xs text-graphite-soft">
                                                {category.products_count}{' '}
                                                {category.products_count === 1
                                                    ? 'produto'
                                                    : 'produtos'}
                                            </span>
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </Container>
                </section>
            )}

            {featured.length > 0 && (
                <section id="ofertas" className="scroll-mt-28 bg-brand-soft">
                    <Container className="py-10">
                        <h2 className="mb-5 text-2xl font-extrabold">
                            Ofertas
                        </h2>
                        <ProductGrid products={featured} />
                    </Container>
                </section>
            )}

            <section>
                <Container className="py-10">
                    <div className="mb-5 flex items-end justify-between gap-4">
                        <h2 className="text-2xl font-extrabold">
                            {featured.length > 0
                                ? 'Mais produtos'
                                : 'Todos os produtos'}
                        </h2>
                        <Link
                            href={allProductsUrl}
                            className="inline-flex min-h-touch items-center gap-1 text-sm font-semibold text-brand-ink hover:underline"
                        >
                            Ver todos ({productsTotal})
                            <ArrowRight className="size-4" aria-hidden />
                        </Link>
                    </div>
                    <ProductGrid products={products} />
                </Container>
            </section>
        </>
    );
}

function Highlight({
    icon,
    children,
}: {
    icon: ReactNode;
    children: ReactNode;
}) {
    return (
        <p className="flex items-center gap-3 font-medium [&_svg]:size-6 [&_svg]:shrink-0 [&_svg]:text-brand-ink">
            {icon}
            {children}
        </p>
    );
}
