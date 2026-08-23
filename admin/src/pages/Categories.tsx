import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Card } from '../components/ui/Card';
import { FolderTree } from 'lucide-react';

export const Categories: React.FC = () => {
  const [categories, setCategories] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const fetchCategories = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/categories', { params: { root_only: false } });
      setCategories(res.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  const columns: Column<any>[] = [
    {
      header: 'Category Name',
      accessor: (c) => (
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-lg bg-slate-800 text-emerald-400">
            <FolderTree className="w-4 h-4" />
          </div>
          <div>
            <span className="font-semibold text-slate-200">{c.name}</span>
            <p className="text-[11px] text-slate-500 font-mono">Slug: {c.slug}</p>
          </div>
        </div>
      ),
    },
    {
      header: 'Order',
      accessor: (c) => <span className="font-mono text-xs">{c.display_order}</span>,
    },
    {
      header: 'Products',
      accessor: (c) => <span className="text-xs text-slate-400">{c.products_count ?? 0}</span>,
    },
    {
      header: 'Status',
      accessor: (c) => (
        <Badge variant={c.is_active ? 'success' : 'neutral'}>
          {c.is_active ? 'Active' : 'Disabled'}
        </Badge>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Category Hierarchy</h2>
        <p className="text-xs text-slate-400 mt-1">Manage global product categories and taxonomy.</p>
      </div>

      <DataTable
        columns={columns}
        data={categories}
        isLoading={isLoading}
        emptyTitle="No categories found."
        emptyDescription="No categories have been established."
      />
    </div>
  );
};
