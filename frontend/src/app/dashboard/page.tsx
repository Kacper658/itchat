'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { api } from '../../lib/api';
import { formatMoney } from '../../lib/currency';

type Tab = 'profile' | 'balance' | 'subscription' | 'transactions' | 'settings';

export default function DashboardPage() {
  const router = useRouter();
  const [tab, setTab] = useState<Tab>('profile');
  const [user, setUser] = useState<any>(null);
  const [wallet, setWallet] = useState<any>(null);
  const [plans, setPlans] = useState<any[]>([]);
  const [transactions, setTransactions] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      router.push('/login');
      return;
    }
    loadData();
  }, []);

  async function loadData() {
    try {
      const [{ user: u }, w, p, txs] = await Promise.all([
        api.me(),
        api.wallet(),
        api.plans(),
        api.transactions(),
      ]);
      setUser(u);
      setWallet(w);
      setPlans(p);
      setTransactions(txs.data || txs);
    } catch {
      router.push('/login');
    } finally {
      setLoading(false);
    }
  }

  if (loading || !user) return <div className="flex min-h-screen items-center justify-center text-slate-400">Ładowanie...</div>;

  const tabs: { key: Tab; label: string }[] = [
    { key: 'profile', label: 'Profil' },
    { key: 'balance', label: 'Saldo' },
    { key: 'subscription', label: 'Subskrypcja' },
    { key: 'transactions', label: 'Historia płatności' },
    { key: 'settings', label: 'Ustawienia' },
  ];

  return (
    <div className="min-h-screen bg-slate-50">
      {/* Header */}
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
          <h1 className="text-xl font-bold text-brand-600">Panel użytkownika</h1>
          <div className="flex items-center gap-4">
            {user.role === 'admin' && (
              <a href="/admin" className="text-sm font-medium text-brand-600 hover:underline">Panel admina</a>
            )}
            <a href="/chat" className="text-sm font-medium text-slate-600 hover:underline">Czat</a>
          </div>
        </div>
      </header>

      <div className="mx-auto max-w-5xl px-4 py-8">
        {/* Tabs */}
        <div className="mb-8 flex gap-2 overflow-x-auto border-b border-slate-200">
          {tabs.map((t) => (
            <button
              key={t.key}
              onClick={() => setTab(t.key)}
              className={`whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium ${
                tab === t.key
                  ? 'border-brand-600 text-brand-600'
                  : 'border-transparent text-slate-500 hover:text-slate-700'
              }`}
            >
              {t.label}
            </button>
          ))}
        </div>

        {/* Profil */}
        {tab === 'profile' && (
          <Card>
            <h2 className="mb-4 text-lg font-semibold">Dane konta</h2>
            <dl className="space-y-2 text-sm">
              <Row label="Imię" value={user.name} />
              <Row label="E-mail" value={user.email} />
              <Row label="Język" value={user.language} />
              <Row label="Waluta" value={user.currency} />
            </dl>
          </Card>
        )}

        {/* Saldo */}
        {tab === 'balance' && (
          <Card>
            <h2 className="mb-4 text-lg font-semibold">Saldo portfela</h2>
            <div className="mb-6 rounded-xl bg-brand-50 p-6 text-center">
              <p className="text-sm text-slate-500">Dostępne środki</p>
              <p className="text-4xl font-bold text-brand-600">
                {wallet ? formatMoney(parseFloat(wallet.balance), wallet.currency) : '—'}
              </p>
            </div>
            <DepositForm onDeposited={loadData} />
          </Card>
        )}

        {/* Subskrypcja */}
        {tab === 'subscription' && (
          <div className="grid gap-4 md:grid-cols-2">
            {plans.map((plan) => (
              <Card key={plan.id}>
                <h3 className="text-lg font-bold">{plan.name}</h3>
                <p className="mb-4 text-2xl font-bold text-brand-600">
                  {formatMoney(parseFloat(plan.price), plan.currency)}
                  <span className="text-sm font-normal text-slate-500">
                    {plan.billing_type === 'monthly' ? '/mies.' : plan.billing_type === 'per_ticket' ? '/paket' : ''}
                  </span>
                </p>
                <ul className="mb-4 space-y-1 text-sm text-slate-600">
                  <li>✓ {plan.max_messages_day} wiadomości / dzień</li>
                  <li>✓ Max {plan.max_prompt_length} znaków / pytanie</li>
                  <li>✓ {plan.max_tokens_month.toLocaleString()} tokenów / miesiąc</li>
                </ul>
                <button
                  onClick={() => api.subscribe(plan.id).then(() => loadData())}
                  className="w-full rounded-lg bg-brand-600 px-4 py-2.5 font-semibold text-white hover:bg-brand-700"
                >
                  Wybierz plan
                </button>
              </Card>
            ))}
          </div>
        )}

        {/* Transakcje */}
        {tab === 'transactions' && (
          <Card>
            <h2 className="mb-4 text-lg font-semibold">Historia płatności</h2>
            <table className="w-full text-sm">
              <thead className="text-left text-slate-500">
                <tr>
                  <th className="pb-2">Data</th>
                  <th className="pb-2">Typ</th>
                  <th className="pb-2 text-right">Kwota</th>
                  <th className="pb-2">Status</th>
                </tr>
              </thead>
              <tbody>
                {transactions.map((tx) => (
                  <tr key={tx.id} className="border-t border-slate-100">
                    <td className="py-2">{new Date(tx.created_at).toLocaleString()}</td>
                    <td className="py-2">{tx.type}</td>
                    <td className={`py-2 text-right font-medium ${parseFloat(tx.amount) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                      {formatMoney(parseFloat(tx.amount), tx.currency)}
                    </td>
                    <td className="py-2">
                      <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{tx.status}</span>
                    </td>
                  </tr>
                ))}
                {transactions.length === 0 && (
                  <tr><td colSpan={4} className="py-4 text-center text-slate-400">Brak transakcji</td></tr>
                )}
              </tbody>
            </table>
          </Card>
        )}

        {/* Ustawienia */}
        {tab === 'settings' && (
          <Card>
            <SettingsForm user={user} onSaved={loadData} />
          </Card>
        )}
      </div>
    </div>
  );
}

function Card({ children }: { children: React.ReactNode }) {
  return <div className="rounded-xl border border-slate-200 bg-white p-6">{children}</div>;
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between">
      <dt className="text-slate-500">{label}</dt>
      <dd className="font-medium">{value}</dd>
    </div>
  );
}

function DepositForm({ onDeposited }: { onDeposited: () => void }) {
  const [amount, setAmount] = useState(50);
  const [loading, setLoading] = useState(false);

  async function handleDeposit() {
    setLoading(true);
    try {
      const result = await api.deposit(amount);
      // Przekierowanie do bramki P24
      window.location.href = result.redirect_url;
    } catch (err: any) {
      alert(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <label className="mb-2 block text-sm font-medium">Kwota doładowania</label>
      <div className="flex gap-2">
        <input
          type="number" min={1} value={amount}
          onChange={(e) => setAmount(Number(e.target.value))}
          className="flex-1 rounded-lg border border-slate-300 px-4 py-2.5"
        />
        <button
          onClick={handleDeposit} disabled={loading}
          className="rounded-lg bg-brand-600 px-6 py-2.5 font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
        >
          {loading ? 'Przetwarzanie...' : 'Opłać'}
        </button>
      </div>
      <div className="mt-3 flex gap-2">
        {[20, 50, 100, 200].map((amt) => (
          <button
            key={amt} onClick={() => setAmount(amt)}
            className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50"
          >
            {amt} zł
          </button>
        ))}
      </div>
    </div>
  );
}

function SettingsForm({ user, onSaved }: { user: any; onSaved: () => void }) {
  const [language, setLanguage] = useState(user.language || 'pl');
  const [currency, setCurrency] = useState(user.currency || 'PLN');
  const [saved, setSaved] = useState(false);

  async function handleSave() {
    await api.updateSettings({ language, currency });
    localStorage.setItem('locale', language);
    localStorage.setItem('currency', currency);
    setSaved(true);
    onSaved();
    setTimeout(() => setSaved(false), 2000);
  }

  return (
    <div className="space-y-4">
      <h2 className="text-lg font-semibold">Ustawienia</h2>
      <div>
        <label className="mb-1 block text-sm font-medium">Język</label>
        <select
          value={language}
          onChange={(e) => setLanguage(e.target.value)}
          className="w-full rounded-lg border border-slate-300 px-4 py-2.5"
        >
          <option value="pl">Polski</option>
          <option value="en">English</option>
          <option value="de">Deutsch</option>
          <option value="fr">Français</option>
          <option value="es">Español</option>
        </select>
      </div>
      <div>
        <label className="mb-1 block text-sm font-medium">Waluta</label>
        <select
          value={currency}
          onChange={(e) => setCurrency(e.target.value)}
          className="w-full rounded-lg border border-slate-300 px-4 py-2.5"
        >
          <option value="PLN">PLN (zł)</option>
          <option value="EUR">EUR (€)</option>
          <option value="USD">USD ($)</option>
          <option value="GBP">GBP (£)</option>
        </select>
      </div>
      <button
        onClick={handleSave}
        className="rounded-lg bg-brand-600 px-6 py-2.5 font-semibold text-white hover:bg-brand-700"
      >
        Zapisz
      </button>
      {saved && <span className="ml-3 text-sm text-green-600">Zapisano!</span>}
    </div>
  );
}
