/**
 * Formatowanie waluty po stronie klienta.
 */

const symbols: Record<string, string> = {
  PLN: 'zł', EUR: '€', USD: '$', GBP: '£',
};

export function getCurrency(): string {
  if (typeof window === 'undefined') return 'EUR';
  return localStorage.getItem('currency') || 'EUR';
}

export function setCurrency(currency: string) {
  if (typeof window === 'undefined') return;
  localStorage.setItem('currency', currency);
}

export function formatMoney(amount: number, currency?: string): string {
  const cur = currency || getCurrency();
  const symbol = symbols[cur] || cur;
  return `${amount.toFixed(2)} ${symbol}`;
}
