import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { DataTable, Column } from '../components/ui/DataTable';
import { Globe2, FileCode, CheckCircle, ExternalLink } from 'lucide-react';

export const SeoOverview: React.FC = () => {
  const [seoData, setSeoData] = useState<any | null>(null);
  const [redirects, setRedirects] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const fetchData = async () => {
    setIsLoading(true);
    try {
      const [seoRes, redRes] = await Promise.all([
        api.get('/admin/seo/overview'),
        api.get('/admin/seo/redirects'),
      ]);
      setSeoData(seoRes.data.data);
      setRedirects(redRes.data.data?.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">SEO Engine & Search Console</h2>
        <p className="text-xs text-slate-400 mt-1">XML sitemaps, robots.txt, Schema.org verification, and 301 redirects.</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
        <Card className="flex items-start gap-4">
          <div className="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
            <FileCode className="w-5 h-5" />
          </div>
          <div>
            <h4 className="text-xs font-semibold uppercase text-slate-400">Master Sitemap Index</h4>
            <p className="text-sm font-bold text-slate-200 mt-0.5">/sitemap.xml</p>
            <p className="text-[11px] text-emerald-400 mt-1 flex items-center gap-1">
              <CheckCircle className="w-3 h-3" /> Partitioned by Market
            </p>
          </div>
        </Card>

        <Card className="flex items-start gap-4">
          <div className="p-3 rounded-xl bg-sky-500/10 border border-sky-500/20 text-sky-400">
            <Globe2 className="w-5 h-5" />
          </div>
          <div>
            <h4 className="text-xs font-semibold uppercase text-slate-400">Robots Directives</h4>
            <p className="text-sm font-bold text-slate-200 mt-0.5">/robots.txt</p>
            <p className="text-[11px] text-sky-400 mt-1 flex items-center gap-1">
              <CheckCircle className="w-3 h-3" /> Production Crawl Rules Active
            </p>
          </div>
        </Card>

        <Card className="flex items-start gap-4">
          <div className="p-3 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400">
            <Globe2 className="w-5 h-5" />
          </div>
          <div>
            <h4 className="text-xs font-semibold uppercase text-slate-400">Structured Data</h4>
            <p className="text-sm font-bold text-slate-200 mt-0.5">Product & AggregateOffer</p>
            <p className="text-[11px] text-purple-400 mt-1 flex items-center gap-1">
              <CheckCircle className="w-3 h-3" /> Schema.org JSON-LD
            </p>
          </div>
        </Card>
      </div>

      <Card>
        <h3 className="text-sm font-semibold text-slate-200 mb-4">Custom URL Redirects</h3>
        <DataTable
          columns={[
            { header: 'Source Path', accessor: 'source_path' },
            { header: 'Target Path', accessor: 'target_path' },
            { header: 'Status Code', accessor: 'status_code' },
            { header: 'Hits', accessor: 'hit_count' },
          ]}
          data={redirects}
          isLoading={isLoading}
          emptyTitle="No redirects configured."
          emptyDescription="Custom 301 or 302 redirects will appear here."
        />
      </Card>
    </div>
  );
};
