import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * Centers the content and keeps the same side margins on every page:
 * 16 px on phones, growing on larger screens.
 */
export function Container({ className, ...props }: ComponentProps<'div'>) {
    return (
        <div
            className={cn(
                'mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8',
                className,
            )}
            {...props}
        />
    );
}
