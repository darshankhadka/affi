export interface Brand {
  id: number;
  name: string;
  slug: string;
  logo_url?: string | null;
  website_url?: string | null;
  description?: string | null;
  is_active: boolean;
  products_count?: number;
  created_at?: string;
  updated_at?: string;
}
