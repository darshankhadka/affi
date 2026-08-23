import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../services/api';

export interface Market {
  id: number;
  code: string;
  name: string;
  locale: string;
  hreflang: string;
  is_active: boolean;
  default_currency?: {
    code: string;
    symbol: string;
  };
}

interface MarketContextType {
  markets: Market[];
  selectedMarket: Market | null;
  setSelectedMarket: (market: Market) => void;
  isLoading: boolean;
  refreshMarkets: () => void;
}

const MarketContext = createContext<MarketContextType | undefined>(undefined);

export const MarketProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [markets, setMarkets] = useState<Market[]>([]);
  const [selectedMarket, setSelectedMarket] = useState<Market | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  const fetchMarkets = () => {
    setIsLoading(true);
    api.get('/markets')
      .then((res) => {
        const data = res.data.data;
        setMarkets(data);
        if (data.length > 0 && !selectedMarket) {
          const us = data.find((m: Market) => m.code === 'us') || data[0];
          setSelectedMarket(us);
        }
      })
      .catch((err) => console.error('Failed to load markets', err))
      .finally(() => setIsLoading(false));
  };

  useEffect(() => {
    fetchMarkets();
  }, []);

  return (
    <MarketContext.Provider value={{ markets, selectedMarket, setSelectedMarket, isLoading, refreshMarkets: fetchMarkets }}>
      {children}
    </MarketContext.Provider>
  );
};

export const useMarket = () => {
  const context = useContext(MarketContext);
  if (!context) {
    throw new Error('useMarket must be used within a MarketProvider');
  }
  return context;
};
