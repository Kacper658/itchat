# Business Rules — Źródło prawdy

> Ten dokument jest **jedynym źródłem prawdy** dla logiki biznesowej aplikacji.
> Wszelkie decyzje architektoniczne i implementacyjne muszą być z nim zgodne.
> Aktualizacja tego pliku = aktualizacja wymagań.

---

## 1. Cel produktu

Platforma łącząca klientów potrzebujących pomocy informatycznej z ekspertem-AI.
Rozmowa odbywa się w interfejsie czatu (model: ChatGPT). Model językowy (domyślnie **DeepSeek**) odpowiada jako wirtualny informatyk / ekspert IT.

---

## 2. Role użytkowników

| Rola | Symbol | Uprawnienia |
|------|--------|-------------|
| Gość (niezalogowany) | `guest` | Maks. **2 wiadomości** w jednej sesji, chat trzymany w LocalStorage |
| Użytkownik | `user` | Pełny chat, historia rozmów, portfel, subskrypcja, ustawienia |
| Administrator | `admin` | Wszystko co `user` + panel administracyjny |

---

## 3. Reguła blokady gościa (paywall)

```
Gość wysyła wiadomość 1  → odpowiedź AI
Gość wysyła wiadomość 2  → odpowiedź AI
Gość wysyła wiadomość 3  → BLOKADA: modal "Opłać aby kontynuować"
```

- Po **2. odpowiedzi** następuje blokada. Trzecia wiadomość nie jest wysyłana do AI.
- Modal oferuje: **Załóż konto** → **Wpłać środki** → **Kontynuuj rozmowę**.
- Rozmowa gościa jest zachowywana w LocalStorage przeglądarki.
- Po rejestracji historia gościa zostaje **przepisana** na konto użytkownika — klient nie traci kontekstu.

### Liczenie wiadomości gościa
- Liczone są tylko **wiadomości użytkownika** (`role = user`), nie odpowiedzi asystenta.
- Licznik resetuje się gdy gość wyczyści historię lub po **24h** od pierwszej wiadomości.

---

## 4. Plany i subskrypcje

### 4.1 Plany

| Plan | Model rozliczenia | Opis |
|------|-------------------|------|
| Free | — | Po rejestracji, brak wymagań wpłaty, bardzo niskie limity |
| Basic | per-ticket | Płatność za pojedyncze zgłoszenie / paczkę wiadomości |
| Pro | miesięczny | Abonament miesięczny, wyższe limity |
| Enterprise | miesięczny | Abonament, najwyższe limity, priorytet |

### 4.2 Limity planu (konfigurowalne z panelu admina)

Każdy plan definiuje:

| Pole | Znaczenie |
|------|-----------|
| `max_messages_day` | Maks. liczba wiadomości użytkownika / dzień |
| `max_tokens_month` | Maks. liczba tokenów / miesiąc |
| `max_prompt_length` | Maks. liczba znaków w pytaniu |
| `max_response_length` | Maks. liczba znaków w odpowiedzi |
| `price` | Cena planu |
| `billing_type` | `per_ticket` \| `monthly` |
| `tickets_per_charge` | Dla `per_ticket`: ile wiadomości obejmuje jedna opłata |

### 4.3 Naliczanie opłat

**Plan per-ticket:**
```
Użytkownik z planem per-ticket wysyła wiadomość.
→ Sprawdź licznik wiadomości od ostatniej opłaty.
→ Jeśli licznik >= tickets_per_charge → pobierz opłatę z portfela → resetuj licznik.
```

**Plan monthly:**
```
Subskrypcja aktywna do data_ostatniej_oplaty + 30 dni.
Brak środków na odnowienie → status = expired, downgrade do Free.
```

### 4.4 Saldo insufficient

Jeśli saldo portfela **< wymaganej kwoty**:
- Plan per-ticket → zablokuj wysyłkę, pokaż modal doładowania.
- Plan monthly → oznacz subskrypcję jako `pending_payment`, wyślij powiadomienie.

---

## 5. Portfel i transakcje

### 5.1 Portfel

Każdy użytkownik ma **jeden portfel**.
- `balance` — saldo (zawsze ≥ 0, nie może być ujemne).
- `currency` — waluta (ustawiana geolokalizacyjnie przy rejestracji, edytowalna).

### 5.2 Typy transakcji

| Typ | Kierunek | Opis |
|-----|----------|------|
| `deposit` | + | Wpłata środków przez operatora płatności |
| `chat_cost` | − | Koszt wiadomości / ticketu |
| `refund` | + | Zwrot środków |
| `subscription_fee` | − | Opłata za subskrypcję miesięczną |
| `bonus` | + | Środki promocyjne od admina |

### 5.3 Nienaruszalność salda

- Wszystkie operacje na portfelu przechodzą przez `BillingService`.
- Opłata może być pobrana **tylko** jeśli `balance >= amount`.
- Transakcje są **niezmienne** (append-only log). Korekty = nowa transakcja `refund`.

---

## 6. Geolokalizacja

Przy pierwszym wejściu (middleware):
```
IP → GeoIP → Country → Language + Currency
```

| Kraj | Język | Waluta |
|------|-------|--------|
| PL | pl | PLN |
| DE | de | EUR |
| US / GB | en | USD / GBP |
| (domyślnie) | en | EUR |

- Wykryte wartości zapisywane w sesji / cookie.
- Po rejestracji zapisywane w `users.language` i `users.currency`.
- Użytkownik może je zmienić w ustawieniach w dowolnym momencie.

---

## 7. AI / LLM Gateway

### 7.1 Abstrakcja

```
ChatController → ChatService → AIProviderInterface → {DeepSeek|Ollama|OpenAI|...}
```

Nigdy nie wołaj API dostawcy bezpośrednio z kontrolera.

### 7.2 Domyślny dostawca
- **DeepSeek** (`deepseek-chat`).
- Konfiguracja w `.env`: `AI_PROVIDER=deepseek`, `DEEPSEEK_API_KEY=...`.

### 7.3 Zmiana na model lokalny
- `AI_PROVIDER=ollama`, `OLLAMA_URL=http://localhost:11434`, `OLLAMA_MODEL=llama3`.
- Logika aplikacji pozostaje bez zmian.

### 7.4 Liczenie kosztu
```
cost = (prompt_tokens × price_in) + (completion_tokens × price_out)
```
Ceny tokenów zależą od dostawcy, konfigurowalne w `config/ai.php`.

---

## 8. Panel administracyjny

### 8.1 Dostęp
Tylko użytkownicy z `role = admin`. Middleware `admin`.

### 8.2 Funkcje

| Moduł | Możliwości |
|-------|------------|
| Dashboard | Statystyki: użytkownicy, rozmowy, wiadomości, przychody, koszty AI, marża |
| Klienci | Lista, blokada/odblokowanie, podgląd salda i subskrypcji, doładowanie bonus |
| Płatności | Lista transakcji, zbiorcze saldo, suma wpływów i wypływów |
| Plany | Edycja limitów planów (`max_messages_day`, `max_tokens_month`, itd.) |
| Chaty | Anonimowy eksport pytań użytkowników (CSV / JSON) |
| Eksport | Eksport pytań (anonimowy), eksport statystyk |
| Ustawienia | Konfiguracja globalna |

### 8.3 Anonimizacja eksportu pytań
Eksportowane pola: `message`, `language`, `timestamp`.
**Nigdy nie eksportować:** `email`, `ip`, `name`, `user_id`.

---

## 9. Płatności — operatorzy

### MVP
- **Przelewy24** (główny operator)

### Kolejne etapy
- PayU
- Stripe

### Abstrakcja
```
PaymentProviderInterface → {Przelewy24Provider|PayUProvider|StripeProvider}
```
Zmiana operatora nie wymaga przebudowy systemu.

### Przepływ wpłaty
```
Użytkownik wybiera kwotę
  → Backend tworzy transakcję deposit (status=pending)
  → Przekierowanie do bramki operatora
  → Użytkownik płaci
  → Webhook z operatora → weryfikacja → balance += amount, status=paid
```

### Bezpieczeństwo webhooka
- Każdy webhook weryfikowany podpisem / sumą kontrolną operatora.
- Idempotentność po `provider_transaction_id`.

---

## 10. Statystyki

Generowane dynamicznie (zapytania SQL agregujące) oraz zapisywane w `daily_stats`.

| Metryka | Źródło |
|---------|--------|
| Liczba użytkowników | `users` |
| Aktywni użytkownicy (30d) | `users.last_login_at` |
| Przychód | `transactions WHERE type=deposit` |
| Koszt AI | `messages.cost` |
| Liczba rozmów | `conversations` |
| Liczba wiadomości | `messages` |
| Marża | przychód − koszt AI |

---

## 11. Zgodność z RODO (fundamenty)

- Eksport pytań domyślnie anonimowy.
- Brak logowania IP w danych użytkownika po geolokalizacji.
- Możliwość usunięcia konta (soft-delete + anonimizacja wiadomości).
- Dane transakcyjne przechowywane zgodnie z wymogami księgowymi.

---

## 12. Internacjonalizacja (i18n)

- Język domyślny: wykryty geolokalizacyjnie, fallback `en`.
- Obsługiwane na start: `pl`, `en`, `de`.
- Zmiana w ustawieniach użytkownika → zapis w `users.language`.
- Tłumaczenia front-endu w `frontend/src/locales/`.

---

## 13. Skrócony słownik pojęć

| Pojęcie | Definicja |
|---------|-----------|
| Ticket | Paczka wiadomości w planie per-ticket |
| Sesja gościa | Chat niezalogowanego użytkownika w LocalStorage |
| Portfel | Saldo użytkownika w wybranej walucie |
| Provider | Dostawca usługi (AI lub płatności) za warstwą interfejsu |
