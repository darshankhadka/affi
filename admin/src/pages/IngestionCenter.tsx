import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { StatCard } from '../components/ui/StatCard';
import { FormField } from '../components/ui/FormField';
import { Modal } from '../components/ui/Modal';
import {
  Database,
  Play,
  RefreshCw,
  Layers,
  Network,
  ShoppingCart,
  CheckCircle2,
  AlertTriangle,
  Clock,
  ArrowRight,
  Sparkles,
  Search,
} from 'lucide-react';

export const IngestionCenter: React.FC = () => {
  const [metrics, setMetrics] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);

  // CJ Bulk Ingestion Form State
  const [cjMarket, setCjMarket] = useState('us');
  const [cjMax, setCjMax] = useState(50);
  const [cjPartnerId, setCjPartnerId] = useState('');
  const [isCjRunning, setIsCjRunning] = useState(false);
  const [cjResult, setCjResult] = useState<any | null>(null);

  // Awin Bulk Ingestion Form State
  const [awinMarket, setAwinMarket] = useState('de');
  const [awinAdvertiserId, setAwinAdvertiserId] = useState('25962'); // BlazeVideo DE
  const [awinMax, setAwinMax] = useState(50);
  const [isAwinRunning, setIsAwinRunning] = useState(false);
  const [awinResult, setAwinResult] = useState<any | null>(null);

  // Selected Job Details Modal
  const [selectedJob, setSelectedJob] = useState<any | null>(null);

  const fetchStatus = async () => {
    setIsRefreshing(true);
    try {
      const res = await api.get('/admin/ingestion/status');
      setMetrics(res.data.data);
    } catch (err) {
      console.error('Failed to fetch ingestion metrics', err);
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  };

  useEffect(() => {
    fetchStatus();
  }, []);

  const handleStartCj = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsCjRunning(true);
    setCjResult(null);

    try {
      const res = await api.post('/admin/ingestion/start', {
        provider: 'cj',
        market: cjMarket,
        max_products: cjMax,
        partner_id: cjPartnerId || undefined,
      });
      setCjResult(res.data.data);
      fetchStatus();
    } catch (err: any) {
      alert(err.response?.data?.message || 'CJ Ingestion failed.');
    } finally {
      setIsCjRunning(false);
    }
  };

  const handleStartAwin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsAwinRunning(true);
    setAwinResult(null);

    try {
      const res = await api.post('/admin/ingestion/start', {
        provider: 'awin',
        market: awinMarket,
        advertiser_id: parseInt(awinAdvertiserId, 10),
        max_products: awinMax,
      });
      setAwinResult(res.data.data);
      fetchStatus();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Awin Ingestion failed.');
    } finally {
      setIsAwinRunning(false);
    }
  };

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-slate-100 tracking-tight flex items-center gap-2.5">
            <Database className="w-6 h-6 text-emerald-400" />
            Catalog Ingestion & Bulk Operations Engine
          </h2>
          <p className="text-xs text-slate-400 mt-1">
            Resumable, high-volume product pipelines across CJ Affiliate, Awin Create-a-Feed, and Amazon Mode 1.
          </p>
        </div>
        <Button
          variant="secondary"
          size="sm"
          onClick={fetchStatus}
          isLoading={isRefreshing}
          icon={<RefreshCw className="w-3.5 h-3.5" />}
        >
          Refresh Telemetry
        </Button>
      </div>

      {/* Live KPI Metric Grid */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <StatCard
          title="Canonical Products"
          value={metrics?.total_products ?? 0}
          subtitle={`${metrics?.published_products ?? 0} published`}
          icon={<Layers className="w-4 h-4" />}
        />
        <StatCard
          title="Total Store Offers"
          value={metrics?.total_offers ?? 0}
          subtitle={`${metrics?.active_offers ?? 0} active & in-stock`}
          icon={<ShoppingCart className="w-4 h-4" />}
        />
        <StatCard
          title="CJ Products Imported"
          value={metrics?.provider_offers?.cj ?? 0}
          subtitle="Joined GraphQL partners"
          icon={<Network className="w-4 h-4" />}
        />
        <StatCard
          title="Awin Products Streamed"
          value={metrics?.provider_offers?.awin ?? 0}
          subtitle="Approved Datafeeds"
          icon={<Sparkles className="w-4 h-4" />}
        />
      </div>

      {/* Provider Bulk Ingestion Control Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* CJ Affiliate Bulk Pipeline */}
        <Card className="space-y-4">
          <div className="flex items-center justify-between border-b border-slate-800 pb-3">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-xl bg-slate-900 text-emerald-400 border border-slate-800">
                <Network className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-sm font-bold text-slate-200">Commission Junction (CJ) Pipeline</h3>
                <p className="text-[11px] text-slate-500 font-mono">GraphQL ads.api.cj.com · PartnerStatus: JOINED</p>
              </div>
            </div>
            <Badge variant="success" dot>Operational</Badge>
          </div>

          <form onSubmit={handleStartCj} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Target Market">
                <select
                  value={cjMarket}
                  onChange={(e) => setCjMarket(e.target.value)}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                >
                  <option value="us">United States (USD)</option>
                  <option value="gb">United Kingdom (GBP)</option>
                  <option value="de">Germany (EUR)</option>
                  <option value="fr">France (EUR)</option>
                  <option value="au">Australia (AUD)</option>
                </select>
              </FormField>

              <FormField label="Batch Target (Max Products)">
                <input
                  type="number"
                  min={1}
                  max={250}
                  value={cjMax}
                  onChange={(e) => setCjMax(Number(e.target.value))}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
            </div>

            <FormField label="Optional Specific Partner ID (Leave blank for all joined)">
              <input
                type="text"
                placeholder="e.g. 5566778"
                value={cjPartnerId}
                onChange={(e) => setCjPartnerId(e.target.value)}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </FormField>

            {cjResult && (
              <div className="p-3 bg-slate-950 border border-emerald-900/50 rounded-xl text-xs space-y-1 font-mono text-slate-300">
                <div className="flex items-center gap-2 text-emerald-400 font-semibold">
                  <CheckCircle2 className="w-3.5 h-3.5" /> CJ Bulk Batch Completed ({cjResult.latency_ms}ms)
                </div>
                <p>Imported: <span className="text-emerald-400 font-bold">{cjResult.imported}</span> (Created: {cjResult.created} · Matched: {cjResult.matched})</p>
                <p className="text-slate-500">Discovered in feed: {cjResult.discovered} | Skipped: {cjResult.skipped} | Failed: {cjResult.failed}</p>
              </div>
            )}

            <div className="flex justify-end pt-2">
              <Button
                type="submit"
                isLoading={isCjRunning}
                icon={<Play className="w-3.5 h-3.5" />}
              >
                Execute CJ Bulk Ingestion
              </Button>
            </div>
          </form>
        </Card>

        {/* Awin Streaming Datafeed Pipeline */}
        <Card className="space-y-4">
          <div className="flex items-center justify-between border-b border-slate-800 pb-3">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-xl bg-slate-900 text-teal-400 border border-slate-800">
                <Sparkles className="w-4 h-4" />
              </div>
              <div>
                <h3 className="text-sm font-bold text-slate-200">Awin Create-a-Feed Streaming Pipeline</h3>
                <p className="text-[11px] text-slate-500 font-mono">productdata.awin.com · GZIP Decompression</p>
              </div>
            </div>
            <Badge variant="success" dot>Operational</Badge>
          </div>

          <form onSubmit={handleStartAwin} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <FormField label="Target Approved Advertiser">
                <select
                  value={awinAdvertiserId}
                  onChange={(e) => setAwinAdvertiserId(e.target.value)}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                >
                  <option value="25962">BlazeVideo DE (DE · EUR · 5.65%)</option>
                  <option value="8800">mcdaekonline DK (DK · DKK · 3.03%)</option>
                  <option value="57897">Geekbuying DE (DE · EUR · 3.66%)</option>
                  <option value="75408">Nothingprojector (Global · USD · 1.25%)</option>
                  <option value="90211">Fast Technology (Global · USD · 1.19%)</option>
                </select>
              </FormField>

              <FormField label="Target Market">
                <select
                  value={awinMarket}
                  onChange={(e) => setAwinMarket(e.target.value)}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                >
                  <option value="de">Germany (DE · EUR)</option>
                  <option value="dk">Denmark (DK · DKK)</option>
                  <option value="us">United States (US · USD)</option>
                  <option value="gb">United Kingdom (GB · GBP)</option>
                </select>
              </FormField>
            </div>

            <FormField label="Stream Target (Max Products)">
              <input
                type="number"
                min={1}
                max={250}
                value={awinMax}
                onChange={(e) => setAwinMax(Number(e.target.value))}
                className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
              />
            </FormField>

            {awinResult && (
              <div className="p-3 bg-slate-950 border border-teal-900/50 rounded-xl text-xs space-y-1 font-mono text-slate-300">
                <div className="flex items-center gap-2 text-teal-400 font-semibold">
                  <CheckCircle2 className="w-3.5 h-3.5" /> Awin Stream Completed ({awinResult.latency_ms}ms)
                </div>
                <p>Imported: <span className="text-teal-400 font-bold">{awinResult.imported}</span> (Created: {awinResult.created} · Matched: {awinResult.matched})</p>
                <p className="text-slate-500">Rows Examined: {awinResult.discovered} | Skipped: {awinResult.skipped} | Failed: {awinResult.failed}</p>
              </div>
            )}

            <div className="flex justify-end pt-2">
              <Button
                type="submit"
                isLoading={isAwinRunning}
                icon={<Play className="w-3.5 h-3.5" />}
              >
                Execute Awin Stream Ingestion
              </Button>
            </div>
          </form>
        </Card>
      </div>

      {/* Amazon Dual-Mode Status Card */}
      <Card className="space-y-3">
        <div className="flex items-center justify-between border-b border-slate-800 pb-3">
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-xl bg-slate-900 text-amber-400 border border-slate-800">
              <ShoppingCart className="w-4 h-4" />
            </div>
            <div>
              <h3 className="text-sm font-bold text-slate-200">Amazon Associates Integration</h3>
              <p className="text-[11px] text-slate-500 font-mono">Dual-Mode: Mode 1 (Active Manual URL Import) | Mode 2 (Dormant PA-API)</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="neutral">Mode 2: NOT CONFIGURED / NOT ELIGIBLE</Badge>
            <Badge variant="success">Mode 1: Active</Badge>
          </div>
        </div>
        <p className="text-xs text-slate-400 leading-relaxed">
          Amazon Mode 1 operates via URL/ASIN parsing with automated associate tag injection and canonical matching (zero HTML scraping). Mode 2 PA-API is dormant until eligible.
        </p>
      </Card>

      {/* Recent Ingestion Batch Jobs */}
      <div className="space-y-3">
        <h3 className="text-base font-bold text-slate-200 flex items-center gap-2">
          <Clock className="w-4 h-4 text-emerald-400" />
          Recent Ingestion Runs & Telemetry Logs
        </h3>

        <div className="space-y-2">
          {(!metrics?.recent_jobs || metrics.recent_jobs.length === 0) ? (
            <Card className="text-center py-8 text-xs text-slate-500">
              No recent bulk ingestion jobs executed.
            </Card>
          ) : (
            metrics.recent_jobs.map((job: any) => (
              <div
                key={job.id}
                onClick={() => setSelectedJob(job)}
                className="p-3.5 bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-xl flex items-center justify-between text-xs cursor-pointer transition-colors"
              >
                <div className="flex items-center gap-3">
                  <div className="p-1.5 rounded-lg bg-slate-950 text-slate-400 font-mono text-[10px]">
                    #{job.id}
                  </div>
                  <div>
                    <span className="font-semibold text-slate-200">{job.batch_type}</span>
                    <p className="text-[11px] text-slate-500 font-mono">
                      Provider: {job.provider?.name || 'Bulk'} · Market: {job.market?.code?.toUpperCase() || 'US'} · {job.processed_items} items processed
                    </p>
                  </div>
                </div>

                <div className="flex items-center gap-3">
                  <span className="text-[11px] text-slate-500 font-mono hidden sm:inline">
                    {new Date(job.started_at).toLocaleTimeString()}
                  </span>
                  <Badge variant={job.status === 'completed' ? 'success' : (job.status === 'processing' ? 'warning' : 'danger')}>
                    {job.status}
                  </Badge>
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      {/* Job Details Modal */}
      <Modal
        isOpen={!!selectedJob}
        onClose={() => setSelectedJob(null)}
        title={`Ingestion Run #${selectedJob?.id}`}
        maxWidth="md"
      >
        {selectedJob && (
          <div className="space-y-4 text-xs font-mono text-slate-300">
            <div className="p-3 bg-slate-950 rounded-xl space-y-1.5 border border-slate-800">
              <p><span className="text-slate-500">Type:</span> {selectedJob.batch_type}</p>
              <p><span className="text-slate-500">Status:</span> {selectedJob.status}</p>
              <p><span className="text-slate-500">Processed Items:</span> {selectedJob.processed_items}</p>
              <p><span className="text-slate-500">Failed Items:</span> {selectedJob.failed_items}</p>
              <p><span className="text-slate-500">Started:</span> {new Date(selectedJob.started_at).toLocaleString()}</p>
              {selectedJob.finished_at && (
                <p><span className="text-slate-500">Finished:</span> {new Date(selectedJob.finished_at).toLocaleString()}</p>
              )}
            </div>

            {selectedJob.error_log && (
              <div>
                <h4 className="text-slate-400 font-semibold mb-1">Error Logs:</h4>
                <div className="p-3 bg-rose-950/40 border border-rose-900/50 rounded-xl text-rose-300 max-h-40 overflow-y-auto whitespace-pre-wrap">
                  {selectedJob.error_log}
                </div>
              </div>
            )}

            <div className="flex justify-end pt-2">
              <Button variant="secondary" onClick={() => setSelectedJob(null)}>
                Close
              </Button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
};
