import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Card } from '../components/ui/Card';
import { Tag } from 'lucide-react';

export const Brands: React.FC = () => {
  const [brands, setBrands] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const fetchBrands = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/brands');
      setBrands(res.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchBrands();
  }, []);

  const columns: Column<any>[] = [
    {
      header: 'Brand Name',
      accessor: (b) => (
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-lg bg-slate-800 text-emerald-400">
            <Tag className="w-4 h-4" />
          </div>
          <div>
            <span className="font-semibold text-slate-200">{b.name}</span>
            <p className="text-[11px] text-slate-500 font-mono">Slug: {b.slug}</p>
          </div>
        </div>
      ),
    },
    {
      header: 'Website',
      accessor: (b) => (
        <span className="text-xs text-slate-400 font-mono">{b.website_url || '—'}</span>
      ),
    },
    {
      header: 'Products',
      accessor: (b) => <span className="text-xs text-slate-400">{b.products_count ?? 0}</span>,
    },
    {
      header: 'Status',
      accessor: (b) => (
        <Badge variant={b.is_active ? 'success' : 'neutral'}>
          {b.is_active ? 'Active' : 'Disabled'}
        </Badge>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Brand Directory</h2>
        <p className="text-xs text-slate-400 mt-1">Verified hardware manufacturers and tech brands.</p>
      </div>

      <DataTable
        columns={columns}
        data={brands}
        isLoading={isLoading}
        emptyTitle="No brands recorded."
        emptyDescription="No hardware brands have been created or imported yet."
      />
    </div>
  );
};
