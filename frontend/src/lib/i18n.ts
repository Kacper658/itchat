/**
 * Minimalny system tłumaczeń (po stronie klienta).
 * Język ustalany geolokalizacyjnie, zapisywany w localStorage.
 */

import pl from '../locales/pl.json';
import en from '../locales/en.json';
import de from '../locales/de.json';

export type Locale = 'pl' | 'en' | 'de';

const dictionaries: Record<Locale, Record<string, string>> = {
  pl: pl as Record<string, string>,
  en: en as Record<string, string>,
  de: de as Record<string, string>,
};

export function getLocale(): Locale {
  if (typeof window === 'undefined') return 'en';
  return (localStorage.getItem('locale') as Locale) || 'en';
}

export function setLocale(locale: Locale) {
  if (typeof window === 'undefined') return;
  localStorage.setItem('locale', locale);
}

export function t(key: string, locale?: Locale): string {
  const loc = locale || getLocale();
  return dictionaries[loc]?.[key] ?? dictionaries.en[key] ?? key;
}
