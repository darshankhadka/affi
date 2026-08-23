import React from 'react';
import { Card } from './Card';
import clsx from 'clsx';

interface StatCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon: React.ReactNode;
  trend?: {
    value: string;
    isPositive: boolean;
  };
  highlight?: boolean;
}

export const StatCard: React.FC<StatCardProps> = ({
  title,
  value,
  subtitle,
  icon,
  trend,
  highlight = false,
}) => {
  return (
    <Card className={clsx('relative overflow-hidden', highlight && 'border-emerald-500/30 bg-emerald-950/10')}>
      <div className="flex items-start justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">{title}</p>
          <h4 className="text-3xl font-bold text-slate-100 tracking-tight">{value}</h4>
          {subtitle && <p className="text-xs text-slate-400 mt-1">{subtitle}</p>}
        </div>
        <div className="p-3 bg-slate-800/80 rounded-xl border border-slate-700/60 text-emerald-400">
          {icon}
        </div>
      </div>
      {trend && (
        <div className="mt-4 pt-3 border-t border-slate-800/60 flex items-center gap-1.5 text-xs">
          <span className={trend.isPositive ? 'text-emerald-400 font-medium' : 'text-rose-400 font-medium'}>
            {trend.value}
          </span>
          <span className="text-slate-500">vs last period</span>
        </div>
      )}
    </Card>
  );
};
