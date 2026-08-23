import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Modal } from '../components/ui/Modal';
import { FolderTree, Plus, Edit2, Trash2, RefreshCw } from 'lucide-react';

export const Categories: React.FC = () => {
  const [categories, setCategories] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<any>(null);
  const [formData, setFormData] = useState({ name: '', slug: '', description: '', is_active: true });
  const [isSaving, setIsSaving] = useState(false);

  const fetchCategories = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/categories');
      setCategories(res.data.data);
    } catch (err) {
      console.error('Failed to load categories', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  const handleOpenCreate = () => {
    setEditingCategory(null);
    setFormData({ name: '', slug: '', description: '', is_active: true });
    setIsModalOpen(true);
  };

  const handleOpenEdit = (category: any) => {
    setEditingCategory(category);
    setFormData({
      name: category.name,
      slug: category.slug,
      description: category.description || '',
      is_active: Boolean(category.is_active),
    });
    setIsModalOpen(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    try {
      if (editingCategory) {
        await api.put(`/admin/categories/${editingCategory.id}`, formData);
      } else {
        await api.post('/admin/categories', formData);
      }
      setIsModalOpen(false);
      fetchCategories();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to save category.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Are you sure you want to delete this category?')) return;
    try {
      await api.delete(`/admin/categories/${id}`);
      fetchCategories();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to delete category.');
    }
  };

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
      header: 'Products',
      accessor: (c) => <span className="text-xs text-slate-400 font-mono">{c.products_count ?? 0}</span>,
    },
    {
      header: 'Status',
      accessor: (c) => (
        <Badge variant={c.is_active ? 'success' : 'neutral'}>
          {c.is_active ? 'Active' : 'Disabled'}
        </Badge>
      ),
    },
    {
      header: 'Actions',
      accessor: (c) => (
        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant="ghost"
            onClick={() => handleOpenEdit(c)}
            icon={<Edit2 className="w-3.5 h-3.5" />}
          >
            Edit
          </Button>
          <Button
            size="sm"
            variant="ghost"
            onClick={() => handleDelete(c.id)}
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
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Category Hierarchy</h2>
          <p className="text-xs text-slate-400 mt-1">Manage global product categories and taxonomy.</p>
        </div>
        <div className="flex items-center gap-3">
          <Button
            variant="secondary"
            size="sm"
            onClick={fetchCategories}
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
            Add Category
          </Button>
        </div>
      </div>

      <DataTable
        columns={columns}
        data={categories}
        isLoading={isLoading}
        emptyTitle="No categories found."
        emptyDescription="No categories have been established."
      />

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingCategory ? 'Edit Category' : 'Create Category'}
      >
        <form onSubmit={handleSave} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-300 mb-1">Category Name</label>
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
            <label className="block text-xs font-semibold text-slate-300 mb-1">Description</label>
            <textarea
              className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
              rows={3}
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
            />
          </div>

          <div className="flex items-center gap-2 pt-2">
            <input
              type="checkbox"
              id="is_active"
              checked={formData.is_active}
              onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
              className="rounded bg-slate-900 border-slate-700 text-emerald-600 focus:ring-0"
            />
            <label htmlFor="is_active" className="text-xs text-slate-300 font-medium">
              Category Active in Catalog
            </label>
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <Button type="button" variant="secondary" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit" variant="primary" isLoading={isSaving}>
              Save Category
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
