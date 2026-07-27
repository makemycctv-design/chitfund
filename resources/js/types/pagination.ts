export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/** Shape produced by Laravel's LengthAwarePaginator (via ->through()). */
export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface SelectOption {
    value: string | number;
    label: string;
}
