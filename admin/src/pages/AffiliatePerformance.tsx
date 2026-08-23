import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { Card } from '../components/ui/Card';
import { DataTable, Column } from '../components/ui/DataTable';
import { StatCard } from '../components/ui/StatCard';
import { Button } from '../components/ui/Button';
import { MousePointerClick, TrendingUp, Store, Globe, RefreshCw, Network, Box } from 'lucide-react';

export const AffiliatePerformance: React.FC = () => {
  const [data, setData] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(true);

  const fetchConversionData = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/analytics/conversion');
      setData(res.data.data);
    } catch (err) {
      console.error('Failed to load conversion analytics', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchConversionData();
  }, []);

  const providerColumns: Column<any>[] = [
    {
      header: 'Affiliate Network',
      accessor: (item) => <span className="font-semibold text-slate-200">{item.provider_name}</span>,
    },
    {
      header: 'Outbound Referrals',
      accessor: (item) => <span className="font-mono text-xs font-bold text-emerald-400">{item.click_count} clicks</span>,
    },
  ];

  const retailerColumns: Column<any>[] = [
    {
      header: 'Merchant Retailer',
      accessor: (item) => <span className="font-semibold text-slate-200">{item.retail_name || item.retailer_name}</span>,
    },
    {
      header: 'Referrals',
      accessor: (item) => <span className="font-mono text-xs font-bold text-emerald-400">{item.click_count} clicks</span>,
    },
  ];

  const productColumns: Column<any>[] = [
    {
      header: 'Product',
      accessor: (item) => <span className="font-semibold text-slate-200">{item.product_name}</span>,
    },
    {
      header: 'Total Clicks',
      accessor: (item) => <span className="font-mono text-xs font-bold text-emerald-400">{item.click_count}</span>,
    },
  ];

  const marketColumns: Column<any>[] = [
    {
      header: 'Market',
      accessor: (item) => <span className="font-mono uppercase font-bold text-slate-300">{item.market_code}</span>,
    },
    {
      header: 'Referrals',
      accessor: (item) => <span className="font-mono text-xs font-bold text-emerald-400">{item.click_count}</span>,
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Affiliate Conversion & Performance</h2>
          <p className="text-xs text-slate-400 mt-1">
            Real-time outbound click volume, network attribution, and retailer conversion funnel metrics.
          </p>
        </div>
        <Button
          variant="secondary"
          size="sm"
          onClick={fetchConversionData}
          isLoading={isLoading}
          icon={<RefreshCw className="w-3.5 h-3.5" />}
        >
          Refresh Data
        </Button>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <StatCard
          title="Total Outbound Referrals"
          value={data?.metrics?.total_clicks ?? 0}
          subtitle="All-time verified affiliate clicks"
          icon={<MousePointerClick className="w-5 h-5" />}
          highlight
        />
        <StatCard
          title="Referrals Today"
          value={data?.metrics?.clicks_today ?? 0}
          subtitle="Outbound clicks logged today"
          icon={<TrendingUp className="w-5 h-5" />}
        />
        <StatCard
          title="Catalog Interactions"
          value={data?.metrics?.total_searches ?? 0}
          subtitle="Search & discovery queries"
          icon={<Globe className="w-5 h-5" />}
        />
        <StatCard
          title="Conversion CTR"
          value={`${data?.metrics?.ctr_percentage ?? 0}%`}
          subtitle="Interactions to click conversion rate"
          icon={<TrendingUp className="w-5 h-5" />}
        />
      </div>

      {/* Breakdown Grids */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
              <Network className="w-4 h-4 text-emerald-400" />
              Clicks by Affiliate Network
            </h3>
          </div>
          <DataTable
            columns={providerColumns}
            data={data?.clicks_by_provider || []}
            isLoading={isLoading}
            emptyTitle="No network clicks logged yet."
            emptyDescription="Attributed affiliate clicks will appear here."
          />
        </Card>

        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
              <Store className="w-4 h-4 text-emerald-400" />
              Clicks by Merchant Retailer
            </h3>
          </div>
          <DataTable
            columns={retailerColumns}
            data={data?.clicks_by_retailer || []}
            isLoading={isLoading}
            emptyTitle="No retailer clicks logged yet."
            emptyDescription="Retailer referral traffic will appear here."
          />
        </Card>

        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
              <Box className="w-4 h-4 text-emerald-400" />
              Top Converted Products
            </h3>
          </div>
          <DataTable
            columns={productColumns}
            data={data?.clicks_by_product || []}
            isLoading={isLoading}
            emptyTitle="No product clicks logged yet."
            emptyDescription="Products driving outbound clicks will appear here."
          />
        </Card>

        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
              <Globe className="w-4 h-4 text-emerald-400" />
              Clicks by Country Market
            </h3>
          </div>
          <DataTable
            columns={marketColumns}
            data={data?.clicks_by_market || []}
            isLoading={isLoading}
            emptyTitle="No market clicks logged yet."
            emptyDescription="Market distribution of outbound clicks will appear here."
          />
        </Card>
      </div>
    </div>
  );
};
