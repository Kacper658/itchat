# Schemat bazy danych (PostgreSQL)

## ERD (uproszczone)

```
users ──< conversations ──< messages
  │
  ├──1 wallets ──< transactions
  │
  └──1 subscriptions >── plans
```

## Tabele

### users
| Kolumna | Typ | Opis |
|---------|-----|------|
| id | bigint PK | |
| name | string | nazwa wyświetlana |
| email | string unique | |
| email_verified_at | timestamp | |
| password | string | hash |
| role | enum(user, admin) | default user |
| language | string(5) | np. pl, en — z geolokalizacji |
| currency | string(3) | np. PLN, EUR |
| last_login_at | timestamp | do statystyk aktywności |
| last_message_at | timestamp | do limitu dziennego |
| messages_today | integer | licznik wiadomości bieżącego dnia |
| messages_since_charge | integer | licznik dla per-ticket |
| remember_token | string | |
| deleted_at | timestamp | soft delete (RODO) |
| created_at, updated_at | timestamps | |

### password_reset_tokens
Standard Laravel.

### sessions
Standard Laravel (driver=database lub redis).

---

### conversations
| Kolumna | Typ |
|---------|-----|
| id | bigint PK |
| user_id | bigint FK → users |
| title | string |
| model | string | np. deepseek-chat |
| created_at, updated_at | timestamps |

### messages
| Kolumna | Typ |
|---------|-----|
| id | bigint PK |
| conversation_id | bigint FK → conversations |
| role | enum(user, assistant, system) |
| content | text |
| prompt_tokens | integer |
| completion_tokens | integer |
| cost | decimal(10,6) | koszt w walucie bazowej |
| created_at | timestamps |

> `cost` przechowywany w **walucie bazowej systemu** (`SYSTEM_CURRENCY`), konwersja na walutę użytkownika przy wyświetlaniu.

---

### plans
| Kolumna | Typ |
|---------|-----|
| id | bigint PK |
| name | string | Free, Basic, Pro, Enterprise |
| slug | string unique | |
| billing_type | enum(per_ticket, monthly, free) |
| price | decimal(10,2) |
| currency | string(3) |
| max_messages_day | integer |
| max_tokens_month | integer |
| max_prompt_length | integer |
| max_response_length | integer |
| tickets_per_charge | integer | ile wiadomości = 1 opłata |
| is_active | boolean |
| sort_order | integer |
| created_at, updated_at | timestamps |

### subscriptions
| Kolumna | Typ |
|---------|-----|
| id | bigint PK |
| user_id | bigint FK → users |
| plan_id | bigint FK → plans |
| status | enum(active, expired, pending_payment, cancelled) |
| started_at | timestamp |
| expires_at | timestamp | dla monthly |
| auto_renew | boolean |
| created_at, updated_at | timestamps |

---

### wallets
| Kolumna | Typ |
|---------|-----|
| id | bigint PK |
| user_id | bigint FK unique → users |
| balance | decimal(12,2) default 0 |
| currency | string(3) |
| created_at, updated_at | timestamps |

### transactions
| Kolumna | Typ |
|---------|-----|
| id | bigint PK |
| wallet_id | bigint FK → wallets |
| user_id | bigint FK → users |
| type | enum(deposit, chat_cost, refund, subscription_fee, bonus) |
| amount | decimal(12,2) | dodatni=wpływ, ujemny=obciążenie |
| currency | string(3) |
| balance_after | decimal(12,2) | snapshot salda po transakcji |
| status | enum(pending, paid, failed, refunded) |
| provider | string(50) | przelewy24, payu, stripe, system |
| provider_transaction_id | string nullable | idempotencja |
| description | string nullable |
| message_id | bigint FK nullable → messages |
| created_at, updated_at | timestamps |

> Indeks unique na `provider + provider_transaction_id` dla idempotencji webhooków.

---

### daily_stats (opcjonalnie, agregacje)
| Kolumna | Typ |
|---------|-----|
| id | bigint PK |
| date | date |
| new_users | integer |
| active_users | integer |
| conversations_count | integer |
| messages_count | integer |
| revenue | decimal(12,2) |
| ai_cost | decimal(12,2) |
| created_at, updated_at | timestamps |

## Indeksy

- `messages(conversation_id)`, `messages(created_at)`
- `transactions(user_id, created_at)`, `transactions(provider, provider_transaction_id)`
- `conversations(user_id, created_at)`
- `users(role)`, `users(last_login_at)`

## Waluta bazowa

`SYSTEM_CURRENCY=PLN` (z `.env`). Wszystkie `cost` i `transactions.amount` w PLN.
Kursy wymiany w `config/currency.php` (lub API NBP dla EUR/USD).
Saldo użytkownika pokazywane w jego walucie, obciążenia przeliczane.
