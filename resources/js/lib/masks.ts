/**
 * Input masks: format what the person types. The server removes the mask
 * and validates the digits again, so these are only for convenience.
 */

const digits = (value: string, max: number) =>
    value.replace(/\D/g, '').slice(0, max);

/** (11) 99999-0000 or (11) 2222-0000 */
export function maskPhone(value: string): string {
    const d = digits(value, 11);

    if (d.length <= 2) {
        return d.length ? `(${d}` : '';
    }

    const local = d.slice(2);
    const split = d.length === 11 ? 5 : 4;

    return local.length > split
        ? `(${d.slice(0, 2)}) ${local.slice(0, split)}-${local.slice(split)}`
        : `(${d.slice(0, 2)}) ${local}`;
}

/** 000.000.000-00 (CPF) or 00.000.000/0000-00 (CNPJ), chosen by length. */
export function maskDocument(value: string): string {
    const d = digits(value, 14);

    if (d.length <= 11) {
        return d
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d{1,2})$/, '.$1-$2');
    }

    return d
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
}

/** 00000-000 */
export function maskZip(value: string): string {
    const d = digits(value, 8);

    return d.length > 5 ? `${d.slice(0, 5)}-${d.slice(5)}` : d;
}

export function onlyDigits(value: string): string {
    return value.replace(/\D/g, '');
}
