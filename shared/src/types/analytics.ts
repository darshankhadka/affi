export interface AffiliateClick {
  id: number;
  offer_id: number;
  product_id: number;
  retailer_id: number;
  market_id: number;
  referrer?: string | null;
  user_agent?: string | null;
  ip_hash: string;
  session_id?: string | null;
  clicked_at: string;
}

export interface IngestionJob {
  id: number;
  provider_id: number;
  market_id: number;
  batch_type: 'full_sync' | 'price_refresh' | 'targeted_update';
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'retrying' | 'skipped';
  total_items: number;
  processed_items: number;
  failed_items: number;
  started_at?: string | null;
  finished_at?: string | null;
  memory_peak_bytes?: number | null;
  cpu_time_ms?: number | null;
  error_log?: string | null;
  created_at: string;
}

export interface SystemHealth {
  status: 'healthy' | 'degraded' | 'critical';
  php_version: string;
  database_connected: boolean;
  cache_accessible: boolean;
  cron_last_run?: string | null;
  stale_offers_count: number;
  failed_jobs_count: number;
  providers: {
    code: string;
    name: string;
    status: 'connected' | 'disconnected' | 'error';
  }[];
}
