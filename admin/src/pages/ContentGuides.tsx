import React from 'react';
import { Card } from '../components/ui/Card';
import { EmptyState } from '../components/ui/EmptyState';
import { BookOpen } from 'lucide-react';

export const ContentGuides: React.FC = () => {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Editorial Buying Guides & Deals</h2>
        <p className="text-xs text-slate-400 mt-1">Curated buying guides, technology comparisons, and deal roundups.</p>
      </div>

      <Card>
        <EmptyState
          icon={<BookOpen className="w-12 h-12 text-slate-500" />}
          title="No editorial guides published yet."
          description="Editorial content and buying guides can be drafted here to drive long-tail SEO traffic."
        />
      </Card>
    </div>
  );
};
