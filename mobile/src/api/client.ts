import { API_URL } from '@/config';
import type { ApiEnvelope } from './types';

let authToken: string | null = null;

/** Set/clear the bearer token used for authenticated requests. */
export function setAuthToken(token: string | null): void {
    authToken = token;
}

export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public errors: Record<string, string[]> | null = null,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

interface RequestOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    body?: Record<string, unknown>;
    /** Skip attaching the bearer token (public endpoints). */
    public?: boolean;
}

/**
 * Thin fetch wrapper around the /api/v1 backend. Attaches the bearer token,
 * parses the standard { success, message, data } envelope, and throws a typed
 * ApiError with validation details on failure.
 */
export async function apiFetch<T>(path: string, options: RequestOptions = {}): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    };
    if (!options.public && authToken) {
        headers.Authorization = `Bearer ${authToken}`;
    }

    let response: Response;
    try {
        response = await fetch(`${API_URL}${path}`, {
            method: options.method ?? 'GET',
            headers,
            body: options.body ? JSON.stringify(options.body) : undefined,
        });
    } catch {
        throw new ApiError('Network error. Please check your connection.', 0);
    }

    let json: (ApiEnvelope<T> & { message?: string }) | null = null;
    try {
        json = (await response.json()) as ApiEnvelope<T>;
    } catch {
        json = null;
    }

    if (!response.ok || (json && json.success === false)) {
        throw new ApiError(
            json?.message ?? `Request failed (${response.status})`,
            response.status,
            json?.errors ?? null,
        );
    }

    return (json?.data ?? (json as unknown as T));
}
