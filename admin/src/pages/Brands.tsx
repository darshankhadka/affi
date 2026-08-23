import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { Tag, Plus, Edit2, Trash2, RefreshCw } from 'lucide-react';

export const Brands: React.FC = () => {
  const [brands, setBrands] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingBrand, setEditingBrand] = useState<any>(null);
  const [formData, setFormData] = useState({ name: '', slug: '', website: '', is_active: true });
  const [isSaving, setIsSaving] = useState(false);

  const fetchBrands = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/brands');
      setBrands(res.data.data);
    } catch (err) {
      console.error('Failed to load brands', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchBrands();
  }, []);

  const handleOpenCreate = () => {
    setEditingBrand(null);
    setFormData({ name: '', slug: '', website: '', is_active: true });
    setIsModalOpen(true);
  };

  const handleOpenEdit = (brand: any) => {
    setEditingBrand(brand);
    setFormData({
      name: brand.name,
      slug: brand.slug,
      website: brand.website || '',
      is_active: Boolean(brand.is_active),
    });
    setIsModalOpen(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    try {
      if (editingBrand) {
        await api.put(`/admin/brands/${editingBrand.id}`, formData);
      } else {
        await api.post('/admin/brands', formData);
      }
      setIsModalOpen(false);
      fetchBrands();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to save brand.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Are you sure you want to delete this brand?')) return;
    try {
      await api.delete(`/admin/brands/${id}`);
      fetchBrands();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to delete brand.');
    }
  };

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
        <span className="text-xs text-slate-400 font-mono">{b.website || '—'}</span>
      ),
    },
    {
      header: 'Products',
      accessor: (b) => <span className="text-xs text-slate-400 font-mono">{b.products_count ?? 0}</span>,
    },
    {
      header: 'Status',
      accessor: (b) => (
        <Badge variant={b.is_active ? 'success' : 'neutral'}>
          {b.is_active ? 'Active' : 'Disabled'}
        </Badge>
      ),
    },
    {
      header: 'Actions',
      accessor: (b) => (
        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant="ghost"
            onClick={() => handleOpenEdit(b)}
            icon={<Edit2 className="w-3.5 h-3.5" />}
          >
            Edit
          </Button>
          <Button
            size="sm"
            variant="ghost"
            onClick={() => handleDelete(b.id)}
            icon={<Trash2 className="w-3.5 h-3.5 text-rose-400" />}
          >
            Delete
          </Button>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Brand Directory</h2>
          <p className="text-xs text-slate-400 mt-1">Verified hardware manufacturers and tech brands.</p>
        </div>
        <div className="flex items-center gap-3">
          <Button
            variant="secondary"
            size="sm"
            onClick={fetchBrands}
            isLoading={isLoading}
            icon={<RefreshCw className="w-3.5 h-3.5" />}
          >
            Refresh
          </Button>
          <Button
            variant="primary"
            size="sm"
            onClick={handleOpenCreate}
            icon={<Plus className="w-3.5 h-3.5" />}
          >
            Add Brand
          </Button>
        </div>
      </div>

      <DataTable
        columns={columns}
        data={brands}
        isLoading={isLoading}
        emptyTitle="No brands recorded."
        emptyDescription="No hardware brands have been created or imported yet."
      />

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingBrand ? 'Edit Brand' : 'Create Brand'}
      >
        <form onSubmit={handleSave} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">Brand Name</label>
            <input
              type="text"
              required
              className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">Slug (Optional)</label>
            <input
              type="text"
              className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
              value={formData.slug}
              onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">Official Website</label>
            <input
              type="url"
              placeholder="https://example.com"
              className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
              value={formData.website}
              onChange={(e) => setFormData({ ...formData, website: e.target.value })}
            />
          </div>

          <div className="flex items-center gap-2 pt-2">
            <input
              type="checkbox"
              id="is_active_brand"
              checked={formData.is_active}
              onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
              className="rounded bg-slate-900 border-slate-700 text-emerald-600 focus:ring-0"
            />
            <label htmlFor="is_active_brand" className="text-xs text-slate-300 font-medium">
              Brand Active in Catalog
            </label>
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <Button type="button" variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" variant="primary" isLoading={isSaving}>
              Save Brand
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
