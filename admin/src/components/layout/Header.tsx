import React from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { useMarket } from '../../contexts/MarketContext';
import { Globe, LogOut, Shield, User as UserIcon } from 'lucide-react';
import { Button } from '../ui/Button';

export const Header: React.FC = () => {
  const { user, logout } = useAuth();
  const { markets, selectedMarket, setSelectedMarket } = useMarket();

  return (
    <header className="h-16 shrink-0 bg-slate-950/70 backdrop-blur-md border-b border-slate-800/80 px-8 flex items-center justify-between sticky top-0 z-20">
      {/* Market Selector */}
      <div className="flex items-center gap-3">
        <div className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs">
          <Globe className="w-3.5 h-3.5 text-emerald-400" />
          <span className="text-slate-400 font-medium">Market:</span>
          <select
            value={selectedMarket?.code || ''}
            onChange={(e) => {
              const m = markets.find((item) => item.code === e.target.value);
              if (m) setSelectedMarket(m);
            }}
            className="bg-transparent text-slate-200 font-semibold focus:outline-none cursor-pointer"
          >
            {markets.map((m) => (
              <option key={m.code} value={m.code} className="bg-slate-900 text-slate-200">
                {m.name} ({m.code.toUpperCase()}) {m.is_active ? '' : '[Inactive]'}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* User profile & actions */}
      <div className="flex items-center gap-4">
        <div className="flex items-center gap-3 pl-4 border-l border-slate-800">
          <div className="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-300">
            <UserIcon className="w-4 h-4" />
          </div>
          <div className="text-left hidden sm:block">
            <p className="text-xs font-semibold text-slate-200 leading-none">{user?.name || 'Admin'}</p>
            <p className="text-[10px] text-emerald-400 font-mono mt-0.5 leading-none">
              {user?.roles?.[0] || 'Administrator'}
            </p>
          </div>
        </div>

        <Button
          variant="ghost"
          size="sm"
          onClick={() => logout()}
          className="text-slate-400 hover:text-rose-400"
          title="Sign out"
        >
          <LogOut className="w-4 h-4" />
        </Button>
      </div>
    </header>
  );
};
