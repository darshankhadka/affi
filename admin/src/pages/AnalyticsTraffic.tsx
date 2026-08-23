import React from 'react';
import { Card } from '../components/ui/Card';
import { EmptyState } from '../components/ui/EmptyState';
import { BarChart3 } from 'lucide-react';

export const AnalyticsTraffic: React.FC = () => {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Traffic Analytics</h2>
        <p className="text-xs text-slate-400 mt-1">Google Analytics 4 telemetry and organic search discovery trends.</p>
      </div>

      <Card>
        <EmptyState
          icon={<BarChart3 className="w-12 h-12 text-slate-500" />}
          title="No traffic recorded yet."
          description="Traffic metrics and GA4 event pipelines will populate as organic visitors discover catalog pages."
        />
      </Card>
    </div>
  );
};
