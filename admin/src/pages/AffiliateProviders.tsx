import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { FormField } from '../components/ui/FormField';
import { Network, Settings2 } from 'lucide-react';

export const AffiliateProviders: React.FC = () => {
  const [providers, setProviders] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [editingProvider, setEditingProvider] = useState<any | null>(null);
  const [accessKey, setAccessKey] = useState('');
  const [secretKey, setSecretKey] = useState('');

  const fetchProviders = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/affiliates/providers');
      setProviders(res.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchProviders();
  }, []);

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingProvider) return;
    try {
      await api.put(`/admin/affiliates/providers/${editingProvider.id}`, {
        is_active: editingProvider.is_active,
        rate_limit_per_minute: editingProvider.rate_limit_per_minute,
        config: {
          access_key: accessKey,
          secret_key: secretKey,
        },
      });
      setEditingProvider(null);
      fetchProviders();
    } catch (err) {
      alert('Failed to update provider configuration.');
    }
  };

  const columns: Column<any>[] = [
    {
      header: 'Provider Name',
      accessor: (p) => (
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-lg bg-slate-800 text-emerald-400">
            <Network className="w-4 h-4" />
          </div>
          <div>
            <span className="font-semibold text-slate-200">{p.name}</span>
            <p className="text-[11px] text-slate-500 font-mono">Driver: {p.code}</p>
          </div>
        </div>
      ),
    },
    {
      header: 'Rate Limit',
      accessor: (p) => <span className="font-mono text-xs">{p.rate_limit_per_minute} req/min</span>,
    },
    {
      header: 'Connector Status',
      accessor: (p) => (
        <Badge variant={p.status === 'connected' ? 'success' : 'neutral'} dot={p.status === 'connected'}>
          {p.status === 'connected' ? 'Connected' : 'Disconnected / Unconfigured'}
        </Badge>
      ),
    },
    {
      header: 'Actions',
      accessor: (p) => (
        <Button
          variant="secondary"
          size="sm"
          onClick={() => {
            setEditingProvider(p);
            setAccessKey('');
            setSecretKey('');
          }}
          icon={<Settings2 className="w-3.5 h-3.5" />}
        >
          Configure
        </Button>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Affiliate Providers</h2>
        <p className="text-xs text-slate-400 mt-1">
          Network connectors (Amazon PA-API, Awin, Impact, CJ). Disconnected by default until credentials are set.
        </p>
      </div>

      <DataTable
        columns={columns}
        data={providers}
        isLoading={isLoading}
        emptyTitle="No providers registered."
        emptyDescription="Affiliate provider drivers will be listed here."
      />

      <Modal
        isOpen={!!editingProvider}
        onClose={() => setEditingProvider(null)}
        title={`Configure ${editingProvider?.name || 'Provider'}`}
        maxWidth="md"
      >
        <form onSubmit={handleUpdate} className="space-y-4">
          <FormField label="API Access Key / Client ID">
            <input
              type="password"
              placeholder="••••••••••••••••"
              value={accessKey}
              onChange={(e) => setAccessKey(e.target.value)}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <FormField label="API Secret Key / Client Secret">
            <input
              type="password"
              placeholder="••••••••••••••••"
              value={secretKey}
              onChange={(e) => setSecretKey(e.target.value)}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <FormField label="Rate Limit (Requests per minute)">
            <input
              type="number"
              value={editingProvider?.rate_limit_per_minute || 60}
              onChange={(e) =>
                setEditingProvider({ ...editingProvider, rate_limit_per_minute: Number(e.target.value) })
              }
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <Button variant="secondary" type="button" onClick={() => setEditingProvider(null)}>
              Cancel
            </Button>
            <Button type="submit">
              Save Configuration
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
