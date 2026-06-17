import Link from 'next/link';
import { Hero } from '../components/Hero';

export default function LandingPage() {
  return (
    <div className="min-h-screen">
      {/* Navbar */}
      <nav className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
          <span className="text-xl font-bold text-brand-600">
            IT<span className="text-slate-900">Expert</span>
          </span>
          <div className="flex items-center gap-4">
            <Link href="/login" className="text-sm font-medium text-slate-600 hover:text-brand-600">
              Zaloguj się
            </Link>
            <Link
              href="/chat"
              className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
            >
              Rozpocznij
            </Link>
          </div>
        </div>
      </nav>

      <Hero />

      {/* Jak działa */}
      <section className="mx-auto max-w-6xl px-4 py-20">
        <h2 className="mb-12 text-center text-3xl font-bold">Jak to działa</h2>
        <div className="grid gap-8 md:grid-cols-3">
          {[
            { n: '1', t: 'Zadaj pytanie', d: 'Opisz swój problem informatyczny w prostym czacie.' },
            { n: '2', t: 'Otrzymaj odpowiedź', d: 'Wirtualny ekspert analizuje i podaje konkretne rozwiązanie.' },
            { n: '3', t: 'Załóż konto', d: 'Aby kontynuować, załóż konto i opłać wybrany plan.' },
          ].map((s) => (
            <div key={s.n} className="rounded-2xl border border-slate-200 bg-white p-8 text-center">
              <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-100 text-xl font-bold text-brand-600">
                {s.n}
              </div>
              <h3 className="mb-2 text-lg font-semibold">{s.t}</h3>
              <p className="text-sm text-slate-600">{s.d}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Dlaczego warto */}
      <section className="bg-slate-900 py-20 text-white">
        <div className="mx-auto max-w-6xl px-4">
          <h2 className="mb-12 text-center text-3xl font-bold">Dlaczego warto</h2>
          <div className="grid gap-8 md:grid-cols-3">
            {[
              { t: 'Wiedza ekspercka', d: 'Odpowiedzi na poziomie doświadczonego informatyka.' },
              { t: 'Natychmiastowa odpowiedź', d: 'Nie czekasz w kolejce — odpowiedź w kilka sekund.' },
              { t: 'Prywatność', d: 'Twoje dane są chronione zgodnie z RODO.' },
            ].map((f) => (
              <div key={f.t} className="rounded-2xl bg-slate-800 p-8">
                <h3 className="mb-2 text-lg font-semibold text-brand-100">{f.t}</h3>
                <p className="text-sm text-slate-300">{f.d}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* FAQ */}
      <section className="mx-auto max-w-3xl px-4 py-20">
        <h2 className="mb-12 text-center text-3xl font-bold">Najczęstsze pytania</h2>
        <div className="space-y-6">
          <div>
            <h3 className="mb-2 font-semibold">Czy pierwsze wiadomości są darmowe?</h3>
            <p className="text-slate-600">Tak, pierwsze dwie wiadomości są w pełni darmowe. Bez rejestracji.</p>
          </div>
          <div>
            <h3 className="mb-2 font-semibold">Jakie plany płatności są dostępne?</h3>
            <p className="text-slate-600">Posiadamy plany Free, Basic (per-ticket), Pro oraz Enterprise (miesięczne).</p>
          </div>
        </div>
      </section>

      {/* CTA końcowy */}
      <section className="bg-brand-600 py-16 text-center text-white">
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="mb-4 text-3xl font-bold">Gotowy na pomoc?</h2>
          <p className="mb-8 text-brand-100">Rozpocznij rozmowę z wirtualnym ekspertem IT teraz.</p>
          <Link
            href="/chat"
            className="inline-block rounded-xl bg-white px-8 py-4 text-lg font-bold text-brand-600 hover:bg-brand-50"
          >
            Rozpocznij rozmowę
          </Link>
        </div>
      </section>

      <footer className="border-t border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
        © 2026 IT Expert Chat. Wszystkie prawa zastrzeżone.
      </footer>
    </div>
  );
}
