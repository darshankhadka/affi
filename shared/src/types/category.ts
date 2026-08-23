export interface Category {
  id: number;
  parent_id?: number | null;
  name: string;
  slug: string;
  icon?: string | null;
  description?: string | null;
  is_active: boolean;
  display_order: number;
  parent?: Category | null;
  children?: Category[];
  products_count?: number;
  created_at?: string;
  updated_at?: string;
}
