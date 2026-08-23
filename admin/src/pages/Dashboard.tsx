import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { StatCard } from '../components/ui/StatCard';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { EmptyState } from '../components/ui/EmptyState';
import {
  Box,
  BadgePercent,
  MousePointerClick,
  Activity,
  Cpu,
  ShieldCheck,
  RefreshCw,
  Clock,
  Layers,
} from 'lucide-react';

interface DashboardMetrics {
  catalog: {
    total_products: number;
    published_products: number;
    draft_products: number;
    total_categories: number;
    total_brands: number;
  };
  offers: {
    total_offers: number;
    active_offers: number;
    in_stock_offers: number;
    stale_offers: number;
  };
  affiliates: {
    total_providers: number;
    connected_providers: number;
    total_retailers: number;
    active_retailers: number;
  };
  performance: {
    clicks_today: number;
    clicks_total: number;
  };
  recent_automation_jobs: any[];
  system_health: {
    status: string;
    php_version: string;
    database_connected: boolean;
    cache_accessible: boolean;
    stale_offers_count: number;
    providers: { code: string; name: string; status: string }[];
  };
}

export const Dashboard: React.FC = () => {
  const [metrics, setMetrics] = useState<DashboardMetrics | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const fetchMetrics = async () => {
    setIsRefreshing(true);
    try {
      const res = await api.get('/admin/dashboard');
      setMetrics(res.data.data);
    } catch (err) {
      console.error('Failed to load dashboard metrics', err);
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  };

  useEffect(() => {
    fetchMetrics();
  }, []);

  return (
    <div className="space-y-8">
      {/* Page Title & Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Platform Operations</h2>
          <p className="text-xs text-slate-400 mt-1">Live production catalog and affiliate telemetry.</p>
        </div>
        <div className="flex items-center gap-3">
          <Button
            variant="secondary"
            size="sm"
            onClick={fetchMetrics}
            isLoading={isRefreshing}
            icon={<RefreshCw className="w-3.5 h-3.5" />}
          >
            Refresh
          </Button>
        </div>
      </div>

      {/* Primary KPI Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <StatCard
          title="Catalog Products"
          value={metrics?.catalog.total_products ?? 0}
          subtitle={`${metrics?.catalog.published_products ?? 0} published · ${metrics?.catalog.draft_products ?? 0} draft`}
          icon={<Box className="w-5 h-5" />}
        />
        <StatCard
          title="Active Offers"
          value={metrics?.offers.active_offers ?? 0}
          subtitle={`${metrics?.offers.in_stock_offers ?? 0} in stock · ${metrics?.offers.stale_offers ?? 0} need refresh`}
          icon={<BadgePercent className="w-5 h-5" />}
        />
        <StatCard
          title="Affiliate Clicks Today"
          value={metrics?.performance.clicks_today ?? 0}
          subtitle={`${metrics?.performance.clicks_total ?? 0} all-time referrals`}
          icon={<MousePointerClick className="w-5 h-5" />}
          highlight
        />
        <StatCard
          title="System Health"
          value={metrics?.system_health.status.toUpperCase() ?? 'CHECKING'}
          subtitle={`PHP ${metrics?.system_health.php_version ?? '8.4'} · MySQL Connected`}
          icon={<Activity className="w-5 h-5" />}
        />
      </div>

      {/* Two Column Section: Health & Connectors / Automation */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Affiliate Providers & Connectors */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
              <ShieldCheck className="w-4 h-4 text-emerald-400" />
              Affiliate Connectors Status
            </h3>
            <span className="text-xs text-slate-500 font-mono">
              {metrics?.affiliates.connected_providers ?? 0} / {metrics?.affiliates.total_providers ?? 0} Connected
            </span>
          </div>

          <div className="space-y-3">
            {metrics?.system_health.providers.map((p) => (
              <div
                key={p.code}
                className="flex items-center justify-between p-3.5 rounded-xl bg-slate-900/60 border border-slate-800"
              >
                <div>
                  <h4 className="text-xs font-semibold text-slate-200">{p.name}</h4>
                  <p className="text-[11px] text-slate-500 font-mono">Driver: {p.code}</p>
                </div>
                <Badge
                  variant={p.status === 'connected' ? 'success' : 'neutral'}
                  dot={p.status === 'connected'}
                >
                  {p.status === 'connected' ? 'Configured & Active' : 'Disconnected / Unconfigured'}
                </Badge>
              </div>
            )) ?? <EmptyState title="No providers configured" />}
          </div>
        </Card>

        {/* Recent Automation Batches */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
              <Cpu className="w-4 h-4 text-emerald-400" />
              Recent Bounded Automation Jobs
            </h3>
            <span className="text-xs text-slate-500 font-mono">CPU-Safe Bounded Batches</span>
          </div>

          {metrics?.recent_automation_jobs && metrics.recent_automation_jobs.length > 0 ? (
            <div className="space-y-2.5">
              {metrics.recent_automation_jobs.map((job) => (
                <div
                  key={job.id}
                  className="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-xs"
                >
                  <div className="flex items-center gap-3">
                    <Clock className="w-3.5 h-3.5 text-slate-500" />
                    <div>
                      <p className="font-semibold text-slate-200">{job.batch_type}</p>
                      <p className="text-[10px] text-slate-500">
                        Processed {job.processed_items} items · {job.cpu_time_ms}ms CPU
                      </p>
                    </div>
                  </div>
                  <Badge variant={job.status === 'completed' ? 'success' : (job.status === 'processing' ? 'warning' : 'danger')}>
                    {job.status}
                  </Badge>
                </div>
              ))}
            </div>
          ) : (
            <EmptyState
              title="No automation batches run yet."
              description="Automated cron jobs will register execution logs and CPU metrics here."
            />
          )}
        </Card>
      </div>
    </div>
  );
};
