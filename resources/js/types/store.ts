/** Money is always in cents: 8860 = R$ 88,60. */
export type Cents = number;

export type ProductImage = {
    url: string;
    alt: string;
};

/** A product in a list (home, category, search). */
export type ProductSummary = {
    id: number;
    name: string;
    url: string;
    price: Cents;
    sale_price: Cents | null;
    in_stock: boolean;
    image: ProductImage | null;
};

export type CategoryLink = {
    name: string;
    slug: string;
    url: string;
};

export type StoreLink = {
    label: string;
    url: string;
};

/** Data every store page receives (see HandleInertiaRequests). */
export type StoreShared = {
    categories: CategoryLink[];
    cart_count: number;
    search_url: string;
    search_query: string;
    cart_url: string;
    home_url: string;
    institutional: StoreLink[];
    company: {
        name: string;
        phone: string | null;
        email: string | null;
        address: string | null;
        document: string | null;
    };
};
