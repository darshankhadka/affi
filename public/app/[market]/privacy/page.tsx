import React from 'react';
import { Metadata } from 'next';
import { Shield, Lock, Eye, FileText } from 'lucide-react';

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: 'Privacy Policy | ARIKARTECH',
    description: 'ARIKARTECH privacy policy and data governance practices.',
  };
}

export default async function PrivacyPage() {
  return (
    <div className="max-w-4xl mx-auto space-y-8">
      <div className="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200 shadow-sm space-y-4">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <Shield className="w-3.5 h-3.5" />
          <span>Data Governance</span>
        </div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          Privacy Policy
        </h1>
        <p className="text-xs text-slate-500 font-mono">
          Last Updated: August 23, 2026
        </p>
      </div>

      <div className="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200 shadow-sm space-y-8 text-xs text-slate-600 leading-relaxed">
        <section className="space-y-3">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <Lock className="w-4 h-4 text-emerald-600" />
            1. Privacy-First Philosophy
          </h2>
          <p>
            ARIKARTECH respects your digital privacy. We operate as an informational price comparison platform and do not collect unnecessary Personally Identifiable Information (PII). We do not require visitors to register an account, enter credit card details, or provide personal billing information to use our discovery tools.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <Eye className="w-4 h-4 text-emerald-600" />
            2. IP Address Cryptographic Hashing
          </h2>
          <p>
            To monitor affiliate click attribution and prevent fraudulent bot traffic, our servers record outbound referral events. All incoming visitor IP addresses are immediately hashed using one-way SHA-256 cryptographic salts before being stored in our database. Raw IP addresses are never saved to disk or shared with third parties.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <FileText className="w-4 h-4 text-emerald-600" />
            3. Third-Party Retailer Links
          </h2>
          <p>
            Our website contains outbound referral links to third-party merchant retailers (e.g., Best Buy, Dell, Samsung, Currys). When you navigate to an external retailer site, their independent privacy policy and terms govern your transaction and data collection. We encourage reviewing the privacy policies of any retailer you visit.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-slate-900">
            4. Contact Data Protection Officer
          </h2>
          <p>
            For any inquiries regarding our privacy standards or data practices, contact our team at: <span className="font-mono font-semibold text-slate-800">privacy@arikartech.com</span>.
          </p>
        </section>
      </div>
    </div>
  );
}
