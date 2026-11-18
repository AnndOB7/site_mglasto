import { Form, Link, usePage } from '@inertiajs/react';
import { Menu, Search, ShoppingCart, X } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import type { ReactNode } from 'react';
import { Container } from '@/components/store/container';
import { cn } from '@/lib/utils';
import type { StoreShared } from '@/types/store';

const TO_DEFINE = '[A DEFINIR]';

/**
 * Layout of every public store page: header with logo, search and cart,
 * category menu, and footer. Separate from the logged-in area and from the
 * /admin panel.
 */
export default function StoreLayout({ children }: { children: ReactNode }) {
    const page = usePage<{ store: StoreShared }>();
    const { store } = page.props;
    const currentUrl = page.url;
    const [menuOpen, setMenuOpen] = useState(false);

    // Close the mobile menu after navigating.
    useEffect(() => setMenuOpen(false), [currentUrl]);

    // Lock page scroll and allow Esc while the mobile menu is open.
    useEffect(() => {
        if (!menuOpen) {
            return;
        }

        const close = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setMenuOpen(false);
            }
        };

        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', close);

        return () => {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', close);
        };
    }, [menuOpen]);

    return (
        <div className="store flex min-h-dvh flex-col bg-white font-sans text-graphite antialiased">
            <a
                href="#conteudo"
                className="sr-only z-50 rounded-control bg-graphite px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-2 focus:left-2"
            >
                Pular para o conteúdo
            </a>

            <header className="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur">
                <Container className="flex items-center gap-3 py-3 lg:gap-6">
                    <button
                        type="button"
                        onClick={() => setMenuOpen(true)}
                        className="-ml-2 inline-flex size-touch items-center justify-center rounded-control hover:bg-surface lg:hidden"
                        aria-label="Abrir menu"
                        aria-expanded={menuOpen}
                        aria-controls="menu-celular"
                    >
                        <Menu className="size-6" />
                    </button>

                    <Link href={store.home_url} className="shrink-0">
                        <img
                            src="/images/logo/logo-mglasto.svg"
                            alt="MGLASTO – página inicial"
                            width={120}
                            height={86}
                            className="h-9 w-auto sm:h-11"
                        />
                    </Link>

                    <SearchForm
                        action={store.search_url}
                        query={store.search_query}
                        className="hidden flex-1 md:flex"
                    />

                    <Link
                        href={store.cart_url}
                        className="relative ml-auto inline-flex min-h-touch items-center gap-2 rounded-control px-2 font-semibold hover:bg-surface md:ml-0"
                    >
                        <ShoppingCart className="size-6" aria-hidden />
                        <span className="hidden sm:inline">Orçamento</span>
                        <span className="sr-only sm:hidden">Orçamento</span>
                        {store.cart_count > 0 && (
                            <span className="absolute top-1 left-5 flex min-w-5 items-center justify-center rounded-full bg-brand px-1 text-xs font-bold text-graphite">
                                {store.cart_count}
                                <span className="sr-only"> itens</span>
                            </span>
                        )}
                    </Link>
                </Container>

                <Container className="pb-3 md:hidden">
                    <SearchForm
                        action={store.search_url}
                        query={store.search_query}
                    />
                </Container>

                <nav
                    aria-label="Categorias"
                    className="hidden border-t border-line lg:block"
                >
                    <Container>
                        <ul className="flex flex-wrap gap-x-1">
                            {store.categories.map((category) => (
                                <li key={category.slug}>
                                    <Link
                                        href={category.url}
                                        className={cn(
                                            'inline-flex min-h-touch items-center border-b-2 px-3 text-sm font-medium whitespace-nowrap hover:text-brand-ink',
                                            currentUrl.startsWith(category.url)
                                                ? 'border-brand text-graphite'
                                                : 'border-transparent text-graphite-soft',
                                        )}
                                    >
                                        {category.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </Container>
                </nav>
            </header>

            {menuOpen && (
                <MobileMenu store={store} onClose={() => setMenuOpen(false)} />
            )}

            <main id="conteudo" className="flex-1">
                {children}
            </main>

            <StoreFooter store={store} />
        </div>
    );
}

function SearchForm({
    action,
    query,
    className,
}: {
    action: string;
    query: string;
    className?: string;
}) {
    const id = useId();

    return (
        <Form
            action={action}
            method="get"
            role="search"
            className={cn('relative w-full max-w-xl', className)}
        >
            <label htmlFor={id} className="sr-only">
                Buscar produtos
            </label>
            <input
                id={id}
                type="search"
                name="q"
                defaultValue={query}
                placeholder="Buscar por produto ou código"
                className="h-touch w-full rounded-control border border-line bg-surface pr-12 pl-4 text-base placeholder:text-graphite-soft focus:border-brand focus:bg-white focus:outline-none sm:text-sm"
            />
            <button
                type="submit"
                className="absolute top-0 right-0 inline-flex size-touch items-center justify-center rounded-r-control text-graphite hover:text-brand-ink"
                aria-label="Buscar"
            >
                <Search className="size-5" />
            </button>
        </Form>
    );
}

function MobileMenu({
    store,
    onClose,
}: {
    store: StoreShared;
    onClose: () => void;
}) {
    return (
        <div className="fixed inset-0 z-40 lg:hidden">
            <button
                type="button"
                className="absolute inset-0 bg-graphite/50"
                aria-label="Fechar menu"
                onClick={onClose}
            />
            <nav
                id="menu-celular"
                aria-label="Menu"
                className="absolute inset-y-0 left-0 flex w-[85%] max-w-sm flex-col overflow-y-auto bg-white shadow-card-hover"
            >
                <div className="flex items-center justify-between border-b border-line px-4 py-3">
                    <img
                        src="/images/logo/logo-mglasto.svg"
                        alt="MGLASTO"
                        className="h-9 w-auto"
                    />
                    <button
                        type="button"
                        onClick={onClose}
                        className="inline-flex size-touch items-center justify-center rounded-control hover:bg-surface"
                        aria-label="Fechar menu"
                        autoFocus
                    >
                        <X className="size-6" />
                    </button>
                </div>
                <p className="px-4 pt-4 text-xs font-bold tracking-wide text-graphite-soft uppercase">
                    Categorias
                </p>
                <ul className="py-2">
                    {store.categories.map((category) => (
                        <li key={category.slug}>
                            <Link
                                href={category.url}
                                className="flex min-h-touch items-center px-4 font-medium hover:bg-surface"
                            >
                                {category.name}
                            </Link>
                        </li>
                    ))}
                </ul>
                <ul className="mt-auto border-t border-line py-2">
                    {store.institutional.map((link) => (
                        <li key={link.url}>
                            <Link
                                href={link.url}
                                className="flex min-h-touch items-center px-4 text-sm text-graphite-soft hover:bg-surface"
                            >
                                {link.label}
                            </Link>
                        </li>
                    ))}
                </ul>
            </nav>
        </div>
    );
}

function StoreFooter({ store }: { store: StoreShared }) {
    const { company } = store;

    return (
        <footer className="mt-16 bg-graphite text-white/80">
            <Container className="grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
                <div className="flex flex-col gap-4">
                    <img
                        src="/images/logo/logo-mglasto-fundo-escuro.svg"
                        alt="MGLASTO"
                        className="h-12 w-auto self-start"
                    />
                    <p className="text-sm">
                        Pisos, revestimentos e artefatos de borracha.
                    </p>
                </div>

                <FooterColumn title="Categorias">
                    {store.categories.map((category) => (
                        <FooterLink key={category.slug} href={category.url}>
                            {category.name}
                        </FooterLink>
                    ))}
                </FooterColumn>

                <FooterColumn title="Ajuda">
                    {store.institutional.map((link) => (
                        <FooterLink key={link.url} href={link.url}>
                            {link.label}
                        </FooterLink>
                    ))}
                </FooterColumn>

                <FooterColumn title="Contato">
                    <li>Telefone: {company.phone ?? TO_DEFINE}</li>
                    <li>E-mail: {company.email ?? TO_DEFINE}</li>
                    <li>Endereço: {company.address ?? TO_DEFINE}</li>
                </FooterColumn>
            </Container>
            <div className="border-t border-white/10">
                <Container className="py-4 text-xs text-white/60">
                    © {new Date().getFullYear()} {company.name} · CNPJ{' '}
                    {company.document ?? TO_DEFINE}
                </Container>
            </div>
        </footer>
    );
}

function FooterColumn({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div>
            <h2 className="mb-3 text-sm font-bold tracking-wide text-white uppercase">
                {title}
            </h2>
            <ul className="flex flex-col gap-1 text-sm">{children}</ul>
        </div>
    );
}

function FooterLink({ href, children }: { href: string; children: ReactNode }) {
    return (
        <li>
            <Link
                href={href}
                className="inline-flex min-h-touch items-center hover:text-brand sm:min-h-8"
            >
                {children}
            </Link>
        </li>
    );
}
