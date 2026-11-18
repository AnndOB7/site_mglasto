import { Link } from '@inertiajs/react';
import { cva } from 'class-variance-authority';
import type { VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

export const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 rounded-control text-center leading-tight font-semibold transition-colors outline-none focus-visible:ring-3 focus-visible:ring-brand/50 disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-5 [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                // Orange with graphite text: white on orange fails contrast.
                primary: 'bg-brand text-graphite hover:bg-brand-hover',
                secondary: 'bg-graphite text-white hover:bg-graphite/90',
                outline:
                    'border border-line bg-white text-graphite hover:border-graphite',
                ghost: 'text-graphite hover:bg-surface',
                whatsapp: 'bg-[#25D366] text-graphite hover:bg-[#1EBE5A]',
            },
            size: {
                md: 'min-h-touch px-5 py-2 text-sm',
                lg: 'min-h-12 px-6 py-2.5 text-base',
                icon: 'size-touch',
            },
            block: {
                true: 'w-full',
            },
        },
        defaultVariants: {
            variant: 'primary',
            size: 'md',
        },
    },
);

type Variants = VariantProps<typeof buttonVariants>;

type ButtonProps = ComponentProps<'button'> & Variants;

/**
 * A button. Use {@link ButtonLink} when it navigates to another page.
 */
export function Button({
    className,
    variant,
    size,
    block,
    type = 'button',
    ...props
}: ButtonProps) {
    return (
        <button
            type={type}
            className={cn(buttonVariants({ variant, size, block }), className)}
            {...props}
        />
    );
}

type ButtonLinkProps = ComponentProps<typeof Link> &
    Variants & {
        /** Opens an external site (e.g. WhatsApp) in a new tab with a plain <a>. */
        external?: boolean;
    };

/**
 * A link that looks like a button.
 */
export function ButtonLink({
    className,
    variant,
    size,
    block,
    external = false,
    href,
    children,
    ...props
}: ButtonLinkProps) {
    const classes = cn(buttonVariants({ variant, size, block }), className);

    if (external) {
        return (
            <a
                href={typeof href === 'string' ? href : href?.url}
                target="_blank"
                rel="noopener noreferrer"
                className={classes}
            >
                {children}
            </a>
        );
    }

    return (
        <Link href={href} className={classes} {...props}>
            {children}
        </Link>
    );
}
