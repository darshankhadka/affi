import React from 'react';
import { Metadata } from 'next';
import { Mail, MessageSquare, Building2 } from 'lucide-react';

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: 'Contact ARIKARTECH | Partnerships & Support',
    description: 'Get in touch with the ARIKARTECH engineering and retailer partnerships team.',
  };
}

export default async function ContactPage() {
  return (
    <div className="max-w-4xl mx-auto space-y-8">
      <div className="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200 shadow-sm space-y-4">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <Mail className="w-3.5 h-3.5" />
          <span>Support & Partnerships</span>
        </div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          Contact Us
        </h1>
        <p className="text-xs text-slate-500 max-w-2xl">
          Have feedback on a price index, or represent an authorized technology retailer interested in inventory feed integration? Reach out to our team.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
          <div className="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
            <Building2 className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-slate-900 text-base">Retailer Feeds & Partnerships</h3>
          <p className="text-xs text-slate-500 leading-relaxed">
            For affiliate network partnerships, merchant data feeds, and direct retailer indexing inquiries:
          </p>
          <div className="pt-2 font-mono text-xs font-semibold text-emerald-700">
            partnerships@arikartech.com
          </div>
        </div>

        <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
          <div className="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold">
            <MessageSquare className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-slate-900 text-base">General Inquiries & Data Corrections</h3>
          <p className="text-xs text-slate-500 leading-relaxed">
            For catalog corrections, pricing anomalies, or platform suggestions:
          </p>
          <div className="pt-2 font-mono text-xs font-semibold text-teal-700">
            support@arikartech.com
          </div>
        </div>
      </div>
    </div>
  );
}
