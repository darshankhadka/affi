import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card } from '../components/ui/Card';
import { Modal } from '../components/ui/Modal';
import { FormField } from '../components/ui/FormField';
import { Plus, Trash2, ExternalLink, RefreshCw } from 'lucide-react';

export const Offers: React.FC = () => {
  const [offers, setOffers] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [products, setProducts] = useState<any[]>([]);
  const [retailers, setRetailers] = useState<any[]>([]);
  const [markets, setMarkets] = useState<any[]>([]);

  const [formData, setFormData] = useState({
    product_id: '',
    retailer_id: '',
    market_id: '',
    currency_id: '',
    title: '',
    affiliate_url: '',
    price: '',
    availability: 'in_stock',
    condition: 'new',
  });

  const fetchOffers = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/offers');
      setOffers(res.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  const fetchDependencies = async () => {
    try {
      const [prodRes, retRes, mktRes] = await Promise.all([
        api.get('/admin/products'),
        api.get('/admin/affiliates/retailers'),
        api.get('/markets'),
      ]);
      setProducts(prodRes.data.data);
      setRetailers(retRes.data.data);
      setMarkets(mktRes.data.data);
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    fetchOffers();
    fetchDependencies();
  }, []);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const selectedMkt = markets.find((m) => m.id === Number(formData.market_id));
      await api.post('/admin/offers', {
        ...formData,
        currency_id: selectedMkt?.default_currency?.id || 1,
      });
      setIsModalOpen(false);
      fetchOffers();
    } catch (err) {
      alert('Failed to register offer.');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Delete this retailer offer?')) return;
    try {
      await api.delete(`/admin/offers/${id}`);
      fetchOffers();
    } catch (err) {
      alert('Failed to delete offer.');
    }
  };

  const columns: Column<any>[] = [
    {
      header: 'Retailer & Title',
      accessor: (o) => (
        <div>
          <span className="font-semibold text-slate-100">{o.retailer?.name || 'Retailer'}</span>
          <p className="text-xs text-slate-400 truncate max-w-xs">{o.title}</p>
        </div>
      ),
    },
    {
      header: 'Target Product',
      accessor: (o) => <span className="text-slate-300 font-medium">{o.product?.name || '—'}</span>,
    },
    {
      header: 'Market',
      accessor: (o) => <span className="font-mono text-xs uppercase">{o.market?.code || 'US'}</span>,
    },
    {
      header: 'Price',
      accessor: (o) => (
        <span className="font-bold text-emerald-400">
          {o.currency?.symbol || '$'}{Number(o.price).toFixed(2)}
        </span>
      ),
    },
    {
      header: 'Availability',
      accessor: (o) => (
        <Badge variant={o.availability === 'in_stock' ? 'success' : 'danger'}>
          {o.availability.replace('_', ' ')}
        </Badge>
      ),
    },
    {
      header: 'Actions',
      accessor: (o) => (
        <div className="flex items-center gap-2">
          <a
            href={o.affiliate_url}
            target="_blank"
            rel="noopener noreferrer"
            className="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:text-emerald-400 transition-colors"
          >
            <ExternalLink className="w-3.5 h-3.5" />
          </a>
          <Button variant="ghost" size="sm" onClick={() => handleDelete(o.id)} className="text-rose-400">
            <Trash2 className="w-3.5 h-3.5" />
          </Button>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Retailer Offers</h2>
          <p className="text-xs text-slate-400 mt-1">Multi-retailer price points, stock availability, and affiliate links.</p>
        </div>
        <Button onClick={() => setIsModalOpen(true)} icon={<Plus className="w-4 h-4" />}>
          Add Offer
        </Button>
      </div>

      <DataTable
        columns={columns}
        data={offers}
        isLoading={isLoading}
        emptyTitle="No offers recorded."
        emptyDescription="There are currently no retailer offers in the system."
      />

      {/* Add Offer Modal */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add Retailer Offer"
        maxWidth="lg"
      >
        <form onSubmit={handleCreate} className="space-y-4">
          <FormField label="Target Product" required>
            <select
              required
              value={formData.product_id}
              onChange={(e) => setFormData({ ...formData, product_id: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            >
              <option value="">Select Canonical Product...</option>
              {products.map((p) => (
                <option key={p.id} value={p.id}>{p.name}</option>
              ))}
            </select>
          </FormField>

          <div className="grid grid-cols-2 gap-4">
            <FormField label="Retailer" required>
              <select
                required
                value={formData.retailer_id}
                onChange={(e) => setFormData({ ...formData, retailer_id: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              >
                <option value="">Select Retailer...</option>
                {retailers.map((r) => (
                  <option key={r.id} value={r.id}>{r.name}</option>
                ))}
              </select>
            </FormField>

            <FormField label="Market" required>
              <select
                required
                value={formData.market_id}
                onChange={(e) => setFormData({ ...formData, market_id: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              >
                <option value="">Select Target Market...</option>
                {markets.map((m) => (
                  <option key={m.id} value={m.id}>{m.name} ({m.code.toUpperCase()})</option>
                ))}
              </select>
            </FormField>
          </div>

          <FormField label="Offer Title" required>
            <input
              type="text"
              required
              placeholder="e.g. Apple MacBook Pro 14 M3 Pro 18GB 512GB - Space Black"
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <div className="grid grid-cols-2 gap-4">
            <FormField label="Price" required>
              <input
                type="number"
                step="0.01"
                required
                placeholder="1299.00"
                value={formData.price}
                onChange={(e) => setFormData({ ...formData, price: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </FormField>

            <FormField label="Availability" required>
              <select
                value={formData.availability}
                onChange={(e) => setFormData({ ...formData, availability: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              >
                <option value="in_stock">In Stock</option>
                <option value="out_of_stock">Out of Stock</option>
                <option value="preorder">Pre-order</option>
                <option value="discontinued">Discontinued</option>
              </select>
            </FormField>
          </div>

          <FormField label="Direct Destination URL" required>
            <input
              type="url"
              required
              placeholder="https://www.retailer.com/item/..."
              value={formData.affiliate_url}
              onChange={(e) => setFormData({ ...formData, affiliate_url: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <Button variant="secondary" type="button" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit">
              Save Offer
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
