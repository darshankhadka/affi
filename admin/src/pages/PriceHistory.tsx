import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { DataTable, Column } from '../components/ui/DataTable';
import { Card } from '../components/ui/Card';
import { History } from 'lucide-react';

export const PriceHistory: React.FC = () => {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Price History Telemetry</h2>
        <p className="text-xs text-slate-400 mt-1">Snapshot logs of retailer price drop trends and stock changes.</p>
      </div>

      <DataTable
        columns={[
          { header: 'Date Recorded', accessor: 'recorded_at' },
          { header: 'Product', accessor: 'product_name' },
          { header: 'Retailer', accessor: 'retailer_name' },
          { header: 'Price', accessor: 'price' },
        ]}
        data={[]}
        emptyTitle="No historical price shifts logged yet."
        emptyDescription="Price snapshots are automatically logged when retailer prices or stock availability change."
      />
    </div>
  );
};
