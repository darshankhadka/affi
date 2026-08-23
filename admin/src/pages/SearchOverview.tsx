import React from 'react';
import { Card } from '../components/ui/Card';
import { DataTable } from '../components/ui/DataTable';

export const SearchOverview: React.FC = () => {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Search & Discovery Intelligence</h2>
        <p className="text-xs text-slate-400 mt-1">Popular visitor searches and zero-result queries to identify catalog expansion gaps.</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <h3 className="text-sm font-semibold text-slate-200 mb-4">Top User Queries</h3>
          <DataTable
            columns={[{ header: 'Query', accessor: 'query' }, { header: 'Hits', accessor: 'count' }]}
            data={[]}
            emptyTitle="No search queries logged yet."
          />
        </Card>

        <Card>
          <h3 className="text-sm font-semibold text-slate-200 mb-4">Zero-Result Queries (Opportunities)</h3>
          <DataTable
            columns={[{ header: 'Query', accessor: 'query' }, { header: 'Searches', accessor: 'count' }]}
            data={[]}
            emptyTitle="No zero-result searches logged yet."
          />
        </Card>
      </div>
    </div>
  );
};
