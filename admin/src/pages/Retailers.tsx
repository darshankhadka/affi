import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { FormField } from '../components/ui/FormField';
import { Store, Plus, Search, Filter, Activity, CheckCircle2, AlertCircle, RefreshCw } from 'lucide-react';

export const Retailers: React.FC = () => {
  const [retailers, setRetailers] = useState<any[]>([]);
  const [providers, setProviders] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedRetailer, setSelectedRetailer] = useState<any>(null);
  const [testResult, setTestResult] = useState<any>(null);
  const [isTesting, setIsTesting] = useState(false);

  // Filters
  const [search, setSearch] = useState('');
  const [marketFilter, setMarketFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [providerFilter, setProviderFilter] = useState('');

  const [formData, setFormData] = useState({
    name: '',
    domain: '',
    country: 'US',
    market_code: 'us',
    currency_code: 'USD',
    logo_url: '',
    website_url: '',
    affiliate_provider_id: '',
    affiliate_network: '',
    status: 'not_configured',
  });

  const fetchRetailers = async () => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams();
      if (search) params.append('q', search);
      if (marketFilter) params.append('market', marketFilter);
      if (statusFilter) params.append('status', statusFilter);
      if (providerFilter) params.append('provider_id', providerFilter);

      const res = await api.get(`/admin/affiliates/retailers?${params.toString()}`);
      setRetailers(res.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  const fetchProviders = async () => {
    try {
      const res = await api.get('/admin/affiliates/providers');
      setProviders(res.data.data);
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    fetchProviders();
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchRetailers();
    }, 200);
    return () => clearTimeout(timer);
  }, [search, marketFilter, statusFilter, providerFilter]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await api.post('/admin/affiliates/retailers', formData);
      setIsModalOpen(false);
      fetchRetailers();
    } catch (err) {
      alert('Failed to create retailer.');
    }
  };

  const handleTestRetailer = async (r: any) => {
    setSelectedRetailer(r);
    setTestResult(null);
    setIsTesting(true);
    try {
      const res = await api.post(`/admin/affiliates/retailers/${r.id}/test`);
      setTestResult(res.data.data);
    } catch (err: any) {
      setTestResult({
        provider_connection: {
          connected: false,
          status: 'error',
          message: err.response?.data?.message || err.message,
        },
      });
    } finally {
      setIsTesting(false);
    }
  };

  const getStatusBadge = (status: string, isActive: boolean) => {
    if (!isActive) return <Badge variant="neutral">⚫ Disabled</Badge>;
    switch (status) {
      case 'connected':
        return <Badge variant="success">🟢 Connected</Badge>;
      case 'approved':
      case 'pending_approval':
        return <Badge variant="warning">🟡 Pending</Badge>;
      case 'application_required':
        return <Badge variant="info">🔵 App Required</Badge>;
      case 'error':
        return <Badge variant="danger">🔴 Error</Badge>;
      default:
        return <Badge variant="neutral">⚪ Not Configured</Badge>;
    }
  };

  const columns: Column<any>[] = [
    {
      header: 'Retailer & Domain',
      accessor: (r) => (
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-lg bg-slate-800 text-emerald-400">
            <Store className="w-4 h-4" />
          </div>
          <div>
            <span className="font-semibold text-slate-200">{r.name}</span>
            <p className="text-[11px] text-slate-500 font-mono">{r.domain}</p>
          </div>
        </div>
      ),
    },
    {
      header: 'Market',
      accessor: (r) => (
        <div>
          <span className="font-mono text-xs font-bold text-slate-300 uppercase">
            {r.market_code || r.country || '—'}
          </span>
          <p className="text-[10px] text-slate-500">{r.currency_code || '—'}</p>
        </div>
      ),
    },
    {
      header: 'Provider / Network',
      accessor: (r) => (
        <div>
          <span className="text-xs font-medium text-slate-300">
            {r.affiliate_provider?.name || 'Direct'}
          </span>
          <p className="text-[10px] text-slate-500 capitalize">{r.affiliate_network || r.integration_type}</p>
        </div>
      ),
    },
    {
      header: 'Status',
      accessor: (r) => getStatusBadge(r.status, r.is_active),
    },
    {
      header: 'Active Offers',
      accessor: (r) => (
        <span className={`text-xs font-mono font-bold ${r.offers_count > 0 ? 'text-emerald-400' : 'text-slate-500'}`}>
          {r.offers_count ?? 0}
        </span>
      ),
    },
    {
      header: 'Last Sync',
      accessor: (r) => (
        <span className="text-[11px] text-slate-400 font-mono">
          {r.last_successful_sync_at ? new Date(r.last_successful_sync_at).toLocaleDateString() : '—'}
        </span>
      ),
    },
    {
      header: 'Actions',
      accessor: (r) => (
        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant="secondary"
            onClick={() => handleTestRetailer(r)}
            icon={<Activity className="w-3.5 h-3.5" />}
          >
            Test
          </Button>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Global Retailer Matrix (35 Markets)</h2>
          <p className="text-xs text-slate-400 mt-1">105 locked priority merchant integrations across North America, Europe, and Oceania.</p>
        </div>
        <Button onClick={() => setIsModalOpen(true)} icon={<Plus className="w-4 h-4" />}>
          Register Retailer
        </Button>
      </div>

      {/* Filters Bar */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 bg-slate-900/60 p-3 rounded-2xl border border-slate-800">
        <div className="relative">
          <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-500" />
          <input
            type="text"
            placeholder="Search retailers or domains..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
          />
        </div>

        <div>
          <select
            value={marketFilter}
            onChange={(e) => setMarketFilter(e.target.value)}
            className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
          >
            <option value="">All 35 Markets</option>
            <option value="us">United States (US)</option>
            <option value="ca">Canada (CA)</option>
            <option value="gb">United Kingdom (GB)</option>
            <option value="de">Germany (DE)</option>
            <option value="fr">France (FR)</option>
            <option value="nl">Netherlands (NL)</option>
            <option value="es">Spain (ES)</option>
            <option value="it">Italy (IT)</option>
            <option value="dk">Denmark (DK)</option>
            <option value="se">Sweden (SE)</option>
            <option value="pl">Poland (PL)</option>
            <option value="cz">Czechia (CZ)</option>
            <option value="no">Norway (NO)</option>
            <option value="ch">Switzerland (CH)</option>
            <option value="au">Australia (AU)</option>
            <option value="nz">New Zealand (NZ)</option>
          </select>
        </div>

        <div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
          >
            <option value="">All Statuses</option>
            <option value="connected">🟢 Connected</option>
            <option value="pending_approval">🟡 Pending</option>
            <option value="not_configured">⚪ Not Configured</option>
            <option value="application_required">🔵 App Required</option>
            <option value="error">🔴 Error</option>
          </select>
        </div>

        <div>
          <select
            value={providerFilter}
            onChange={(e) => setProviderFilter(e.target.value)}
            className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
          >
            <option value="">All Providers</option>
            {providers.map((p) => (
              <option key={p.id} value={p.id}>{p.name}</option>
            ))}
          </select>
        </div>
      </div>

      <DataTable
        columns={columns}
        data={retailers}
        isLoading={isLoading}
        emptyTitle="No retailers match the filter criteria."
        emptyDescription="Try clearing your filters or search query."
      />

      {/* Diagnostics Modal */}
      {selectedRetailer && (
        <Modal
          isOpen={!!selectedRetailer}
          onClose={() => setSelectedRetailer(null)}
          title={`Retailer Diagnostics: ${selectedRetailer.name}`}
          maxWidth="md"
        >
          <div className="space-y-4">
            <div className="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-2">
              <div className="flex justify-between text-xs">
                <span className="text-slate-500">Retailer Slug:</span>
                <span className="font-mono text-slate-300">{selectedRetailer.slug}</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-slate-500">Market / Currency:</span>
                <span className="font-bold text-slate-300">{selectedRetailer.market_code?.toUpperCase()} ({selectedRetailer.currency_code})</span>
              </div>
              <div className="flex justify-between text-xs">
                <span className="text-slate-500">Provider Driver:</span>
                <span className="text-slate-300">{selectedRetailer.affiliate_provider?.name || 'None'}</span>
              </div>
            </div>

            {isTesting ? (
              <div className="p-6 text-center text-slate-400 text-xs flex items-center justify-center gap-2">
                <RefreshCw className="w-4 h-4 animate-spin text-emerald-400" />
                Testing connection and deeplink resolution...
              </div>
            ) : testResult ? (
              <div className={`p-4 rounded-xl border text-xs space-y-2 ${
                testResult.provider_connection?.connected
                  ? 'bg-emerald-950/20 border-emerald-800 text-emerald-300'
                  : 'bg-slate-900 border-slate-800 text-slate-300'
              }`}>
                <div className="flex items-center gap-2 font-bold">
                  {testResult.provider_connection?.connected ? (
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                  ) : (
                    <AlertCircle className="w-4 h-4 text-amber-400" />
                  )}
                  Status: {testResult.provider_connection?.status?.toUpperCase()}
                </div>
                <p className="text-[11px] text-slate-400">{testResult.provider_connection?.message}</p>
              </div>
            ) : null}

            <div className="flex justify-end pt-3 border-t border-slate-800">
              <Button variant="secondary" onClick={() => setSelectedRetailer(null)}>
                Close
              </Button>
            </div>
          </div>
        </Modal>
      )}

      {/* Register Modal */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Register Retailer Merchant"
        maxWidth="md"
      >
        <form onSubmit={handleCreate} className="space-y-4">
          <FormField label="Retailer Name" required>
            <input
              type="text"
              required
              placeholder="e.g. Best Buy or B&H Photo"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <FormField label="Primary Domain" required>
            <input
              type="text"
              required
              placeholder="e.g. bestbuy.com"
              value={formData.domain}
              onChange={(e) => setFormData({ ...formData, domain: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <div className="grid grid-cols-2 gap-3">
            <FormField label="Market Code">
              <input
                type="text"
                value={formData.market_code}
                onChange={(e) => setFormData({ ...formData, market_code: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </FormField>
            <FormField label="Currency Code">
              <input
                type="text"
                value={formData.currency_code}
                onChange={(e) => setFormData({ ...formData, currency_code: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </FormField>
          </div>

          <FormField label="Affiliate Provider Connector">
            <select
              value={formData.affiliate_provider_id}
              onChange={(e) => setFormData({ ...formData, affiliate_provider_id: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            >
              <option value="">Direct / None</option>
              {providers.map((p) => (
                <option key={p.id} value={p.id}>{p.name}</option>
              ))}
            </select>
          </FormField>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <Button variant="secondary" type="button" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit">
              Register Merchant
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
