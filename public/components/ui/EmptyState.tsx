import React from 'react';
import { PackageOpen } from 'lucide-react';
import Link from 'next/link';

interface EmptyStateProps {
  icon?: React.ReactNode;
  title?: string;
  description?: string;
  actionHref?: string;
  actionText?: string;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
  icon = <PackageOpen className="w-10 h-10 text-slate-400" />,
  title = 'No products available yet.',
  description = 'There are currently no products or offers matching this criteria.',
  actionHref,
  actionText,
}) => {
  return (
    <div className="flex flex-col items-center justify-center p-12 text-center rounded-2xl border border-dashed border-slate-300 bg-white my-8 shadow-xs">
      <div className="p-4 bg-slate-50 rounded-2xl mb-4 border border-slate-200 text-slate-500">
        {icon}
      </div>
      <h3 className="text-base font-semibold text-slate-900 mb-1">{title}</h3>
      <p className="text-xs text-slate-500 max-w-sm mb-6">{description}</p>
      {actionHref && actionText && (
        <Link
          href={actionHref}
          className="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-xs"
        >
          {actionText}
        </Link>
      )}
    </div>
  );
};
