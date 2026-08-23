import React, { useState } from 'react';
import api from '../services/api';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { FormField } from '../components/ui/FormField';
import { Badge } from '../components/ui/Badge';
import { GitMerge, CheckCircle, XCircle, Search } from 'lucide-react';

export const ProductMatching: React.FC = () => {
  const [identifierType, setIdentifierType] = useState('UPC');
  const [identifierValue, setIdentifierValue] = useState('');
  const [brandName, setBrandName] = useState('');
  const [modelNumber, setModelNumber] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [result, setResult] = useState<any | null>(null);

  const handleTestMatch = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setResult(null);

    try {
      const payload: any = {};
      if (identifierValue) {
        payload.identifiers = { [identifierType]: identifierValue };
      }
      if (brandName) payload.brand_name = brandName;
      if (modelNumber) payload.model_number = modelNumber;

      const res = await api.post('/admin/products/match', payload);
      setResult(res.data.data);
    } catch (err) {
      alert('Failed to execute matching pipeline.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-100 tracking-tight">Product Matching Pipeline</h2>
        <p className="text-xs text-slate-400 mt-1">
          O(1) indexed identifier matching (UPC, EAN, GTIN, ASIN, MPN) with zero full-table fuzzy overhead.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Input Card */}
        <Card>
          <h3 className="text-sm font-semibold text-slate-200 mb-4 flex items-center gap-2">
            <GitMerge className="w-4 h-4 text-emerald-400" />
            Test Ingestion Match Resolver
          </h3>

          <form onSubmit={handleTestMatch} className="space-y-4">
            <div className="grid grid-cols-3 gap-3">
              <FormField label="Identifier Type">
                <select
                  value={identifierType}
                  onChange={(e) => setIdentifierType(e.target.value)}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                >
                  <option value="UPC">UPC</option>
                  <option value="EAN">EAN</option>
                  <option value="GTIN">GTIN</option>
                  <option value="ASIN">ASIN</option>
                  <option value="MPN">MPN</option>
                </select>
              </FormField>

              <div className="col-span-2">
                <FormField label="Identifier Value">
                  <input
                    type="text"
                    placeholder="e.g. 195949123456 or B0CM5NXYZ1"
                    value={identifierValue}
                    onChange={(e) => setIdentifierValue(e.target.value)}
                    className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                  />
                </FormField>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <FormField label="Brand Name (Optional)">
                <input
                  type="text"
                  placeholder="e.g. Apple or AMD"
                  value={brandName}
                  onChange={(e) => setBrandName(e.target.value)}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>

              <FormField label="Model Number (Optional)">
                <input
                  type="text"
                  placeholder="e.g. MRX33LL/A"
                  value={modelNumber}
                  onChange={(e) => setModelNumber(e.target.value)}
                  className="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </FormField>
            </div>

            <Button type="submit" isLoading={isLoading} className="w-full mt-2">
              Run Match Pipeline
            </Button>
          </form>
        </Card>

        {/* Results Card */}
        <Card>
          <h3 className="text-sm font-semibold text-slate-200 mb-4">Pipeline Evaluation Result</h3>
          {result ? (
            <div className="space-y-4">
              <div className="flex items-center justify-between p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                <div className="flex items-center gap-3">
                  {result.matched ? (
                    <CheckCircle className="w-5 h-5 text-emerald-400" />
                  ) : (
                    <XCircle className="w-5 h-5 text-rose-400" />
                  )}
                  <div>
                    <h4 className="text-sm font-semibold text-slate-200">
                      {result.matched ? 'Canonical Match Found' : 'No Match Found'}
                    </h4>
                    <p className="text-xs text-slate-500">
                      Method: {result.match_type || 'None'} · Confidence: {(result.confidence * 100).toFixed(0)}%
                    </p>
                  </div>
                </div>
                <Badge variant={result.matched ? 'success' : 'danger'}>
                  {result.matched ? 'MATCHED' : 'UNMATCHED'}
                </Badge>
              </div>

              {result.product && (
                <div className="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                  <span className="text-xs font-semibold text-emerald-400 uppercase tracking-wider">
                    Resolved Product Target
                  </span>
                  <h4 className="font-bold text-slate-100">{result.product.name}</h4>
                  <p className="text-xs text-slate-400">Brand: {result.product.brand?.name || '—'}</p>
                  <p className="text-xs text-slate-400 font-mono">Slug: {result.product.slug}</p>
                </div>
              )}
            </div>
          ) : (
            <div className="p-12 text-center text-slate-500 text-xs">
              Enter identifiers on the left and run the match resolver to evaluate canonical resolution.
            </div>
          )}
        </Card>
      </div>
    </div>
  );
};
