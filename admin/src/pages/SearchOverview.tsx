import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { Card } from '../components/ui/Card';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { StatCard } from '../components/ui/StatCard';
import { Search, Sparkles, TrendingUp, AlertCircle, RefreshCw } from 'lucide-react';
import { Button } from '../components/ui/Button';

export const SearchOverview: React.FC = () => {
  const [data, setData] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(true);

  const fetchSearchIntelligence = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/analytics/search-intelligence');
      setData(res.data.data);
    } catch (err) {
      console.error('Failed to load search intelligence', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchSearchIntelligence();
  }, []);

  const topColumns: Column<any>[] = [
    {
      header: 'Search Query',
      accessor: (item) => (
        <span className="font-semibold text-slate-200">{item.query}</span>
      ),
    },
    {
      header: 'Hit Count',
      accessor: (item) => (
        <span className="font-mono text-xs font-bold text-emerald-400">{item.count} searches</span>
      ),
    },
    {
      header: 'Max Results Returned',
      accessor: (item) => (
        <span className="text-xs text-slate-400">{item.max_results} results</span>
      ),
    },
    {
      header: 'Last Searched',
      accessor: (item) => (
        <span className="text-xs text-slate-500 font-mono">
          {item.last_searched_at ? new Date(item.last_searched_at).toLocaleDateString() : 'N/A'}
        </span>
      ),
    },
  ];

  const zeroColumns: Column<any>[] = [
    {
      header: 'Unmet Demand Query',
      accessor: (item) => (
        <div className="flex items-center gap-2">
          <AlertCircle className="w-3.5 h-3.5 text-amber-400 shrink-0" />
          <span className="font-semibold text-slate-200">{item.query}</span>
        </div>
      ),
    },
    {
      header: 'Search Volume',
      accessor: (item) => (
        <span className="font-mono text-xs text-amber-400">{item.count}</span>
      ),
    },
    {
      header: 'Opportunity Score',
      accessor: (item) => (
        <Badge variant="warning">
          Score: {item.opportunity_score}
        </Badge>
      ),
    },
    {
      header: 'Action',
      accessor: (item) => (
        <span className="text-[11px] text-emerald-400 font-medium">Prioritize Feed Ingestion</span>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Search & Discovery Intelligence</h2>
          <p className="text-xs text-slate-400 mt-1">
            Real visitor search patterns and zero-result commercial keyword gaps to guide feed expansion.
          </p>
        </div>
        <Button
          variant="secondary"
          size="sm"
          onClick={fetchSearchIntelligence}
          isLoading={isLoading}
          icon={<RefreshCw className="w-3.5 h-3.5" />}
        >
          Refresh Data
        </Button>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <StatCard
          title="Total User Searches"
          value={data?.total_searches ?? 0}
          subtitle="Real logged catalog searches"
          icon={<Search className="w-5 h-5" />}
        />
        <StatCard
          title="Zero-Result Gaps"
          value={data?.zero_result_searches_count ?? 0}
          subtitle="Queries with 0 current products"
          icon={<AlertCircle className="w-5 h-5" />}
        />
        <StatCard
          title="Search Coverage"
          value={
            data?.total_searches > 0
              ? `${Math.round(((data.total_searches - data.zero_result_searches_count) / data.total_searches) * 100)}%`
              : '100%'
          }
          subtitle="Queries matching existing products"
          icon={<Sparkles className="w-5 h-5" />}
          highlight
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
              <TrendingUp className="w-4 h-4 text-emerald-400" />
              Top User Searches
            </h3>
          </div>
          <DataTable
            columns={topColumns}
            data={data?.top_queries || []}
            isLoading={isLoading}
            emptyTitle="No search queries logged yet."
            emptyDescription="User search queries from the public storefront will be analyzed here."
          />
        </Card>

        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
              <AlertCircle className="w-4 h-4 text-amber-400" />
              Zero-Result Queries (Expansion Opportunities)
            </h3>
          </div>
          <DataTable
            columns={zeroColumns}
            data={data?.zero_result_queries || []}
            isLoading={isLoading}
            emptyTitle="No zero-result searches logged yet."
            emptyDescription="Unmet user queries with high commercial intent will appear here."
          />
        </Card>
      </div>
    </div>
  );
};
