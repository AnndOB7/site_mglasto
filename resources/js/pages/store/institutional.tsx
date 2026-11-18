import { Head, Link } from '@inertiajs/react';
import { ChevronRight, MessageCircle } from 'lucide-react';
import { ButtonLink } from '@/components/store/button';
import { Container } from '@/components/store/container';

type InstitutionalProps = {
    title: string;
    intro: string;
    sections: { title: string; body: string }[];
    whatsappUrl: string | null;
};

/**
 * Highlights every "[A DEFINIR...]" so missing information is impossible to miss.
 */
function WithPlaceholders({ text }: { text: string }) {
    const parts = text.split(/(\[A DEFINIR[^\]]*\])/g);

    return (
        <>
            {parts.map((part, index) =>
                part.startsWith('[A DEFINIR') ? (
                    <mark
                        key={index}
                        className="rounded bg-brand-soft px-1 font-semibold text-brand-ink"
                    >
                        {part}
                    </mark>
                ) : (
                    <span key={index}>{part}</span>
                ),
            )}
        </>
    );
}

export default function Institutional({
    title,
    intro,
    sections,
    whatsappUrl,
}: InstitutionalProps) {
    return (
        <>
            <Head title={title}>
                <meta name="description" content={intro} />
            </Head>
            <Container className="max-w-3xl py-6 sm:py-10">
                <nav aria-label="Você está em" className="mb-4 text-sm">
                    <ol className="flex items-center gap-1 text-graphite-soft">
                        <li>
                            <Link
                                href="/"
                                className="hover:text-brand-ink hover:underline"
                            >
                                Início
                            </Link>
                        </li>
                        <li className="flex items-center gap-1">
                            <ChevronRight className="size-4" aria-hidden />
                            <span aria-current="page">{title}</span>
                        </li>
                    </ol>
                </nav>

                <h1 className="text-2xl font-extrabold sm:text-3xl">{title}</h1>
                <p className="mt-2 text-graphite-soft">{intro}</p>

                {whatsappUrl && (
                    <ButtonLink
                        href={whatsappUrl}
                        external
                        variant="whatsapp"
                        size="lg"
                        className="mt-6"
                    >
                        <MessageCircle /> Falar pelo WhatsApp
                    </ButtonLink>
                )}

                <dl className="mt-8 divide-y divide-line border-y border-line">
                    {sections.map((section) => (
                        <div key={section.title} className="py-5">
                            <dt className="font-bold">{section.title}</dt>
                            <dd className="mt-1 text-graphite-soft">
                                <WithPlaceholders text={section.body} />
                            </dd>
                        </div>
                    ))}
                </dl>
            </Container>
        </>
    );
}
