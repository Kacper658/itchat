'use client';

import Link from 'next/link';

interface Props {
  onClose: () => void;
}

export function PaywallModal({ onClose }: Props) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div className="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-2xl">
        {/* Ikona */}
        <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-brand-100">
          <svg className="h-8 w-8 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>

        <h2 className="mb-3 text-2xl font-bold text-slate-900">
          Opłać aby kontynuować rozmowę
        </h2>
        <p className="mb-6 text-slate-600">
          Wykorzystałeś limit darmowych wiadomości. Załóż konto i doładuj saldo,
          aby kontynuować rozmowę z ekspertem IT.
        </p>

        <div className="space-y-3">
          <Link
            href="/register"
            className="block w-full rounded-xl bg-brand-600 px-6 py-4 font-bold text-white hover:bg-brand-700"
          >
            Załóż konto
          </Link>
          <button
            onClick={onClose}
            className="block w-full rounded-xl border border-slate-300 px-6 py-3 font-medium text-slate-600 hover:bg-slate-50"
          >
            Anuluj
          </button>
        </div>

        <p className="mt-4 text-xs text-slate-400">
          Twoja rozmowa zostanie zachowana po rejestracji.
        </p>
      </div>
    </div>
  );
}
