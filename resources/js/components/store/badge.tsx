import { cva } from 'class-variance-authority';
import type { VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold tracking-wide uppercase',
    {
        variants: {
            variant: {
                offer: 'bg-brand text-graphite',
                soft: 'bg-brand-soft text-brand-ink',
                neutral: 'bg-surface text-graphite-soft',
                success: 'bg-success/10 text-success',
                danger: 'bg-danger/10 text-danger',
            },
        },
        defaultVariants: {
            variant: 'neutral',
        },
    },
);

export function Badge({
    className,
    variant,
    ...props
}: ComponentProps<'span'> & VariantProps<typeof badgeVariants>) {
    return (
        <span
            className={cn(badgeVariants({ variant }), className)}
            {...props}
        />
    );
}
