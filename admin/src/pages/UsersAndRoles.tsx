import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Badge } from '../components/ui/Badge';
import { DataTable, Column } from '../components/ui/DataTable';
import { Modal } from '../components/ui/Modal';
import { FormField } from '../components/ui/FormField';
import { ShieldCheck, Plus, UserPlus } from 'lucide-react';

export const UsersAndRoles: React.FC = () => {
  const [users, setUsers] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    role: 'Editor',
  });

  const fetchUsers = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/users');
      setUsers(res.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await api.post('/admin/users', formData);
      setIsModalOpen(false);
      setFormData({ name: '', email: '', password: '', role: 'Editor' });
      fetchUsers();
    } catch (err) {
      alert('Failed to create user.');
    }
  };

  const columns: Column<any>[] = [
    {
      header: 'User',
      accessor: (u) => (
        <div>
          <span className="font-semibold text-slate-200">{u.name}</span>
          <p className="text-xs text-slate-500 font-mono">{u.email}</p>
        </div>
      ),
    },
    {
      header: 'Assigned Role',
      accessor: (u) => (
        <Badge variant={u.roles?.includes('Super Admin') ? 'purple' : 'info'}>
          {u.roles?.[0] || 'User'}
        </Badge>
      ),
    },
    {
      header: 'Last Login',
      accessor: (u) => <span className="text-xs text-slate-400">{u.last_login_at ? new Date(u.last_login_at).toLocaleString() : 'Never'}</span>,
    },
    {
      header: 'Status',
      accessor: (u) => (
        <Badge variant={u.is_active ? 'success' : 'danger'}>
          {u.is_active ? 'Active' : 'Disabled'}
        </Badge>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Users & Role-Based Access Control (RBAC)</h2>
          <p className="text-xs text-slate-400 mt-1">Granular permissions: Super Admin, Admin, Editor, and Analyst.</p>
        </div>
        <Button onClick={() => setIsModalOpen(true)} icon={<UserPlus className="w-4 h-4" />}>
          Add User
        </Button>
      </div>

      <DataTable
        columns={columns}
        data={users}
        isLoading={isLoading}
        emptyTitle="No users found."
      />

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Add Staff User"
        maxWidth="md"
      >
        <form onSubmit={handleCreate} className="space-y-4">
          <FormField label="Full Name" required>
            <input
              type="text"
              required
              placeholder="e.g. Jane Doe"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <FormField label="Email Address" required>
            <input
              type="email"
              required
              placeholder="jane@arikartech.com"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <FormField label="Initial Password" required>
            <input
              type="password"
              required
              placeholder="••••••••••••"
              value={formData.password}
              onChange={(e) => setFormData({ ...formData, password: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            />
          </FormField>

          <FormField label="Role Assignment" required>
            <select
              value={formData.role}
              onChange={(e) => setFormData({ ...formData, role: e.target.value })}
              className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
            >
              <option value="Admin">Admin</option>
              <option value="Editor">Editor</option>
              <option value="Analyst">Analyst</option>
            </select>
          </FormField>

          <div className="flex justify-end gap-3 pt-4 border-t border-slate-800">
            <Button variant="secondary" type="button" onClick={() => setIsModalOpen(false)}>
              Cancel
            </Button>
            <Button type="submit">
              Create User
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
