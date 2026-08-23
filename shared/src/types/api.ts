export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  data: T;
  meta?: ApiMeta;
  errors?: Record<string, string[]>;
}

export interface ApiMeta {
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
  from?: number;
  to?: number;
  server_time?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: ApiMeta;
}
