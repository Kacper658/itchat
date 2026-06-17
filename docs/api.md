# Dokumentacja API (Laravel)

Base URL: `/api`

Autoryzacja: `Authorization: Bearer <sanctum-token>` (dla endpointów wymagających konta).

---

## Auth

| Metoda | Endpoint | Auth | Opis |
|--------|----------|------|------|
| POST | `/auth/register` | — | Rejestracja. Body: `{name,email,password,language?,currency?,guest_chat?}` |
| POST | `/auth/login` | — | Logowanie. Zwraca token Sanctum. |
| POST | `/auth/logout` | user | Wylogowanie (usunięcie tokenu). |
| GET | `/auth/me` | user | Bieżący użytkownik + portfel + subskrypcja. |
| POST | `/auth/forgot-password` | — | Reset hasła — wysyłka e-mail. |
| POST | `/auth/reset-password` | — | Reset hasła — potwierdzenie. |

### Rejestracja z gościa
```json
POST /auth/register
{
  "name": "Jan",
  "email": "jan@example.com",
  "password": "secret123",
  "language": "pl",
  "currency": "PLN",
  "guest_chat": {
    "title": "Rozmowa gościa",
    "messages": [
      {"role":"user","content":"Cześć"},
      {"role":"assistant","content":"Witaj!"},
      {"role":"user","content":"Pomóż mi"}
    ]
  }
}
```
Backend tworzy conversation + przenosi wiadomości. Użytkownik nie traci kontekstu.

---

## Chat

| Metoda | Endpoint | Auth | Opis |
|--------|----------|------|------|
| GET | `/chat/conversations` | user | Lista rozmów użytkownika. |
| POST | `/chat/conversations` | user | Nowa rozmowa. Body: `{title?}` |
| GET | `/chat/conversations/{id}/messages` | user | Wiadomości w rozmowie. |
| DELETE | `/chat/conversations/{id}` | user | Usunięcie rozmowy. |
| POST | `/chat/messages` | user | Wysłanie wiadomości. Body: `{conversation_id?, content}` |
| POST | `/chat/guest` | — | Wiadomość gościa (lim. 2). Body: `{session_id, content, history}` |

### POST /chat/messages — odpowiedź
```json
{
  "conversation_id": 42,
  "message": {
    "id": 1001,
    "role": "assistant",
    "content": "Aby rozwiączyć ten problem...",
    "prompt_tokens": 120,
    "completion_tokens": 80,
    "cost": 0.0021
  }
}
```

### POST /chat/guest — odpowiedź przy blokadzie
```json
{
  "blocked": true,
  "reason": "guest_limit_reached",
  "message": "Osiągnąłeś limit darmowych wiadomości. Załóż konto, aby kontynuować."
}
```

---

## Billing

| Metoda | Endpoint | Auth | Opis |
|--------|----------|------|------|
| GET | `/billing/wallet` | user | Saldo portfela. |
| GET | `/billing/transactions` | user | Historia transakcji (paginacja). |
| POST | `/billing/deposit` | user | Inicjacja wpłaty. Body: `{amount}` → redirect URL. |
| GET | `/billing/plans` | — | Lista planów z limitami. |
| POST | `/billing/subscribe` | user | Wybór planu. Body: `{plan_id}` |
| GET | `/billing/subscription` | user | Bieżąca subskrypcja. |

---

## Webhooks (publiczne, weryfikowane podpisem)

| Metoda | Endpoint | Opis |
|--------|----------|------|
| POST | `/webhooks/przelewy24` | Powiadomienie o płatności P24. |
| POST | `/webhooks/payu` | PayU (przyszłość). |

---

## User

| Metoda | Endpoint | Auth | Opis |
|--------|----------|------|------|
| GET | `/user/settings` | user | Ustawienia (język, waluta). |
| PUT | `/user/settings` | user | Aktualizacja ustawień. |
| DELETE | `/user` | user | Usunięcie konta (RODO). |

---

## Geo (publiczne)

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | `/geo/detect` | Zwraca wykryty język i walutę na podstawie IP. |

---

## Admin (wymaga role=admin)

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | `/admin/stats` | Dashboard: użytkownicy, przychody, koszty, marża. |
| GET | `/admin/users` | Lista klientów (paginacja, filtry). |
| PATCH | `/admin/users/{id}` | Blokada/odblokowanie, zmiana roli. |
| POST | `/admin/users/{id}/bonus` | Doładowanie bonus. |
| GET | `/admin/transactions` | Wszystkie transakcje. |
| GET | `/admin/plans` | Lista planów. |
| PUT | `/admin/plans/{id}` | Edycja limitów planu. |
| GET | `/admin/messages/export` | Eksport pytań (anonimowy). `?format=csv\|json` |
| GET | `/admin/stats/export` | Eksport statystyk. |

### Eksport pytań (CSV)
```
message,language,created_at
"Jak zainstalować Linux?","pl","2026-06-16 10:00:00"
```
Brak kolumn identyfikujących użytkownika.

---

## Kody błędów

| HTTP | Kod | Znaczenie |
|------|-----|-----------|
| 401 | `unauthenticated` | Brak/niepoprawny token |
| 403 | `forbidden` | Brak uprawnień (np. admin) |
| 402 | `payment_required` | Wymagana wpłata / brak salda |
| 422 | `validation_error` | Błędne dane wejściowe |
| 429 | `guest_limit_reached` | Gość przekroczył limit wiadomości |
| 429 | `plan_limit_reached` | Przekroczono limit planu |
