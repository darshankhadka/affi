import React from 'react';
import { Metadata } from 'next';
import { Mail, MessageSquare, Building2, HelpCircle } from 'lucide-react';

interface ContactPageProps {
  params: Promise<{ market: string }>;
}

export async function generateStaticParams() {
  return [
    { market: 'us' },
    { market: 'uk' },
    { market: 'de' },
    { market: 'fr' },
    { market: 'es' },
    { market: 'it' },
    { market: 'nl' },
    { market: 'au' },
    { market: 'nz' },
  ];
}

export const metadata: Metadata = {
  title: 'Contact Us | ARIKARTECH',
  description: 'Get in touch with the ARIKARTECH engineering, merchant partnerships, and editorial team.',
};

export default async function ContactPage({ params }: ContactPageProps) {
  await params;

  return (
    <div className="max-w-4xl mx-auto space-y-12 py-4">
      <div className="space-y-4 text-center">
        <span className="px-3.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-bold uppercase tracking-wider">
          Support & Partnerships
        </span>
        <h1 className="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight">
          Contact ARIKARTECH
        </h1>
        <p className="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
          Questions about hardware specifications, retailer integration, or data corrections? Reach out to our team.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-4">
          <div className="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700">
            <Building2 className="w-5 h-5" />
          </div>
          <h2 className="text-lg font-bold text-slate-900">Retailer & Merchant Partnerships</h2>
          <p className="text-xs text-slate-600 leading-relaxed">
            Authorized technology retailers interested in having their product feeds indexed on ARIKARTECH can submit their affiliate network details.
          </p>
          <div className="pt-2 text-xs font-mono text-emerald-800 font-semibold">
            merchants@arikartech.com
          </div>
        </div>

        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-4">
          <div className="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700">
            <HelpCircle className="w-5 h-5" />
          </div>
          <h2 className="text-lg font-bold text-slate-900">Catalog Corrections & Feedback</h2>
          <p className="text-xs text-slate-600 leading-relaxed">
            Spotted an incorrect technical specification or misclassified product? Report catalog anomalies directly to our data team.
          </p>
          <div className="pt-2 text-xs font-mono text-emerald-800 font-semibold">
            corrections@arikartech.com
          </div>
        </div>
      </div>
    </div>
  );
}
