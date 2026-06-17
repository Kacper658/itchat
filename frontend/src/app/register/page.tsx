'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { api, setToken } from '../../lib/api';

export default function RegisterPage() {
  const router = useRouter();
  const [form, setForm] = useState({
    name: '', email: '', password: '', password_confirmation: '',
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Pobranie rozmowy gościa z LocalStorage — do przepisania na konto
  function getGuestChat() {
    try {
      const data = JSON.parse(localStorage.getItem('guest_chat') || '[]');
      if (!Array.isArray(data) || data.length === 0) return undefined;
      return {
        title: 'Rozmowa z gościa',
        messages: data.filter((m: any) => m.role === 'user' || m.role === 'assistant'),
      };
    } catch {
      return undefined;
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const guestChat = getGuestChat();
      const { token } = await api.register({ ...form, guest_chat: guestChat });
      setToken(token);

      // Wyczyść chat gościa (został przeniesiony na konto)
      localStorage.removeItem('guest_chat');

      router.push('/chat');
    } catch (err: any) {
      setError(err.message || 'Błąd rejestracji');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <div className="w-full max-w-md">
        <Link href="/" className="mb-8 block text-center text-2xl font-bold text-brand-600">
          IT<span className="text-slate-900">Expert</span>
        </Link>
        <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
          <h1 className="mb-2 text-2xl font-bold">Rejestracja</h1>
          <p className="mb-6 text-sm text-slate-500">
            Załóż konto, aby kontynuować rozmowę z ekspertem IT.
          </p>

          {error && (
            <div className="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-600">{error}</div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium">Imię / Nazwa</label>
              <input
                type="text" required value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                className="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">E-mail</label>
              <input
                type="email" required value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                className="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Hasło (min. 8 znaków)</label>
              <input
                type="password" required value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
                className="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Powtórz hasło</label>
              <input
                type="password" required value={form.password_confirmation}
                onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
                className="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
              />
            </div>
            <button
              type="submit" disabled={loading}
              className="w-full rounded-lg bg-brand-600 px-4 py-3 font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
            >
              {loading ? 'Rejestrowanie...' : 'Załóż konto'}
            </button>
          </form>

          <p className="mt-6 text-center text-sm text-slate-600">
            Masz już konto?{' '}
            <Link href="/login" className="font-semibold text-brand-600 hover:underline">
              Zaloguj się
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}
