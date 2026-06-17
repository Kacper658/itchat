/**
 * Lekki klient API dla backendu Laravel (Sanctum).
 */

const API_URL = process.env.NEXT_PUBLIC_API_URL || '/api';

function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem('auth_token');
}

export function setToken(token: string | null) {
  if (typeof window === 'undefined') return;
  if (token) localStorage.setItem('auth_token', token);
  else localStorage.removeItem('auth_token');
}

async function request<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const token = getToken();
  const res = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  });

  if (!res.ok) {
    const error = await res.json().catch(() => ({}));
    throw new ApiError(error.message || res.statusText, res.status, error);
  }

  return res.json();
}

export class ApiError extends Error {
  constructor(message: string, public status: number, public data?: any) {
    super(message);
  }
}

export const api = {
  // --- Auth ---
  register: (data: any) => request<{ user: any; token: string }>('/auth/register', {
    method: 'POST', body: JSON.stringify(data),
  }),
  login: (data: any) => request<{ user: any; token: string }>('/auth/login', {
    method: 'POST', body: JSON.stringify(data),
  }),
  logout: () => request('/auth/logout', { method: 'POST' }),
  me: () => request<{ user: any }>('/auth/me'),

  // --- Chat ---
  guestMessage: (data: any) => request('/chat/guest', {
    method: 'POST', body: JSON.stringify(data),
  }),
  sendMessage: (data: any) => request('/chat/messages', {
    method: 'POST', body: JSON.stringify(data),
  }),
  conversations: () => request('/chat/conversations'),
  messages: (id: number) => request(`/chat/conversations/${id}/messages`),

  // --- Billing ---
  wallet: () => request('/billing/wallet'),
  transactions: () => request('/billing/transactions'),
  plans: () => request('/billing/plans'),
  deposit: (amount: number) => request('/billing/deposit', {
    method: 'POST', body: JSON.stringify({ amount }),
  }),
  subscribe: (planId: number) => request('/billing/subscribe', {
    method: 'POST', body: JSON.stringify({ plan_id: planId }),
  }),

  // --- User ---
  updateSettings: (data: any) => request('/user/settings', {
    method: 'PUT', body: JSON.stringify(data),
  }),

  // --- Geo ---
  detectGeo: () => request('/geo/detect'),
};
