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

/** Laravel paginator, as sent by ->paginate(). */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type SortOption = {
    value: string;
    label: string;
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
