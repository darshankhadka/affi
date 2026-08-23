import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { DataTable, Column } from '../components/ui/DataTable';
import { Globe, Settings as SettingsIcon } from 'lucide-react';

export const SettingsGeneral: React.FC = () => {
  const [markets, setMarkets] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const fetchMarkets = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/markets');
      setMarkets(res.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchMarkets();
  }, []);

  const columns: Column<any>[] = [
    {
      header: 'Market Name',
      accessor: (m) => (
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-lg bg-slate-800 text-emerald-400">
            <Globe className="w-4 h-4" />
          </div>
          <div>
            <span className="font-semibold text-slate-200">{m.name}</span>
            <p className="text-[11px] text-slate-500 font-mono">Code: {m.code.toUpperCase()} · Locale: {m.locale}</p>
          </div>
        </div>
      ),
    },
    {
      header: 'Default Currency',
      accessor: (m) => (
        <span className="font-mono text-xs font-semibold text-slate-300">
          {m.default_currency?.code || 'USD'} ({m.default_currency?.symbol || '$'})
        </span>
      ),
    },
    {
      header: 'Hreflang',
      accessor: (m) => <span className="font-mono text-xs text-slate-400">{m.hreflang}</span>,
    },
    {
      header: 'Status',
      accessor: (m) => (
        <Badge variant={m.is_active ? 'success' : 'neutral'}>
          {m.is_active ? 'Active' : 'Inactive (Awaiting Feeds)'}
        </Badge>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Market & General Configuration</h2>
        <p className="text-xs text-slate-400 mt-1">Multi-country markets (US, UK, DE, FR, ES, IT, AU, NZ) and localized currencies.</p>
      </div>

      <DataTable
        columns={columns}
        data={markets}
        isLoading={isLoading}
        emptyTitle="No markets loaded."
      />
    </div>
  );
};
