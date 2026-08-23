import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Badge } from '../components/ui/Badge';
import { DataTable, Column } from '../components/ui/DataTable';
import { Cpu, Play, RefreshCw, Clock } from 'lucide-react';

export const AutomationStatus: React.FC = () => {
  const [jobs, setJobs] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRunningBatch, setIsRunningBatch] = useState(false);

  const fetchJobs = async () => {
    setIsLoading(true);
    try {
      const res = await api.get('/admin/automation/jobs');
      setJobs(res.data.data?.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchJobs();
  }, []);

  const handleTriggerBatch = async () => {
    setIsRunningBatch(true);
    try {
      const res = await api.post('/admin/automation/batch');
      alert(`Batch completed: ${res.data.data.processed} items processed in ${res.data.data.cpu_time_sec}s.`);
      fetchJobs();
    } catch (err) {
      alert('Failed to trigger batch.');
    } finally {
      setIsRunningBatch(false);
    }
  };

  const columns: Column<any>[] = [
    {
      header: 'Batch Type',
      accessor: (j) => (
        <div>
          <span className="font-semibold text-slate-200">{j.batch_type}</span>
          <p className="text-[10px] text-slate-500 font-mono">Job #{j.id}</p>
        </div>
      ),
    },
    {
      header: 'Status',
      accessor: (j) => (
        <Badge variant={j.status === 'completed' ? 'success' : (j.status === 'processing' ? 'warning' : 'danger')}>
          {j.status}
        </Badge>
      ),
    },
    {
      header: 'Processed / Total',
      accessor: (j) => (
        <span className="text-xs font-mono">
          {j.processed_items} / {j.total_items}
        </span>
      ),
    },
    {
      header: 'CPU Runtime',
      accessor: (j) => <span className="text-xs font-mono text-slate-400">{j.cpu_time_ms ? `${j.cpu_time_ms}ms` : '—'}</span>,
    },
    {
      header: 'Peak Memory',
      accessor: (j) => (
        <span className="text-xs font-mono text-slate-400">
          {j.memory_peak_bytes ? `${(j.memory_peak_bytes / 1024 / 1024).toFixed(1)} MB` : '—'}
        </span>
      ),
    },
    {
      header: 'Executed At',
      accessor: (j) => <span className="text-xs text-slate-400">{new Date(j.created_at).toLocaleString()}</span>,
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Automation & Cron Ingestion</h2>
          <p className="text-xs text-slate-400 mt-1">CPU-safe, rate-limited, bounded execution telemetry.</p>
        </div>
        <div className="flex items-center gap-3">
          <Button
            variant="primary"
            onClick={handleTriggerBatch}
            isLoading={isRunningBatch}
            icon={<Play className="w-3.5 h-3.5 fill-current" />}
          >
            Trigger Bounded Batch
          </Button>
          <Button
            variant="secondary"
            onClick={fetchJobs}
            icon={<RefreshCw className="w-3.5 h-3.5" />}
          >
            Refresh
          </Button>
        </div>
      </div>

      <DataTable
        columns={columns}
        data={jobs}
        isLoading={isLoading}
        emptyTitle="No automation batches run yet."
        emptyDescription="Executed automation jobs will register memory, runtime, and item counters here."
      />
    </div>
  );
};
