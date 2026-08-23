const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api/v1';

export async function fetchApi<T = any>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const url = `${API_BASE_URL}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;
  
  try {
    const res = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers,
      },
      next: { revalidate: 60 }, // ISR with 60s revalidation for high performance & fresh prices
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
