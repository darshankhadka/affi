/**
 * ARIKARTECH Canonical Catalog & Taxonomy Constants
 * 
 * Used for zero-dependency static site generation (Next.js static export).
 * Eliminates build-time HTTP requests to the API, ensuring 100% deterministic,
 * offline-capable static HTML/CSS/JS export for shared hosting.
 */

export interface MarketDef {
  code: string;
  name: string;
  currency: string;
  symbol: string;
  locale: string;
}

export interface CategoryDef {
  name: string;
  slug: string;
  icon: string;
  description?: string;
}

export interface BrandDef {
  name: string;
  slug: string;
  description?: string;
}

export const CANONICAL_MARKETS: MarketDef[] = [
  { code: 'de', name: 'Germany', currency: 'EUR', symbol: '€', locale: 'de-DE' },
  { code: 'fr', name: 'France', currency: 'EUR', symbol: '€', locale: 'fr-FR' },
  { code: 'nl', name: 'Netherlands', currency: 'EUR', symbol: '€', locale: 'nl-NL' },
  { code: 'es', name: 'Spain', currency: 'EUR', symbol: '€', locale: 'es-ES' },
  { code: 'it', name: 'Italy', currency: 'EUR', symbol: '€', locale: 'it-IT' },
  { code: 'be', name: 'Belgium', currency: 'EUR', symbol: '€', locale: 'nl-BE' },
  { code: 'at', name: 'Austria', currency: 'EUR', symbol: '€', locale: 'de-AT' },
  { code: 'ie', name: 'Ireland', currency: 'EUR', symbol: '€', locale: 'en-IE' },
  { code: 'pt', name: 'Portugal', currency: 'EUR', symbol: '€', locale: 'pt-PT' },
  { code: 'fi', name: 'Finland', currency: 'EUR', symbol: '€', locale: 'fi-FI' },
  { code: 'se', name: 'Sweden', currency: 'EUR', symbol: '€', locale: 'sv-SE' },
  { code: 'dk', name: 'Denmark', currency: 'DKK', symbol: 'kr.', locale: 'da-DK' },
  { code: 'pl', name: 'Poland', currency: 'PLN', symbol: 'zł', locale: 'pl-PL' },
  { code: 'cz', name: 'Czech Republic', currency: 'CZK', symbol: 'Kč', locale: 'cs-CZ' },
  { code: 'gb', name: 'United Kingdom', currency: 'GBP', symbol: '£', locale: 'en-GB' },
];

export const CANONICAL_CATEGORIES: CategoryDef[] = [
  { name: 'Laptops', slug: 'laptops', icon: 'laptop', description: 'Compare Ultrabooks, MacBooks, business laptops, and creator notebooks.' },
  { name: 'Gaming Laptops', slug: 'gaming-laptops', icon: 'laptop', description: 'High-performance laptops equipped with RTX and Radeon discrete graphics.' },
  { name: 'MacBooks', slug: 'macbooks', icon: 'laptop', description: 'Apple MacBook Air and MacBook Pro with M-series Apple Silicon processors.' },
  { name: 'Desktops & Mini PCs', slug: 'desktops-mini-pcs', icon: 'monitor', description: 'Prebuilt desktop towers, workstations, and compact mini PCs.' },
  { name: 'Smartphones', slug: 'smartphones', icon: 'smartphone', description: 'Flagship and mid-range Android smartphones and Apple iPhones.' },
  { name: 'Tablets & iPads', slug: 'tablets-ipads', icon: 'tablet', description: 'Tablets, iPads, and convertible 2-in-1 touchscreen devices.' },
  { name: 'Smartwatches', slug: 'smartwatches', icon: 'watch', description: 'Smartwatches, fitness bands, and GPS sports watches.' },
  { name: 'GPUs & Graphics Cards', slug: 'gpus-graphics-cards', icon: 'cpu', description: 'NVIDIA GeForce RTX, AMD Radeon RX, and Intel Arc graphics cards.' },
  { name: 'CPUs & Processors', slug: 'cpus-processors', icon: 'cpu', description: 'Intel Core and AMD Ryzen desktop and workstation processors.' },
  { name: 'RAM & Memory', slug: 'ram-memory', icon: 'server', description: 'DDR4 and DDR5 desktop and laptop RAM kits.' },
  { name: 'SSDs & Storage', slug: 'ssds-storage', icon: 'hard-drive', description: 'NVMe M.2 SSDs, SATA drives, and portable external SSDs.' },
  { name: 'Motherboards', slug: 'motherboards', icon: 'layers', description: 'Intel and AMD socket motherboards across ATX, Micro-ATX, and Mini-ITX.' },
  { name: 'Power Supplies & Cases', slug: 'power-supplies-cases', icon: 'box', description: 'Modular power supply units (PSUs) and PC enclosures.' },
  { name: 'Gaming Monitors', slug: 'gaming-monitors', icon: 'monitor', description: 'High refresh rate, 4K, OLED, and ultrawide monitors.' },
  { name: '4K & OLED TVs', slug: '4k-oled-tvs', icon: 'tv', description: 'Smart TVs, OLED displays, and high-performance home displays.' },
  { name: 'Mechanical Keyboards', slug: 'mechanical-keyboards', icon: 'keyboard', description: 'Wireless and wired mechanical keyboards with custom switches.' },
  { name: 'Gaming Mice', slug: 'gaming-mice', icon: 'mouse', description: 'Lightweight, wireless, and ergonomic gaming mice.' },
  { name: 'Headphones & Audio', slug: 'headphones-audio', icon: 'headphones', description: 'Noise-canceling headphones, wireless earbuds, and studio monitors.' },
  { name: 'Routers & Mesh WiFi', slug: 'routers-mesh-wifi', icon: 'wifi', description: 'WiFi 6E and WiFi 7 routers, mesh systems, and networking switches.' },
  { name: 'Cables & Docks', slug: 'cables-docks', icon: 'cable', description: 'Thunderbolt docks, USB-C hubs, HDMI 2.1, and DisplayPort cables.' },
];

export const CANONICAL_BRANDS: BrandDef[] = [
  { name: 'Apple', slug: 'apple', description: 'MacBooks, iPhones, iPads, and Apple Silicon hardware.' },
  { name: 'Dell', slug: 'dell', description: 'XPS laptops, Alienware gaming PCs, and UltraSharp monitors.' },
  { name: 'ASUS', slug: 'asus', description: 'ROG gaming laptops, motherboards, GPUs, and ZenBooks.' },
  { name: 'Lenovo', slug: 'lenovo', description: 'ThinkPad business laptops, Legion gaming rigs, and Yoga 2-in-1s.' },
  { name: 'HP', slug: 'hp', description: 'Spectre, Envy, Omen gaming laptops, and accessories.' },
  { name: 'Samsung', slug: 'samsung', description: 'Galaxy devices, OLED monitors, and 990 PRO NVMe SSDs.' },
  { name: 'Sony', slug: 'sony', description: 'WH-1000XM headphones, Bravia OLED displays, and audio.' },
  { name: 'Intel', slug: 'intel', description: 'Core Ultra processors, Arc discrete graphics, and NUCs.' },
  { name: 'AMD', slug: 'amd', description: 'Ryzen 9000 processors and Radeon RX 7000 graphics cards.' },
  { name: 'NVIDIA', slug: 'nvidia', description: 'GeForce RTX 40/50 series GPUs and AI computing hardware.' },
];

export function getMarketDef(code: string): MarketDef {
  const found = CANONICAL_MARKETS.find((m) => m.code.toLowerCase() === code.toLowerCase());
  return found || CANONICAL_MARKETS[0];
}

export function getCategoryDef(slug: string): CategoryDef {
  const normalized = slug.toLowerCase();
  const found = CANONICAL_CATEGORIES.find((c) => c.slug === normalized);
  if (found) return found;
  
  // Format slug to human-readable fallback
  const name = slug
    .split('-')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');

  return {
    name,
    slug,
    icon: 'layers',
    description: `Compare prices and discover verified ${name} from authorized technology retailers.`,
  };
}

export function getBrandDef(slug: string): BrandDef {
  const normalized = slug.toLowerCase();
  const found = CANONICAL_BRANDS.find((b) => b.slug === normalized);
  if (found) return found;

  const name = slug
    .split('-')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');

  return {
    name,
    slug,
    description: `Compare verified store offers and hardware specs for ${name} products.`,
  };
}
