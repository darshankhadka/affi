import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Button } from '../components/ui/Button';
import { RefreshCw, TrendingDown } from 'lucide-react';

export const BestPrices: React.FC = () => {
  const [bestPrices, setBestPrices] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const fetchBestPrices = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/products');
      setBestPrices(res.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchBestPrices();
  }, []);

  const handleRecalculate = async (productId: number) => {
    try {
      await api.post(`/admin/offers/recalculate/${productId}`);
      fetchBestPrices();
    } catch (err) {
      alert('Failed to recalculate.');
    }
  };

  const columns: Column<any>[] = [
    {
      header: 'Canonical Product',
      accessor: (p) => (
        <div>
          <span className="font-semibold text-slate-100">{p.name}</span>
          <p className="text-[11px] text-slate-500 font-mono">Brand: {p.brand?.name || '—'}</p>
        </div>
      ),
    },
    {
      header: 'Offers Count',
      accessor: (p) => <span className="text-xs font-mono">{p.offers_count ?? 0}</span>,
    },
    {
      header: 'Actions',
      accessor: (p) => (
        <Button variant="ghost" size="sm" onClick={() => handleRecalculate(p.id)} icon={<RefreshCw className="w-3.5 h-3.5" />}>
          Recalculate
        </Button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Materialized Best Prices</h2>
        <p className="text-xs text-slate-400 mt-1">Pre-aggregated index for sub-millisecond public catalog lookups.</p>
      </div>

      <DataTable
        columns={columns}
        data={bestPrices}
        isLoading={isLoading}
        emptyTitle="No price records found."
        emptyDescription="Best price indexes will materialize as products and offers are added."
      />
    </div>
  );
};
