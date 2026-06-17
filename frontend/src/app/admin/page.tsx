'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { formatMoney } from '../../lib/currency';

type Tab = 'dashboard' | 'users' | 'payments' | 'plans' | 'export';

export default function AdminPage() {
  const router = useRouter();
  const [tab, setTab] = useState<Tab>('dashboard');
  const [stats, setStats] = useState<any>(null);
  const [users, setUsers] = useState<any[]>([]);
  const [plans, setPlans] = useState<any[]>([]);
  const [transactions, setTransactions] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

  useEffect(() => {
    if (!token) {
      router.push('/login');
      return;
    }
    loadDashboard();
  }, []);

  async function apiGet(path: string) {
    const res = await fetch(`/api${path}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    if (res.status === 403) {
      router.push('/dashboard');
      throw new Error('Brak uprawnień');
    }
    const data = await res.json();
    return data.data || data;
  }

  async function loadDashboard() {
    try {
      const s = await apiGet('/admin/stats');
      setStats(s);
    } catch {
      router.push('/login');
    } finally {
      setLoading(false);
    }
  }

  async function loadUsers() {
    const u = await apiGet('/admin/users');
    setUsers(u);
  }

  async function loadPlans() {
    const p = await apiGet('/admin/plans');
    setPlans(p);
  }

  async function loadTransactions() {
    const t = await apiGet('/admin/transactions');
    setTransactions(t);
  }

  async function toggleBlock(user: any) {
    await fetch(`/api/admin/users/${user.id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ is_blocked: !user.is_blocked }),
    });
    loadUsers();
  }

  async function savePlan(plan: any) {
    await fetch(`/api/admin/plans/${plan.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify(plan),
    });
    alert(`Plan "${plan.name}" zapisany`);
  }

  async function exportMessages(format: string) {
    window.location.href = `/api/admin/messages/export?format=${format}&token=${token}`;
    // Note: w produkcji token przez header, tu uproszczenie (lub endpoint sanctum)
  }

  if (loading || !stats) {
    return <div className="flex min-h-screen items-center justify-center text-slate-400">Ładowanie...</div>;
  }

  const tabs: { key: Tab; label: string }[] = [
    { key: 'dashboard', label: 'Dashboard' },
    { key: 'users', label: 'Klienci' },
    { key: 'payments', label: 'Płatności' },
    { key: 'plans', label: 'Plany' },
    { key: 'export', label: 'Eksport' },
  ];

  return (
    <div className="min-h-screen bg-slate-100">
      <header className="border-b border-slate-200 bg-slate-900 text-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
          <h1 className="text-lg font-bold">Panel administracyjny</h1>
          <a href="/chat" className="text-sm text-slate-300 hover:underline">Wróć do czatu</a>
        </div>
      </header>

      <div className="mx-auto max-w-6xl px-4 py-8">
        {/* Sidebar tabs */}
        <div className="mb-8 flex gap-1 rounded-xl bg-white p-1 shadow-sm">
          {tabs.map((t) => (
            <button
              key={t.key}
              onClick={() => {
                setTab(t.key);
                if (t.key === 'users') loadUsers();
                if (t.key === 'plans') loadPlans();
                if (t.key === 'payments') loadTransactions();
              }}
              className={`rounded-lg px-4 py-2 text-sm font-medium ${
                tab === t.key ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100'
              }`}
            >
              {t.label}
            </button>
          ))}
        </div>

        {/* Dashboard */}
        {tab === 'dashboard' && (
          <div className="space-y-6">
            <div className="grid gap-4 md:grid-cols-4">
              <StatCard label="Użytkownicy" value={stats.users.total} sub={`${stats.users.active_30d} aktywnych (30d)`} />
              <StatCard label="Rozmowy" value={stats.conversations} />
              <StatCard label="Wiadomości" value={stats.messages} />
              <StatCard label="Przychód" value={formatMoney(stats.revenue)} accent="green" />
            </div>
            <div className="grid gap-4 md:grid-cols-3">
              <StatCard label="Koszt AI" value={formatMoney(stats.ai_cost)} accent="red" />
              <StatCard label="Marża" value={formatMoney(stats.margin)} accent="green" />
              <StatCard label="Zbiorcze saldo" value={formatMoney(stats.wallets.total_balance)} />
            </div>
            <div className="grid gap-4 md:grid-cols-2">
              <StatCard label="Suma wpływów (depozyty)" value={formatMoney(stats.wallets.deposits_in)} accent="green" />
              <StatCard label="Suma wypływów" value={formatMoney(stats.wallets.outflows_out)} accent="red" />
            </div>
          </div>
        )}

        {/* Klienci */}
        {tab === 'users' && (
          <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-slate-500">
                <tr>
                  <th className="p-3">Użytkownik</th>
                  <th className="p-3">Saldo</th>
                  <th className="p-3">Plan</th>
                  <th className="p-3">Ostatnie logowanie</th>
                  <th className="p-3">Akcje</th>
                </tr>
              </thead>
              <tbody>
                {users.map((u) => (
                  <tr key={u.id} className="border-t border-slate-100">
                    <td className="p-3">
                      <div className="font-medium">{u.name}</div>
                      <div className="text-xs text-slate-500">{u.email}</div>
                    </td>
                    <td className="p-3">{u.wallet ? formatMoney(parseFloat(u.wallet.balance), u.wallet.currency) : '—'}</td>
                    <td className="p-3">{u.active_subscription?.plan?.name || 'Free'}</td>
                    <td className="p-3 text-xs text-slate-500">
                      {u.last_login_at ? new Date(u.last_login_at).toLocaleDateString() : '—'}
                    </td>
                    <td className="p-3">
                      <button
                        onClick={() => toggleBlock(u)}
                        className={`rounded px-2 py-1 text-xs font-medium ${
                          u.is_blocked ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'
                        }`}
                      >
                        {u.is_blocked ? 'Odblokuj' : 'Zablokuj'}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Płatności */}
        {tab === 'payments' && (
          <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-slate-500">
                <tr>
                  <th className="p-3">Data</th>
                  <th className="p-3">Użytkownik</th>
                  <th className="p-3">Typ</th>
                  <th className="p-3 text-right">Kwota</th>
                  <th className="p-3">Status</th>
                </tr>
              </thead>
              <tbody>
                {transactions.map((tx) => (
                  <tr key={tx.id} className="border-t border-slate-100">
                    <td className="p-3 text-xs">{new Date(tx.created_at).toLocaleString()}</td>
                    <td className="p-3">{tx.user?.email}</td>
                    <td className="p-3">{tx.type}</td>
                    <td className={`p-3 text-right font-medium ${parseFloat(tx.amount) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                      {formatMoney(parseFloat(tx.amount), tx.currency)}
                    </td>
                    <td className="p-3"><span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{tx.status}</span></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Plany */}
        {tab === 'plans' && (
          <div className="space-y-4">
            {plans.map((plan) => (
              <div key={plan.id} className="rounded-xl border border-slate-200 bg-white p-6">
                <div className="mb-4 flex items-center justify-between">
                  <h3 className="text-lg font-bold">{plan.name}</h3>
                  <span className="rounded bg-slate-100 px-2 py-1 text-xs">{plan.billing_type}</span>
                </div>
                <div className="grid gap-3 md:grid-cols-3">
                  <Field label="Cena (PLN)" value={plan.price} onChange={(v) => updatePlan(plan.id, 'price', v)} />
                  <Field label="Max wiadomości/dzień" value={plan.max_messages_day} onChange={(v) => updatePlan(plan.id, 'max_messages_day', v)} />
                  <Field label="Max tokenów/miesiąc" value={plan.max_tokens_month} onChange={(v) => updatePlan(plan.id, 'max_tokens_month', v)} />
                  <Field label="Max znaków pytania" value={plan.max_prompt_length} onChange={(v) => updatePlan(plan.id, 'max_prompt_length', v)} />
                  <Field label="Max znaków odpowiedzi" value={plan.max_response_length} onChange={(v) => updatePlan(plan.id, 'max_response_length', v)} />
                  <Field label="Wiadomości/pakiet" value={plan.tickets_per_charge} onChange={(v) => updatePlan(plan.id, 'tickets_per_charge', v)} />
                </div>
                <button
                  onClick={() => savePlan(plans.find((p) => p.id === plan.id))}
                  className="mt-4 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                >
                  Zapisz zmiany
                </button>
              </div>
            ))}
          </div>
        )}

        {/* Eksport */}
        {tab === 'export' && (
          <div className="rounded-xl border border-slate-200 bg-white p-8">
            <h2 className="mb-2 text-lg font-semibold">Eksport pytań (anonimowy)</h2>
            <p className="mb-6 text-sm text-slate-500">
              Pobiera wszystkie pytania użytkowników. Dane są anonimowe — bez e-mail, IP, nazwy użytkownika.
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => exportMessages('csv')}
                className="rounded-lg bg-brand-600 px-6 py-3 font-semibold text-white hover:bg-brand-700"
              >
                Pobierz CSV
              </button>
              <button
                onClick={() => exportMessages('json')}
                className="rounded-lg border border-slate-300 px-6 py-3 font-semibold text-slate-700 hover:bg-slate-50"
              >
                Pobierz JSON
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );

  function updatePlan(id: number, field: string, value: any) {
    setPlans((prev) => prev.map((p) => (p.id === id ? { ...p, [field]: value } : p)));
  }
}

function StatCard({ label, value, sub, accent }: { label: string; value: any; sub?: string; accent?: string }) {
  const color = accent === 'green' ? 'text-green-600' : accent === 'red' ? 'text-red-600' : 'text-slate-900';
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-5">
      <p className="text-sm text-slate-500">{label}</p>
      <p className={`text-2xl font-bold ${color}`}>{typeof value === 'number' ? value.toLocaleString() : value}</p>
      {sub && <p className="mt-1 text-xs text-slate-400">{sub}</p>}
    </div>
  );
}

function Field({ label, value, onChange }: { label: string; value: any; onChange: (v: any) => void }) {
  return (
    <div>
      <label className="mb-1 block text-xs font-medium text-slate-500">{label}</label>
      <input
        type="number" value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
      />
    </div>
  );
}
