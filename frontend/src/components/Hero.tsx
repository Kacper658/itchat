import Link from 'next/link';

export function Hero() {
  return (
    <section className="relative overflow-hidden bg-gradient-to-br from-brand-50 to-white">
      <div className="mx-auto max-w-6xl px-4 py-24 text-center">
        <h1 className="mb-6 text-4xl font-extrabold leading-tight text-slate-900 md:text-6xl">
          Ekspert informatyczny <br />
          <span className="text-brand-600">dostępny 24/7</span>
        </h1>
        <p className="mx-auto mb-10 max-w-2xl text-lg text-slate-600">
          Uzyskaj profesjonalną pomoc IT od wirtualnego eksperta. Szybkie, dokładne
          odpowiedzi na każde pytanie techniczne.
        </p>
        <div className="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
          <Link
            href="/chat"
            className="rounded-xl bg-brand-600 px-8 py-4 text-lg font-bold text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-700"
          >
            Rozpocznij rozmowę →
          </Link>
          <span className="text-sm text-slate-500">Bez rejestracji • 2 darmowe wiadomości</span>
        </div>
      </div>

      {/* Dekoracja */}
      <div className="pointer-events-none absolute inset-0 -z-10">
        <div className="absolute left-1/4 top-0 h-72 w-72 -translate-x-1/2 rounded-full bg-brand-200/40 blur-3xl" />
        <div className="absolute right-0 top-40 h-96 w-96 rounded-full bg-brand-100/50 blur-3xl" />
      </div>
    </section>
  );
}
