import { CANONICAL_MARKETS, MarketDef } from './catalog';

const COOKIE_NAME = 'arikartech_market';
const STORAGE_KEY = 'arikartech_market';

export function getCookie(name: string): string | null {
  if (typeof document === 'undefined') return null;
  const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
  return match ? decodeURIComponent(match[3]) : null;
}

export function setCookie(name: string, value: string, days: number = 365) {
  if (typeof document === 'undefined') return;
  const date = new Date();
  date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
  document.cookie = `${name}=${encodeURIComponent(value)};expires=${date.toUTCString()};path=/;SameSite=Lax`;
}

export function getUserMarketPreference(): string | null {
  if (typeof window === 'undefined') return null;

  // 1. Check Cookie
  const cookieVal = getCookie(COOKIE_NAME);
  if (cookieVal && CANONICAL_MARKETS.some((m) => m.code === cookieVal.toLowerCase())) {
    return cookieVal.toLowerCase();
  }

  // 2. Check localStorage
  try {
    const storageVal = localStorage.getItem(STORAGE_KEY);
    if (storageVal && CANONICAL_MARKETS.some((m) => m.code === storageVal.toLowerCase())) {
      return storageVal.toLowerCase();
    }
  } catch (e) {
    // Ignore localStorage restrictions
  }

  return null;
}

export function setUserMarketPreference(marketCode: string) {
  const code = marketCode.toLowerCase();
  setCookie(COOKIE_NAME, code, 365);
  try {
    localStorage.setItem(STORAGE_KEY, code);
  } catch (e) {
    // Ignore
  }
}

export function detectBrowserMarket(): string {
  // Check explicit saved user preference first
  const saved = getUserMarketPreference();
  if (saved) return saved;

  if (typeof navigator === 'undefined') return 'us';

  const languages = navigator.languages || [navigator.language || ''];
  for (const lang of languages) {
    if (!lang) continue;
    const parts = lang.split('-');
    const country = (parts[1] || parts[0]).toLowerCase();

    // Direct match with 35 canonical markets
    const matched = CANONICAL_MARKETS.find((m) => m.code === country);
    if (matched) return matched.code;

    // Language prefix mapping
    if (parts[0] === 'da') return 'dk';
    if (parts[0] === 'de') return 'de';
    if (parts[0] === 'fr') return 'fr';
    if (parts[0] === 'es') return 'es';
    if (parts[0] === 'it') return 'it';
    if (parts[0] === 'sv') return 'se';
    if (parts[0] === 'pl') return 'pl';
    if (parts[0] === 'cs') return 'cz';
    if (parts[0] === 'nl') return 'nl';
    if (parts[0] === 'nb' || parts[0] === 'no') return 'no';
    if (parts[0] === 'fi') return 'fi';
    if (parts[0] === 'pt') return 'pt';
    if (parts[0] === 'el') return 'gr';
    if (parts[0] === 'hu') return 'hu';
    if (parts[0] === 'bg') return 'bg';
    if (parts[0] === 'ro') return 'ro';
    if (parts[0] === 'sk') return 'sk';
    if (parts[0] === 'sl') return 'si';
    if (parts[0] === 'et') return 'ee';
    if (parts[0] === 'lv') return 'lv';
    if (parts[0] === 'lt') return 'lt';
  }

  // Global default fallback
  return 'us';
}
