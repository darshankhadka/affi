import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { DataTable, Column } from '../components/ui/DataTable';
import { 
  ArrowLeft, 
  ExternalLink, 
  Tag, 
  Layers, 
  ShieldCheck, 
  TrendingDown, 
  History, 
  Store,
  Laptop
} from 'lucide-react';

export const ProductDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [product, setProduct] = useState<any | null>(null);
  const [priceHistory, setPriceHistory] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const fetchProduct = async () => {
    setIsLoading(true);
    try {
      const [prodRes, histRes] = await Promise.all([
        api.get(`/admin/products/${id}`),
        api.get(`/products/${id}/price-history?days=90`).catch(() => ({ data: { data: [] } })),
      ]);
      setProduct(prodRes.data.data);
      setPriceHistory(histRes.data.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (id) {
      fetchProduct();
    }
  }, [id]);

  if (isLoading) {
    return (
      <div className="p-8 text-center text-slate-400">
        <div className="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-3" />
        <p className="text-xs">Loading product details...</p>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="p-8 text-center text-slate-400">
        <p className="text-sm font-semibold text-rose-400">Product not found.</p>
        <Link to="/catalog/products" className="text-xs text-emerald-400 hover:underline mt-2 inline-block">
          Back to Products
        </Link>
      </div>
    );
  }

  const offers = product.offers || [];
  const identifiers = product.identifiers || [];
  const specs = product.specifications || [];

  const offerColumns: Column<any>[] = [
    {
      header: 'Retailer',
      accessor: (o) => (
        <div className="flex items-center gap-2">
          <Store className="w-4 h-4 text-emerald-400" />
          <span className="font-semibold text-slate-200">{o.retailer?.name || 'Retailer'}</span>
        </div>
      ),
    },
    {
      header: 'Market',
      accessor: (o) => <span className="font-mono text-xs uppercase">{o.market?.code || 'US'}</span>,
    },
    {
      header: 'Price',
      accessor: (o) => (
        <span className="font-mono text-xs font-bold text-slate-100">
          {o.currency?.symbol || '$'}{Number(o.price).toFixed(2)}
        </span>
      ),
    },
    {
      header: 'Availability',
      accessor: (o) => (
        <Badge variant={o.availability === 'in_stock' ? 'success' : 'danger'}>
          {o.availability === 'in_stock' ? 'In Stock' : o.availability}
        </Badge>
      ),
    },
    {
      header: 'Direct Link',
      accessor: (o) => (
        <a
          href={o.affiliate_url}
          target="_blank"
          rel="noreferrer"
          className="text-xs text-emerald-400 hover:underline inline-flex items-center gap-1"
        >
          View Destination <ExternalLink className="w-3 h-3" />
        </a>
      ),
    },
  ];

  const historyColumns: Column<any>[] = [
    {
      header: 'Timestamp',
      accessor: (h) => <span className="text-xs font-mono">{new Date(h.recorded_at).toLocaleString()}</span>,
    },
    {
      header: 'Recorded Price',
      accessor: (h) => (
        <span className="text-xs font-bold font-mono text-emerald-400">
          {h.currency?.symbol || '$'}{Number(h.price).toFixed(2)}
        </span>
      ),
    },
    {
      header: 'Availability',
      accessor: (h) => <span className="text-xs capitalize">{h.availability}</span>,
    },
  ];

  return (
    <div className="space-y-6">
      {/* Top Header */}
      <div className="flex items-center justify-between">
        <Link
          to="/catalog/products"
          className="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-200 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Products Catalog
        </Link>
      </div>

      {/* Hero Overview */}
      <Card className="p-6">
        <div className="flex flex-col md:flex-row gap-6 items-start">
          <div className="w-32 h-32 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center shrink-0">
            {product.primary_image?.url ? (
              <img src={product.primary_image.url} alt={product.name} className="w-full h-full object-contain p-2" />
            ) : (
              <Laptop className="w-12 h-12 text-slate-600" />
            )}
          </div>

          <div className="space-y-3 flex-1">
            <div className="flex items-center gap-3">
              <Badge variant={product.status === 'published' ? 'success' : 'neutral'}>
                {product.status}
              </Badge>
              <span className="text-xs font-mono text-emerald-400 font-bold uppercase">{product.brand?.name}</span>
              {product.model_number && (
                <span className="text-xs font-mono text-slate-400">Model: {product.model_number}</span>
              )}
            </div>

            <h1 className="text-2xl font-black text-slate-100">{product.name}</h1>
            <p className="text-xs text-slate-400 font-mono">Slug: {product.slug}</p>
          </div>
        </div>
      </Card>

      {/* Identifiers Card */}
      <Card>
        <h3 className="text-sm font-semibold text-slate-200 mb-4 flex items-center gap-2">
          <ShieldCheck className="w-4 h-4 text-emerald-400" />
          Canonical Indexed Identifiers (O(1) Anti-Duplicate Index)
        </h3>
        {identifiers.length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            {identifiers.map((idItem: any) => (
              <div key={idItem.id} className="p-3 bg-slate-900 border border-slate-800 rounded-xl">
                <span className="text-[10px] uppercase font-bold text-slate-500 font-mono block">
                  {idItem.type}
                </span>
                <span className="text-xs font-mono font-bold text-slate-200">{idItem.value}</span>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-xs text-slate-500 italic">No structured identifiers registered.</p>
        )}
      </Card>

      {/* Offers Table */}
      <div className="space-y-3">
        <h3 className="text-base font-bold text-slate-100 flex items-center gap-2">
          <Store className="w-4 h-4 text-emerald-400" />
          Associated Retailer Offers ({offers.length})
        </h3>
        <DataTable
          columns={offerColumns}
          data={offers}
          emptyTitle="No retailer offers mapped yet."
          emptyDescription="Offers from Amazon and partner merchants will appear here when ingested or synchronized."
        />
      </div>

      {/* Price History Table */}
      <div className="space-y-3">
        <h3 className="text-base font-bold text-slate-100 flex items-center gap-2">
          <History className="w-4 h-4 text-emerald-400" />
          Historical Price Shift Snapshots
        </h3>
        <DataTable
          columns={historyColumns}
          data={priceHistory}
          emptyTitle="No price movements recorded yet."
          emptyDescription="Snapshots are recorded when price or availability changes during automated refresh batches."
        />
      </div>
    </div>
  );
};
