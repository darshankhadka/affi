import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { MarketProvider } from './contexts/MarketContext';
import { AdminLayout } from './components/layout/AdminLayout';
import { Login } from './pages/Login';
import { Dashboard } from './pages/Dashboard';
import { Products } from './pages/Products';
import { ProductDetail } from './pages/ProductDetail';
import { Categories } from './pages/Categories';
import { Brands } from './pages/Brands';
import { ProductMatching } from './pages/ProductMatching';
import { Offers } from './pages/Offers';
import { BestPrices } from './pages/BestPrices';
import { PriceHistory } from './pages/PriceHistory';
import { AffiliateProviders } from './pages/AffiliateProviders';
import { Retailers } from './pages/Retailers';
import { AffiliatePerformance } from './pages/AffiliatePerformance';
import { AnalyticsTraffic } from './pages/AnalyticsTraffic';
import { SearchOverview } from './pages/SearchOverview';
import { ContentGuides } from './pages/ContentGuides';
import { SeoOverview } from './pages/SeoOverview';
import { AutomationStatus } from './pages/AutomationStatus';
import { IngestionCenter } from './pages/IngestionCenter';
import { SettingsGeneral } from './pages/SettingsGeneral';
import { UsersAndRoles } from './pages/UsersAndRoles';

export const App: React.FC = () => {
  return (
    <AuthProvider>
      <MarketProvider>
        <Router>
          <Routes>
            <Route path="/login" element={<Login />} />
            
            <Route path="/" element={<AdminLayout />}>
              <Route index element={<Dashboard />} />
              
              {/* Catalog */}
              <Route path="catalog/products" element={<Products />} />
              <Route path="catalog/products/:id" element={<ProductDetail />} />
              <Route path="catalog/categories" element={<Categories />} />
              <Route path="catalog/brands" element={<Brands />} />
              <Route path="catalog/matching" element={<ProductMatching />} />
              
              {/* Offers */}
              <Route path="offers" element={<Offers />} />
              <Route path="offers/best-prices" element={<BestPrices />} />
              <Route path="offers/history" element={<PriceHistory />} />
              
              {/* Affiliates */}
              <Route path="affiliates/providers" element={<AffiliateProviders />} />
              <Route path="affiliates/retailers" element={<Retailers />} />
              <Route path="affiliates/performance" element={<AffiliatePerformance />} />
              
              {/* Analytics */}
              <Route path="analytics/traffic" element={<AnalyticsTraffic />} />
              <Route path="analytics/clicks" element={<AffiliatePerformance />} />
              <Route path="analytics/revenue" element={<AffiliatePerformance />} />
              
              {/* Search */}
              <Route path="search" element={<SearchOverview />} />
              
              {/* Content */}
              <Route path="content/guides" element={<ContentGuides />} />
              
              {/* SEO */}
              <Route path="seo" element={<SeoOverview />} />
              
              {/* Automation & Ingestion */}
              <Route path="automation" element={<AutomationStatus />} />
              <Route path="automation/ingestion" element={<IngestionCenter />} />
              
              {/* Settings */}
              <Route path="settings" element={<SettingsGeneral />} />
              <Route path="settings/users" element={<UsersAndRoles />} />
            </Route>

            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </Router>
      </MarketProvider>
    </AuthProvider>
  );
};
