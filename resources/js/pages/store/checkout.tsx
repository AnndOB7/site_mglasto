import { Head, Link, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useId, useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import { Button } from '@/components/store/button';
import { Container } from '@/components/store/container';
import { maskDocument, maskPhone, maskZip, onlyDigits } from '@/lib/masks';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import type { CartLine, Cents } from '@/types/store';

type CheckoutProps = {
    lines: CartLine[];
    subtotal: Cents;
    states: string[];
    submitUrl: string;
    cartUrl: string;
};

type ZipStatus = 'idle' | 'loading' | 'found' | 'failed';

export default function Checkout({
    lines,
    subtotal,
    states,
    submitUrl,
    cartUrl,
}: CheckoutProps) {
    const form = useForm({
        name: '',
        phone: '',
        email: '',
        document: '',
        zip: '',
        street: '',
        number: '',
        complement: '',
        district: '',
        city: '',
        state: '',
        notes: '',
    });
    const [zipStatus, setZipStatus] = useState<ZipStatus>('idle');
    const errors = form.errors as Record<string, string | undefined>;

    /**
     * Fills the address from the CEP using ViaCEP. If the service is down or
     * the CEP is unknown, the fields stay editable for manual typing.
     */
    const lookupZip = async (zip: string) => {
        const cep = onlyDigits(zip);

        if (cep.length !== 8) {
            return;
        }

        setZipStatus('loading');

        try {
            const response = await fetch(
                `https://viacep.com.br/ws/${cep}/json/`,
                { signal: AbortSignal.timeout(5000) },
            );
            const data = (await response.json()) as {
                erro?: boolean;
                logradouro?: string;
                bairro?: string;
                localidade?: string;
                uf?: string;
            };

            if (!response.ok || data.erro) {
                throw new Error('CEP não encontrado');
            }

            form.setData((current) => ({
                ...current,
                street: data.logradouro || current.street,
                district: data.bairro || current.district,
                city: data.localidade || current.city,
                state: data.uf || current.state,
            }));
            setZipStatus('found');
        } catch {
            setZipStatus('failed');
        }
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(submitUrl);
    };

    return (
        <>
            <Head title="Dados para o orçamento" />
            <Container className="py-6 sm:py-8">
                <h1 className="mb-2 text-2xl font-extrabold sm:text-3xl">
                    Dados para o orçamento
                </h1>
                <p className="mb-6 text-graphite-soft">
                    Usamos esses dados para calcular o frete e falar com você.
                    Não é preciso criar conta.
                </p>

                {errors.cart && (
                    <p
                        className="mb-6 rounded-control bg-danger/10 px-4 py-3 text-sm font-semibold text-danger"
                        role="alert"
                    >
                        {errors.cart}{' '}
                        <Link href={cartUrl} className="underline">
                            Voltar ao orçamento
                        </Link>
                    </p>
                )}

                <form
                    onSubmit={submit}
                    noValidate
                    className="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_20rem]"
                >
                    <div className="flex flex-col gap-8">
                        <FormSection title="Contato">
                            <Field
                                label="Nome completo"
                                error={errors.name}
                                className="sm:col-span-2"
                                input={{
                                    autoComplete: 'name',
                                    value: form.data.name,
                                    onChange: (e) =>
                                        form.setData('name', e.target.value),
                                    required: true,
                                }}
                            />
                            <Field
                                label="Telefone / WhatsApp"
                                error={errors.phone}
                                input={{
                                    type: 'tel',
                                    inputMode: 'tel',
                                    autoComplete: 'tel-national',
                                    placeholder: '(11) 99999-0000',
                                    value: form.data.phone,
                                    onChange: (e) =>
                                        form.setData(
                                            'phone',
                                            maskPhone(e.target.value),
                                        ),
                                    required: true,
                                }}
                            />
                            <Field
                                label="E-mail"
                                optional
                                error={errors.email}
                                input={{
                                    type: 'email',
                                    autoComplete: 'email',
                                    value: form.data.email,
                                    onChange: (e) =>
                                        form.setData('email', e.target.value),
                                }}
                            />
                            <Field
                                label="CPF ou CNPJ"
                                optional
                                hint="Para emitir a nota fiscal."
                                error={errors.document}
                                input={{
                                    inputMode: 'numeric',
                                    value: form.data.document,
                                    onChange: (e) =>
                                        form.setData(
                                            'document',
                                            maskDocument(e.target.value),
                                        ),
                                }}
                            />
                        </FormSection>

                        <FormSection title="Endereço de entrega">
                            <Field
                                label="CEP"
                                error={errors.zip}
                                hint={
                                    zipStatus === 'loading' ? (
                                        <span className="inline-flex items-center gap-1">
                                            <Loader2
                                                className="size-3 animate-spin"
                                                aria-hidden
                                            />
                                            Buscando endereço...
                                        </span>
                                    ) : zipStatus === 'failed' ? (
                                        'Não encontramos o endereço deste CEP. Preencha os campos abaixo.'
                                    ) : undefined
                                }
                                input={{
                                    inputMode: 'numeric',
                                    autoComplete: 'postal-code',
                                    placeholder: '00000-000',
                                    value: form.data.zip,
                                    onChange: (e) => {
                                        const zip = maskZip(e.target.value);
                                        form.setData('zip', zip);

                                        if (onlyDigits(zip).length === 8) {
                                            void lookupZip(zip);
                                        }
                                    },
                                    required: true,
                                }}
                            />
                            <div className="hidden sm:block" />
                            <Field
                                label="Rua"
                                error={errors.street}
                                className="sm:col-span-2"
                                input={{
                                    autoComplete: 'address-line1',
                                    value: form.data.street,
                                    onChange: (e) =>
                                        form.setData('street', e.target.value),
                                    required: true,
                                }}
                            />
                            <Field
                                label="Número"
                                error={errors.number}
                                input={{
                                    value: form.data.number,
                                    onChange: (e) =>
                                        form.setData('number', e.target.value),
                                    required: true,
                                }}
                            />
                            <Field
                                label="Complemento"
                                optional
                                error={errors.complement}
                                input={{
                                    autoComplete: 'address-line2',
                                    value: form.data.complement,
                                    onChange: (e) =>
                                        form.setData(
                                            'complement',
                                            e.target.value,
                                        ),
                                }}
                            />
                            <Field
                                label="Bairro"
                                error={errors.district}
                                input={{
                                    value: form.data.district,
                                    onChange: (e) =>
                                        form.setData(
                                            'district',
                                            e.target.value,
                                        ),
                                    required: true,
                                }}
                            />
                            <Field
                                label="Cidade"
                                error={errors.city}
                                input={{
                                    autoComplete: 'address-level2',
                                    value: form.data.city,
                                    onChange: (e) =>
                                        form.setData('city', e.target.value),
                                    required: true,
                                }}
                            />
                            <SelectField
                                label="Estado"
                                error={errors.state}
                                value={form.data.state}
                                onChange={(value) =>
                                    form.setData('state', value)
                                }
                                options={states}
                            />
                        </FormSection>

                        <FormSection title="Observações">
                            <div className="sm:col-span-2">
                                <label htmlFor="notes" className="sr-only">
                                    Observações
                                </label>
                                <textarea
                                    id="notes"
                                    rows={3}
                                    value={form.data.notes}
                                    onChange={(e) =>
                                        form.setData('notes', e.target.value)
                                    }
                                    placeholder="Medidas, cores, prazo desejado... (opcional)"
                                    className="w-full rounded-control border border-line px-3 py-2 text-base focus:border-brand focus:outline-none sm:text-sm"
                                />
                            </div>
                        </FormSection>
                    </div>

                    <aside className="flex h-fit flex-col gap-4 rounded-card bg-surface p-5 lg:sticky lg:top-36">
                        <h2 className="font-bold">Resumo</h2>
                        <ul className="flex flex-col gap-2 text-sm">
                            {lines.map((line) => (
                                <li
                                    key={line.key}
                                    className="flex justify-between gap-3"
                                >
                                    <span className="min-w-0">
                                        {line.quantity}x {line.name}
                                        {line.variant && (
                                            <span className="block text-graphite-soft">
                                                {line.variant}
                                            </span>
                                        )}
                                    </span>
                                    <span className="shrink-0 font-semibold">
                                        {formatCents(line.total)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <div className="flex items-baseline justify-between border-t border-line pt-3">
                            <span className="font-semibold">
                                Total dos produtos
                            </span>
                            <span className="text-xl font-extrabold">
                                {formatCents(subtotal)}
                            </span>
                        </div>
                        <p className="text-xs text-graphite-soft">
                            O frete é calculado pela equipe e informado no
                            WhatsApp.
                        </p>
                        <Button
                            type="submit"
                            size="lg"
                            block
                            disabled={form.processing}
                        >
                            {form.processing
                                ? 'Enviando...'
                                : 'Gerar pedido de orçamento'}
                        </Button>
                        <Link
                            href={cartUrl}
                            className="text-center text-sm font-semibold text-brand-ink hover:underline"
                        >
                            Voltar e alterar itens
                        </Link>
                    </aside>
                </form>
            </Container>
        </>
    );
}

function FormSection({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <fieldset className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <legend className="mb-4 text-lg font-bold">{title}</legend>
            {children}
        </fieldset>
    );
}

const inputClass =
    'h-touch w-full rounded-control border bg-white px-3 text-base focus:border-brand focus:outline-none sm:text-sm';

function Field({
    label,
    error,
    hint,
    optional = false,
    className,
    input,
}: {
    label: string;
    error?: string;
    hint?: ReactNode;
    optional?: boolean;
    className?: string;
    input: ComponentProps<'input'>;
}) {
    const id = useId();

    return (
        <div className={cn('flex flex-col gap-1', className)}>
            <label htmlFor={id} className="text-sm font-semibold">
                {label}
                {optional && (
                    <span className="font-normal text-graphite-soft">
                        {' '}
                        (opcional)
                    </span>
                )}
            </label>
            <input
                id={id}
                aria-invalid={error ? true : undefined}
                aria-describedby={error || hint ? `${id}-help` : undefined}
                className={cn(
                    inputClass,
                    error ? 'border-danger' : 'border-line',
                )}
                {...input}
            />
            {(error || hint) && (
                <p
                    id={`${id}-help`}
                    className={cn(
                        'text-sm',
                        error ? 'text-danger' : 'text-graphite-soft',
                    )}
                >
                    {error ?? hint}
                </p>
            )}
        </div>
    );
}

function SelectField({
    label,
    error,
    value,
    onChange,
    options,
}: {
    label: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
    options: string[];
}) {
    const id = useId();

    return (
        <div className="flex flex-col gap-1">
            <label htmlFor={id} className="text-sm font-semibold">
                {label}
            </label>
            <select
                id={id}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                aria-invalid={error ? true : undefined}
                className={cn(
                    inputClass,
                    error ? 'border-danger' : 'border-line',
                )}
            >
                <option value="">Selecione</option>
                {options.map((uf) => (
                    <option key={uf} value={uf}>
                        {uf}
                    </option>
                ))}
            </select>
            {error && <p className="text-sm text-danger">{error}</p>}
        </div>
    );
}
