import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card } from '../components/ui/Card';
import { Modal } from '../components/ui/Modal';
import { FormField } from '../components/ui/FormField';
import { Plus, Search, Filter, Trash2, Edit3, ExternalLink } from 'lucide-react';

export const Products: React.FC = () => {
  const [products, setProducts] = useState<any[]>([]);
  const [categories, setCategories] = useState<any[]>([]);
  const [brands, setBrands] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Form State
  const [formData, setFormData] = useState({
    name: '',
    brand_id: '',
    category_id: '',
    model_number: '',
    short_description: '',
    status: 'published',
    canonical_upc: '',
    canonical_mpn: '',
  });

  const fetchProducts = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/products', { params: { q: search } });
      setProducts(res.data.data);
    } catch (err) {
      console.error('Failed to load products', err);
    } finally {
      setIsLoading(false);
    }
  };

  const fetchDependencies = async () => {
    try {
      const [catRes, brandRes] = await Promise.all([
        api.get('/categories', { params: { root_only: false } }),
        api.get('/brands'),
      ]);
      setCategories(catRes.data.data);
      setBrands(brandRes.data.data);
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    fetchProducts();
    fetchDependencies();
  }, [search]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      await api.post('/admin/products', formData);
      setIsModalOpen(false);
      setFormData({
        name: '',
        brand_id: '',
        category_id: '',
        model_number: '',
        short_description: '',
        status: 'published',
        canonical_upc: '',
        canonical_mpn: '',
      });
      fetchProducts();
    } catch (err) {
      alert('Failed to create product. Please verify fields.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this canonical product?')) return;
    try {
      await api.delete(`/admin/products/${id}`);
      fetchProducts();
    } catch (err) {
      alert('Failed to delete product.');
    }
  };

  const columns: Column<any>[] = [
    {
      header: 'Product Name',
      accessor: (p) => (
        <div>
          <span className="font-semibold text-slate-100">{p.name}</span>
          <p className="text-[11px] text-slate-500 font-mono mt-0.5">Slug: {p.slug}</p>
        </div>
      ),
    },
    {
      header: 'Brand',
      accessor: (p) => <span className="text-slate-300">{p.brand?.name || '—'}</span>,
    },
    {
      header: 'Category',
      accessor: (p) => <span className="text-slate-300">{p.category?.name || '—'}</span>,
    },
    {
      header: 'Model / MPN',
      accessor: (p) => <span className="font-mono text-xs text-slate-400">{p.model_number || p.canonical_mpn || '—'}</span>,
    },
    {
      header: 'Status',
      accessor: (p) => (
        <Badge variant={p.status === 'published' ? 'success' : (p.status === 'draft' ? 'warning' : 'neutral')}>
          {p.status}
        </Badge>
      ),
    },
    {
      header: 'Actions',
      accessor: (p) => (
        <div className="flex items-center gap-2">
          <Button variant="ghost" size="sm" onClick={() => handleDelete(p.id)} className="text-rose-400">
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
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Canonical Products</h2>
          <p className="text-xs text-slate-400 mt-1">Manage single canonical products with multi-retailer offers.</p>
        </div>
        <Button onClick={() => setIsModalOpen(true)} icon={<Plus className="w-4 h-4" />}>
          Add Product
        </Button>
      </div>

      <Card className="p-4">
        <div className="flex items-center gap-4">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search products by name, model number, slug..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>
        </div>
      </Card>

      <DataTable
        columns={columns}
        data={products}
        isLoading={isLoading}
        emptyTitle="No products found in catalog."
        emptyDescription="No canonical products have been imported or created yet."
      />

      {/* Create Product Modal */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add Canonical Product"
        maxWidth="xl"
      >
        <form onSubmit={handleCreate} className="space-y-4">
          <FormField label="Product Name" required>
            <input
              type="text"
              required
              placeholder="e.g. Apple MacBook Pro 14 M3 Pro"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <div className="grid grid-cols-2 gap-4">
            <FormField label="Brand" required>
              <select
                required
                value={formData.brand_id}
                onChange={(e) => setFormData({ ...formData, brand_id: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              >
                <option value="">Select Brand...</option>
                {brands.map((b) => (
                  <option key={b.id} value={b.id}>{b.name}</option>
                ))}
              </select>
            </FormField>

            <FormField label="Category" required>
              <select
                required
                value={formData.category_id}
                onChange={(e) => setFormData({ ...formData, category_id: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              >
                <option value="">Select Category...</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </FormField>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <FormField label="Model Number">
              <input
                type="text"
                placeholder="e.g. MRX33LL/A"
                value={formData.model_number}
                onChange={(e) => setFormData({ ...formData, model_number: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </FormField>

            <FormField label="Canonical UPC">
              <input
                type="text"
                placeholder="e.g. 195949123456"
                value={formData.canonical_upc}
                onChange={(e) => setFormData({ ...formData, canonical_upc: e.target.value })}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </FormField>
          </div>

          <FormField label="Short Description">
            <textarea
              rows={3}
              placeholder="Brief summary of key specs and highlights..."
              value={formData.short_description}
              onChange={(e) => setFormData({ ...formData, short_description: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <Button variant="secondary" type="button" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" isLoading={isSubmitting}>
              Create Product
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
