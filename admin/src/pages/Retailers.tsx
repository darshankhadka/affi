import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { FormField } from '../components/ui/FormField';
import { Store, Plus } from 'lucide-react';

export const Retailers: React.FC = () => {
  const [retailers, setRetailers] = useState<any[]>([]);
  const [providers, setProviders] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const [formData, setFormData] = useState({
    name: '',
    domain: '',
    logo_url: '',
    affiliate_provider_id: '',
  });

  const fetchRetailers = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/affiliates/retailers');
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
    fetchRetailers();
    fetchProviders();
  }, []);

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

  const columns: Column<any>[] = [
    {
      header: 'Retailer Name',
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
      header: 'Affiliate Provider',
      accessor: (r) => <span className="text-xs text-slate-400">{r.affiliate_provider?.name || 'Direct / None'}</span>,
    },
    {
      header: 'Active Offers',
      accessor: (r) => <span className="text-xs font-mono text-slate-300">{r.offers_count ?? 0}</span>,
    },
    {
      header: 'Status',
      accessor: (r) => (
        <Badge variant={r.is_active ? 'success' : 'neutral'}>
          {r.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Retailers & Merchants</h2>
          <p className="text-xs text-slate-400 mt-1">Authorized merchant partners and affiliate stores.</p>
        </div>
        <Button onClick={() => setIsModalOpen(true)} icon={<Plus className="w-4 h-4" />}>
          Add Retailer
        </Button>
      </div>

      <DataTable
        columns={columns}
        data={retailers}
        isLoading={isLoading}
        emptyTitle="No retailers registered."
        emptyDescription="Merchant partners will appear here once registered or synced."
      />

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
