import React from 'react';
import { Card } from '../components/ui/Card';
import { DataTable } from '../components/ui/DataTable';

export const AffiliatePerformance: React.FC = () => {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Affiliate Conversion & Performance</h2>
        <p className="text-xs text-slate-400 mt-1">Retailer outbound click volume, conversion telemetry, and revenue.</p>
      </div>

      <DataTable
        columns={[
          { header: 'Date', accessor: 'date' },
          { header: 'Retailer', accessor: 'retailer' },
          { header: 'Outbound Clicks', accessor: 'clicks' },
          { header: 'Est. Earnings', accessor: 'earnings' },
        ]}
        data={[]}
        emptyTitle="No affiliate performance data recorded yet."
        emptyDescription="Performance analytics will display real click traffic and conversion events as visitors use the platform."
      />
    </div>
  );
};
