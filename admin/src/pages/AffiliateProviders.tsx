import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { FormField } from '../components/ui/FormField';
import { Network, Settings2, Play, CheckCircle2, AlertCircle, RefreshCw } from 'lucide-react';

export const AffiliateProviders: React.FC = () => {
  const [providers, setProviders] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [editingProvider, setEditingProvider] = useState<any | null>(null);
  const [syncProvider, setSyncProvider] = useState<any | null>(null);
  const [isSyncing, setIsSyncing] = useState(false);
  const [syncOutput, setSyncOutput] = useState<string | null>(null);

  // Dynamic credentials state
  const [configFields, setConfigFields] = useState<Record<string, string>>({});

  // Sync Form State
  const [syncMarket, setSyncMarket] = useState('us');
  const [syncLimit, setSyncLimit] = useState(25);
  const [syncKeywords, setSyncKeywords] = useState('Laptops');

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

  const openConfigModal = (p: any) => {
    setEditingProvider(p);
    setConfigFields({});
  };

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingProvider) return;
    try {
      await api.put(`/admin/affiliates/providers/${editingProvider.id}`, {
        is_active: editingProvider.is_active,
        rate_limit_per_minute: editingProvider.rate_limit_per_minute,
        config: configFields,
      });
      setEditingProvider(null);
      fetchProviders();
    } catch (err) {
      alert('Failed to update provider configuration.');
    }
  };

  const handleTriggerSync = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!syncProvider) return;
    setIsSyncing(true);
    setSyncOutput(null);
    try {
      const res = await api.post(`/admin/affiliates/providers/${syncProvider.id}/sync`, {
        market: syncMarket,
        limit: syncLimit,
        keywords: syncKeywords,
      });
      setSyncOutput(res.data.data.output || 'Sync completed successfully.');
      fetchProviders();
    } catch (err: any) {
      setSyncOutput(err.response?.data?.message || 'Sync failed.');
    } finally {
      setIsSyncing(false);
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
      header: 'Supported Markets',
      accessor: (p) => (
        <div className="flex flex-wrap gap-1 max-w-xs">
          {(p.supported_markets || ['us']).map((m: string) => (
            <span key={m} className="px-1.5 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] font-mono uppercase text-slate-300">
              {m}
            </span>
          ))}
        </div>
      ),
    },
    {
      header: 'Rate Limit',
      accessor: (p) => <span className="font-mono text-xs">{p.rate_limit_per_minute} req/min</span>,
    },
    {
      header: 'Connector Status',
      accessor: (p) => {
        if (p.status === 'deferred') {
          return <Badge variant="warning">Deferred / Not Eligible</Badge>;
        }
        if (p.status === 'connected') {
          return <Badge variant="success" dot>Connected</Badge>;
        }
        return <Badge variant="neutral">Disconnected / Unconfigured</Badge>;
      },
    },
    {
      header: 'Actions',
      accessor: (p) => (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={async () => {
              try {
                const res = await api.post(`/admin/affiliates/providers/${p.id}/test`);
                alert(`Connection Test: ${res.data.data.message}`);
                fetchProviders();
              } catch (err: any) {
                alert(`Connection Test Failed: ${err.response?.data?.message || 'Error executing test'}`);
              }
            }}
          >
            Test
          </Button>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => {
              setSyncProvider(p);
              setSyncOutput(null);
            }}
            icon={<Play className="w-3.5 h-3.5" />}
          >
            Sync
          </Button>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => openConfigModal(p)}
            icon={<Settings2 className="w-3.5 h-3.5" />}
          >
            Configure
          </Button>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Affiliate Providers</h2>
        <p className="text-xs text-slate-400 mt-1">
          Premier network connectors (Awin, CJ Affiliate, Impact, Amazon Deferred). Disconnected by default until credentials are set.
        </p>
      </div>

      <DataTable
        columns={columns}
        data={providers}
        isLoading={isLoading}
        emptyTitle="No providers registered."
        emptyDescription="Affiliate provider drivers will be listed here."
      />

      {/* Configure Provider Modal */}
      <Modal
        isOpen={!!editingProvider}
        onClose={() => setEditingProvider(null)}
        title={`Configure ${editingProvider?.name || 'Provider'}`}
        maxWidth="md"
      >
        <form onSubmit={handleUpdate} className="space-y-4">
          {editingProvider?.code === 'awin' && (
            <>
              <FormField label="Awin API Token (OAuth / Bearer)">
                <input
                  type="password"
                  placeholder="••••••••••••••••"
                  value={configFields['api_token'] || ''}
                  onChange={(e) => setConfigFields({ ...configFields, api_token: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
              <FormField label="Awin Publisher ID (Affiliate ID)">
                <input
                  type="text"
                  placeholder="e.g. 123456"
                  value={configFields['publisher_id'] || ''}
                  onChange={(e) => setConfigFields({ ...configFields, publisher_id: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
            </>
          )}

          {editingProvider?.code === 'cj' && (
            <>
              <FormField label="CJ Personal Access Token">
                <input
                  type="password"
                  placeholder="••••••••••••••••"
                  value={configFields['api_token'] || ''}
                  onChange={(e) => setConfigFields({ ...configFields, api_token: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
              <FormField label="CJ Company ID">
                <input
                  type="text"
                  placeholder="e.g. 5566778"
                  value={configFields['company_id'] || ''}
                  onChange={(e) => setConfigFields({ ...configFields, company_id: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
              <FormField label="CJ Website ID (PID)">
                <input
                  type="text"
                  placeholder="e.g. 100500100"
                  value={configFields['website_id'] || ''}
                  onChange={(e) => setConfigFields({ ...configFields, website_id: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
            </>
          )}

          {editingProvider?.code === 'impact' && (
            <>
              <FormField label="Impact Account SID">
                <input
                  type="text"
                  placeholder="e.g. IRxxxxxxxx"
                  value={configFields['account_sid'] || ''}
                  onChange={(e) => setConfigFields({ ...configFields, account_sid: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
              <FormField label="Impact Auth Token">
                <input
                  type="password"
                  placeholder="••••••••••••••••"
                  value={configFields['auth_token'] || ''}
                  onChange={(e) => setConfigFields({ ...configFields, auth_token: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
            </>
          )}

          {editingProvider?.code === 'amazon' && (
            <>
              <FormField label="Amazon PA-API Access Key">
                <input
                  type="password"
                  placeholder="••••••••••••••••"
                  value={configFields['access_key'] || ''}
                  onChange={(e) => setConfigFields({ ...configFields, access_key: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
              <FormField label="Amazon PA-API Secret Key">
                <input
                  type="password"
                  placeholder="••••••••••••••••"
                  value={configFields['secret_key'] || ''}
                  onChange={(e) => setConfigFields({ ...configFields, secret_key: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
            </>
          )}

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

      {/* Bounded Sync Modal */}
      <Modal
        isOpen={!!syncProvider}
        onClose={() => setSyncProvider(null)}
        title={`Trigger Bounded Sync: ${syncProvider?.name}`}
        maxWidth="md"
      >
        <form onSubmit={handleTriggerSync} className="space-y-4">
          <FormField label="Target Market">
            <select
              value={syncMarket}
              onChange={(e) => setSyncMarket(e.target.value)}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            >
              {(syncProvider?.supported_markets || ['us', 'uk', 'de']).map((m: string) => (
                <option key={m} value={m}>
                  {m.toUpperCase()}
                </option>
              ))}
            </select>
          </FormField>

          <FormField label="Search Keywords / Product Category">
            <input
              type="text"
              value={syncKeywords}
              onChange={(e) => setSyncKeywords(e.target.value)}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <FormField label="Batch Limit (Max 50 items)">
            <input
              type="number"
              min={1}
              max={50}
              value={syncLimit}
              onChange={(e) => setSyncLimit(Number(e.target.value))}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          {syncOutput && (
            <div className="p-3 bg-slate-950 border border-slate-800 rounded-xl font-mono text-xs text-slate-300 whitespace-pre-wrap max-h-40 overflow-y-auto">
              {syncOutput}
            </div>
          )}

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <Button variant="secondary" type="button" onClick={() => setSyncProvider(null)}>
              Close
            </Button>
            <Button type="submit" isLoading={isSyncing} icon={<Play className="w-3.5 h-3.5" />}>
              Start Bounded Sync
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
