export function getApiBaseUrl(): string {
  return process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api/v1';
}

export function getOutboundUrl(offerId: number | string): string {
  const base = getApiBaseUrl();
  return `${base}/affiliates/out/${offerId}`;
}

export async function fetchApi<T = any>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const baseUrl = getApiBaseUrl();
  const url = `${baseUrl}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;
  
  try {
    const res = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers,
      },
    });

    if (!res.ok) {
      if (res.status === 404) {
        throw new Error('Not found');
      }
      throw new Error(`API error: ${res.status}`);
    }

    return await res.json();
  } catch (err: any) {
    console.error(`Fetch error on ${url}:`, err.message);
    throw err;
  }
}
