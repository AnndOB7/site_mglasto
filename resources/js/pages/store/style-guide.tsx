import { Head } from '@inertiajs/react';
import { MessageCircle, ShoppingCart } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/store/badge';
import { Button } from '@/components/store/button';
import { Container } from '@/components/store/container';
import { Price } from '@/components/store/price';
import { ProductCard } from '@/components/store/product-card';
import type { ProductSummary } from '@/types/store';

const colors = [
    { token: 'brand', hex: '#FF7A00', use: 'Botões, selos, destaques' },
    { token: 'brand-hover', hex: '#FF6000', use: 'Botão ao passar o mouse' },
    { token: 'brand-soft', hex: '#FFF3E8', use: 'Fundos de seções e selos' },
    {
        token: 'brand-ink',
        hex: '#C25400',
        use: 'Laranja para texto sobre branco',
    },
    { token: 'graphite', hex: '#1C1D1C', use: 'Textos principais, rodapé' },
    { token: 'graphite-soft', hex: '#54595F', use: 'Textos secundários' },
    { token: 'line', hex: '#E9E9E9', use: 'Bordas e divisórias' },
    { token: 'surface', hex: '#F5F5F5', use: 'Fundo de cards e seções' },
];

/** Tailwind needs the full class names written out to generate them. */
const swatch: Record<string, string> = {
    brand: 'bg-brand',
    'brand-hover': 'bg-brand-hover',
    'brand-soft': 'bg-brand-soft',
    'brand-ink': 'bg-brand-ink',
    graphite: 'bg-graphite',
    'graphite-soft': 'bg-graphite-soft',
    line: 'bg-line',
    surface: 'bg-surface',
};

const sample: ProductSummary[] = [
    {
        id: 1,
        name: 'Produto com nome curto',
        url: '/guia-visual',
        price: 10_000,
        sale_price: 8_860,
        in_stock: true,
        image: null,
    },
    {
        id: 2,
        name: 'Produto com um nome bem comprido que precisa quebrar em duas linhas no máximo',
        url: '/guia-visual',
        price: 1_900,
        sale_price: null,
        in_stock: true,
        image: null,
    },
    {
        id: 3,
        name: 'Produto esgotado',
        url: '/guia-visual',
        price: 45_000,
        sale_price: null,
        in_stock: false,
        image: null,
    },
];

/**
 * Living style guide: every token and store component in one page, so the
 * look can be reviewed and adjusted in one place. Not available in production.
 */
export default function StyleGuide() {
    return (
        <>
            <Head title="Guia visual" />
            <Container className="flex flex-col gap-12 py-10">
                <header>
                    <h1 className="text-3xl font-extrabold">Guia visual</h1>
                    <p className="mt-2 text-graphite-soft">
                        Tokens em{' '}
                        <code className="break-all">resources/css/app.css</code>{' '}
                        (bloco <code className="break-all">@theme</code>) e
                        componentes em{' '}
                        <code className="break-all">
                            resources/js/components/store/
                        </code>
                        .
                    </p>
                </header>

                <Section title="Cores">
                    <ul className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        {colors.map((color) => (
                            <li key={color.token} className="text-sm">
                                <div
                                    className={`h-16 rounded-card border border-line ${swatch[color.token]}`}
                                />
                                <p className="mt-2 font-semibold">
                                    {color.token}
                                </p>
                                <p className="text-graphite-soft">
                                    {color.hex} · {color.use}
                                </p>
                            </li>
                        ))}
                    </ul>
                </Section>

                <Section title="Tipografia (Inter)">
                    <p className="text-3xl font-extrabold">Título da página</p>
                    <p className="text-xl font-bold">Título de seção</p>
                    <p>Texto normal de parágrafo.</p>
                    <p className="text-sm text-graphite-soft">
                        Texto secundário.
                    </p>
                </Section>

                <Section title="Botões (altura mínima de 44 px)">
                    <div className="flex flex-wrap gap-3">
                        <Button>
                            <ShoppingCart /> Adicionar ao orçamento
                        </Button>
                        <Button variant="secondary">Secundário</Button>
                        <Button variant="outline">Contorno</Button>
                        <Button variant="ghost">Discreto</Button>
                        <Button variant="whatsapp">
                            <MessageCircle /> WhatsApp
                        </Button>
                        <Button disabled>Desativado</Button>
                    </div>
                    <Button size="lg" block className="sm:w-auto">
                        Botão grande (largura total no celular)
                    </Button>
                </Section>

                <Section title="Selos">
                    <div className="flex flex-wrap gap-2">
                        <Badge variant="offer">Oferta</Badge>
                        <Badge variant="soft">Novo</Badge>
                        <Badge variant="neutral">Esgotado</Badge>
                        <Badge variant="success">Em estoque</Badge>
                        <Badge variant="danger">Indisponível</Badge>
                    </div>
                </Section>

                <Section title="Preço">
                    <Price price={8_860} />
                    <Price price={10_000} salePrice={8_860} />
                    <Price price={10_000} salePrice={8_860} size="lg" />
                </Section>

                <Section title="Cards de produto">
                    <div className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                        {sample.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                </Section>
            </Container>
        </>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-4">
            <h2 className="border-b border-line pb-2 text-xl font-bold">
                {title}
            </h2>
            {children}
        </section>
    );
}
