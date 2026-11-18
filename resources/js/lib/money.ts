const brl = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

/**
 * Prices come from the server in cents (8860). This turns them into
 * "R$ 88,60" for display. Never do money math with the formatted text.
 */
export function formatCents(cents: number): string {
    return brl.format(cents / 100);
}
