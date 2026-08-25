import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { FormField } from '../components/ui/FormField';
import { Network, Settings2, Play, CheckCircle2, AlertCircle, RefreshCw, ShoppingCart, ArrowRight, ListFilter, PauseCircle, PlayCircle, AlertTriangle } from 'lucide-react';

export const AffiliateProviders: React.FC = () => {
  const [providers, setProviders] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [editingProvider, setEditingProvider] = useState<any | null>(null);
  const [syncProvider, setSyncProvider] = useState<any | null>(null);
  const [isSyncing, setIsSyncing] = useState(false);
  const [syncOutput, setSyncOutput] = useState<string | null>(null);

  // Programmes Discovery State
  const [programmesProvider, setProgrammesProvider] = useState<any | null>(null);
  const [programmesData, setProgrammesData] = useState<{ database_programmes: any[]; live_programmes: any[] } | null>(null);
  const [isLoadingProgrammes, setIsLoadingProgrammes] = useState(false);

  // View Errors State
  const [errorsProvider, setErrorsProvider] = useState<any | null>(null);
  const [errorsData, setErrorsData] = useState<any[]>([]);
  const [isLoadingErrors, setIsLoadingErrors] = useState(false);

  // Amazon Manual Import State (Mode 1)
  const [isAmazonImportOpen, setIsAmazonImportOpen] = useState(false);
  const [amazonUrl, setAmazonUrl] = useState('');
  const [isValidatingAmazon, setIsValidatingAmazon] = useState(false);
  const [amazonValidation, setAmazonValidation] = useState<any | null>(null);
  const [amazonFormData, setAmazonFormData] = useState({
    name: '',
    price: '',
    original_price: '',
    brand_name: '',
    model_number: '',
    category_slug: 'laptops',
    market_code: 'us',
    currency_code: 'USD',
    availability: 'in_stock',
    condition: 'new',
    image_url: '',
    upc: '',
    ean: '',
    mpn: '',
  });
  const [isImportingAmazon, setIsImportingAmazon] = useState(false);
  const [amazonImportResult, setAmazonImportResult] = useState<any | null>(null);

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

  const handleTogglePause = async (p: any) => {
    try {
      await api.post(`/admin/affiliates/providers/${p.id}/pause`, {
        is_active: !p.is_active,
      });
      fetchProviders();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to update provider pause state.');
    }
  };

  const openProgrammesModal = async (p: any) => {
    setProgrammesProvider(p);
    setIsLoadingProgrammes(true);
    setProgrammesData(null);
    try {
      const res = await api.get(`/admin/affiliates/providers/${p.id}/programmes`);
      setProgrammesData(res.data.data);
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to fetch programmes.');
    } finally {
      setIsLoadingProgrammes(false);
    }
  };

  const openErrorsModal = async (p: any) => {
    setErrorsProvider(p);
    setIsLoadingErrors(true);
    setErrorsData([]);
    try {
      const res = await api.get(`/admin/affiliates/providers/${p.id}/errors`);
      setErrorsData(res.data.data.recent_errors || []);
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to fetch errors.');
    } finally {
      setIsLoadingErrors(false);
    }
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
            <div className="flex items-center gap-2">
              <span className="font-semibold text-slate-200">{p.name}</span>
              {!p.is_active && (
                <span className="px-1.5 py-0.5 rounded bg-amber-950/60 text-amber-400 border border-amber-800/60 text-[10px] uppercase font-mono">
                  PAUSED
                </span>
              )}
            </div>
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
        if (p.code === 'amazon') {
          return <Badge variant="neutral">Mode 1: Active | Mode 2: Dormant</Badge>;
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
        <div className="flex items-center gap-1.5 flex-wrap">
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
            onClick={() => openProgrammesModal(p)}
            icon={<ListFilter className="w-3.5 h-3.5" />}
          >
            Programs
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
            variant="ghost"
            size="sm"
            onClick={() => handleTogglePause(p)}
            title={p.is_active ? 'Pause Ingestion' : 'Resume Ingestion'}
          >
            {p.is_active ? <PauseCircle className="w-3.5 h-3.5 text-amber-400" /> : <PlayCircle className="w-3.5 h-3.5 text-emerald-400" />}
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => openErrorsModal(p)}
            title="View Errors"
          >
            <AlertTriangle className="w-3.5 h-3.5 text-rose-400" />
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

  const handleValidateAmazonUrl = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!amazonUrl) return;
    setIsValidatingAmazon(true);
    setAmazonValidation(null);
    setAmazonImportResult(null);
    try {
      const res = await api.post('/admin/affiliates/amazon/validate-url', {
        url: amazonUrl,
      });
      setAmazonValidation(res.data.data);
      setAmazonFormData(prev => ({
        ...prev,
        market_code: res.data.data.market_code || 'us',
        currency_code: res.data.data.market_code === 'uk' || res.data.data.market_code === 'gb' ? 'GBP' : (res.data.data.market_code === 'de' ? 'EUR' : 'USD'),
      }));
    } catch (err: any) {
      alert(err.response?.data?.message || 'Invalid Amazon URL');
    } finally {
      setIsValidatingAmazon(false);
    }
  };

  const handleImportAmazon = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!amazonUrl || !amazonFormData.name || !amazonFormData.price) return;
    setIsImportingAmazon(true);
    try {
      const res = await api.post('/admin/affiliates/amazon/import', {
        url: amazonUrl,
        ...amazonFormData,
        price: parseFloat(amazonFormData.price),
        original_price: amazonFormData.original_price ? parseFloat(amazonFormData.original_price) : undefined,
      });
      setAmazonImportResult(res.data.data);
      alert('Amazon product offer successfully created/matched in canonical catalog!');
      fetchProviders();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to import Amazon product offer.');
    } finally {
      setIsImportingAmazon(false);
    }
  };

  const openAmazonModal = () => {
    setIsAmazonImportOpen(true);
    setAmazonUrl('');
    setAmazonValidation(null);
    setAmazonImportResult(null);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Affiliate Providers</h2>
          <p className="text-xs text-slate-400 mt-1">
            Premier network connectors (Awin, CJ Affiliate, Impact, Amazon). Real status and bounded sync.
          </p>
        </div>
        <Button
          variant="primary"
          onClick={openAmazonModal}
          icon={<ShoppingCart className="w-4 h-4" />}
        >
          Import Amazon URL (Mode 1)
        </Button>
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

      {/* Mode 1: Amazon Manual Import Modal */}
      <Modal
        isOpen={isAmazonImportOpen}
        onClose={() => setIsAmazonImportOpen(false)}
        title="Manual Amazon Affiliate Import (Mode 1)"
        maxWidth="lg"
      >
        <div className="space-y-6">
          <form onSubmit={handleValidateAmazonUrl} className="space-y-3">
            <FormField label="Amazon Product or Affiliate URL">
              <div className="flex gap-2">
                <input
                  type="url"
                  placeholder="https://www.amazon.com/dp/B0CX23V2ZP..."
                  value={amazonUrl}
                  onChange={(e) => setAmazonUrl(e.target.value)}
                  className="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                  required
                />
                <Button type="submit" isLoading={isValidatingAmazon} icon={<ArrowRight className="w-4 h-4" />}>
                  Validate URL
                </Button>
              </div>
            </FormField>
          </form>

          {amazonValidation && (
            <div className="space-y-4 pt-4 border-t border-slate-800">
              <div className="p-3 bg-slate-950 border border-emerald-900/50 rounded-xl text-xs space-y-1">
                <div className="flex items-center gap-2 text-emerald-400 font-semibold">
                  <CheckCircle2 className="w-4 h-4" /> Validated ASIN: {amazonValidation.asin} ({amazonValidation.domain})
                </div>
                <p className="text-slate-400">Attached Tag: <span className="font-mono text-slate-200">{amazonValidation.associate_tag}</span></p>
                {amazonValidation.existing_product && (
                  <p className="text-amber-400">
                    Matches existing catalog product: #{amazonValidation.existing_product.id} {amazonValidation.existing_product.name} ({amazonValidation.existing_product.match_type})
                  </p>
                )}
              </div>

              <form onSubmit={handleImportAmazon} className="space-y-4">
                <FormField label="Product Title / Name *">
                  <input
                    type="text"
                    value={amazonFormData.name}
                    onChange={(e) => setAmazonFormData({ ...amazonFormData, name: e.target.value })}
                    placeholder="e.g. Apple MacBook Pro 14 M3"
                    className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                    required
                  />
                </FormField>

                <div className="grid grid-cols-2 gap-4">
                  <FormField label="Price *">
                    <input
                      type="number"
                      step="0.01"
                      min="0.01"
                      value={amazonFormData.price}
                      onChange={(e) => setAmazonFormData({ ...amazonFormData, price: e.target.value })}
                      placeholder="e.g. 1499.00"
                      className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                      required
                    />
                  </FormField>
                  <FormField label="Original / RRP Price (Optional)">
                    <input
                      type="number"
                      step="0.01"
                      value={amazonFormData.original_price}
                      onChange={(e) => setAmazonFormData({ ...amazonFormData, original_price: e.target.value })}
                      placeholder="e.g. 1699.00"
                      className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                    />
                  </FormField>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <FormField label="Brand Name">
                    <input
                      type="text"
                      value={amazonFormData.brand_name}
                      onChange={(e) => setAmazonFormData({ ...amazonFormData, brand_name: e.target.value })}
                      placeholder="e.g. Apple"
                      className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                    />
                  </FormField>
                  <FormField label="Model Number">
                    <input
                      type="text"
                      value={amazonFormData.model_number}
                      onChange={(e) => setAmazonFormData({ ...amazonFormData, model_number: e.target.value })}
                      placeholder="e.g. MRX33LL/A"
                      className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                    />
                  </FormField>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <FormField label="Target Market">
                    <select
                      value={amazonFormData.market_code}
                      onChange={(e) => setAmazonFormData({ ...amazonFormData, market_code: e.target.value })}
                      className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                    >
                      <option value="us">United States (USD)</option>
                      <option value="gb">United Kingdom (GBP)</option>
                      <option value="de">Germany (EUR)</option>
                      <option value="fr">France (EUR)</option>
                      <option value="es">Spain (EUR)</option>
                      <option value="it">Italy (EUR)</option>
                      <option value="au">Australia (AUD)</option>
                      <option value="ca">Canada (CAD)</option>
                    </select>
                  </FormField>

                  <FormField label="Availability">
                    <select
                      value={amazonFormData.availability}
                      onChange={(e) => setAmazonFormData({ ...amazonFormData, availability: e.target.value })}
                      className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                    >
                      <option value="in_stock">In Stock</option>
                      <option value="out_of_stock">Out of Stock</option>
                      <option value="preorder">Pre-order</option>
                    </select>
                  </FormField>
                </div>

                <FormField label="Image URL (Optional)">
                  <input
                    type="url"
                    value={amazonFormData.image_url}
                    onChange={(e) => setAmazonFormData({ ...amazonFormData, image_url: e.target.value })}
                    placeholder="https://..."
                    className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                  />
                </FormField>

                <div className="flex justify-end gap-3 pt-4 border-t border-slate-800">
                  <Button variant="secondary" type="button" onClick={() => setIsAmazonImportOpen(false)}>
                    Cancel
                  </Button>
                  <Button type="submit" isLoading={isImportingAmazon} icon={<CheckCircle2 className="w-4 h-4" />}>
                    Save Amazon Offer
                  </Button>
                </div>
              </form>
            </div>
          )}
        </div>
      </Modal>

      {/* Discovered / Approved Programmes Modal */}
      <Modal
        isOpen={!!programmesProvider}
        onClose={() => setProgrammesProvider(null)}
        title={`Advertiser Programmes: ${programmesProvider?.name}`}
        maxWidth="lg"
      >
        <div className="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
          {isLoadingProgrammes ? (
            <div className="flex items-center justify-center py-10">
              <RefreshCw className="w-6 h-6 text-emerald-400 animate-spin" />
            </div>
          ) : (
            <div className="space-y-6">
              <div>
                <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                  Database Approved Programmes ({programmesData?.database_programmes?.length || 0})
                </h4>
                {(!programmesData?.database_programmes || programmesData.database_programmes.length === 0) ? (
                  <p className="text-xs text-slate-500 italic">No programmes configured in database.</p>
                ) : (
                  <div className="space-y-2">
                    {programmesData.database_programmes.map((p: any) => (
                      <div key={p.id} className="p-3 bg-slate-900 border border-slate-800 rounded-xl flex items-center justify-between">
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-semibold text-sm text-slate-200">{p.name}</span>
                            <Badge variant={p.status === 'approved' ? 'success' : 'warning'}>
                              {p.status}
                            </Badge>
                          </div>
                          <p className="text-xs text-slate-400 mt-0.5">
                            ID: <span className="font-mono text-slate-300">{p.external_programme_id}</span> | Country: {p.country_code || 'Global'} | Currency: {p.currency || 'EUR'}
                          </p>
                        </div>
                        {p.commission_rate && (
                          <div className="text-right">
                            <span className="text-xs font-mono text-emerald-400 font-semibold">{p.commission_rate}</span>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {programmesData?.live_programmes && programmesData.live_programmes.length > 0 && (
                <div>
                  <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    Live API Joined Programmes ({programmesData.live_programmes.length})
                  </h4>
                  <div className="space-y-2">
                    {programmesData.live_programmes.map((lp: any) => (
                      <div key={lp.id} className="p-2.5 bg-slate-950 border border-slate-800/80 rounded-xl flex items-center justify-between text-xs">
                        <div>
                          <span className="font-medium text-slate-300">{lp.name}</span>
                          <p className="text-[11px] text-slate-500 font-mono">ID: {lp.id} | {lp.primaryRegion?.countryCode || 'Global'} | {lp.currencyCode}</p>
                        </div>
                        <Badge variant="neutral">{lp.status || 'Active'}</Badge>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          <div className="flex justify-end pt-4 border-t border-slate-800">
            <Button variant="secondary" onClick={() => setProgrammesProvider(null)}>
              Close
            </Button>
          </div>
        </div>
      </Modal>

      {/* View Errors Modal */}
      <Modal
        isOpen={!!errorsProvider}
        onClose={() => setErrorsProvider(null)}
        title={`Recent Errors: ${errorsProvider?.name}`}
        maxWidth="md"
      >
        <div className="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
          {isLoadingErrors ? (
            <div className="flex items-center justify-center py-8">
              <RefreshCw className="w-6 h-6 text-emerald-400 animate-spin" />
            </div>
          ) : errorsData.length === 0 ? (
            <div className="text-center py-8 text-slate-400 text-xs">
              <CheckCircle2 className="w-8 h-8 text-emerald-400 mx-auto mb-2 opacity-80" />
              No recent ingestion errors recorded for {errorsProvider?.name}.
            </div>
          ) : (
            <div className="space-y-2">
              {errorsData.map((job: any) => (
                <div key={job.id} className="p-3 bg-slate-950 border border-rose-900/40 rounded-xl text-xs space-y-1">
                  <div className="flex items-center justify-between">
                    <span className="font-semibold text-rose-400">{job.batch_type}</span>
                    <span className="text-[10px] font-mono text-slate-500">Job #{job.id}</span>
                  </div>
                  <p className="text-slate-300 font-mono text-[11px] whitespace-pre-wrap">{job.error_log || 'Unspecified batch error'}</p>
                  <p className="text-[10px] text-slate-500">Started: {new Date(job.started_at).toLocaleString()}</p>
                </div>
              ))}
            </div>
          )}

          <div className="flex justify-end pt-4 border-t border-slate-800">
            <Button variant="secondary" onClick={() => setErrorsProvider(null)}>
              Close
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
};
