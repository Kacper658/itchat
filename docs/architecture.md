# Architektura systemu

## Przegląd

```
┌──────────────────────────────────────────────────────────┐
│                      Przeglądarka                         │
│  Next.js (frontend — SSR/CSR, i18n, chat UI, panele)      │
└───────────────────────────┬──────────────────────────────┘
                            │ HTTPS / REST + Server-Sent Events
┌───────────────────────────▼──────────────────────────────┐
│                    Apache 24.04 (reverse proxy)           │
│  :443 → /var/www/frontend (Next, port 3000)              │
│  :443/api → /var/www/backend (Laravel, PHP-FPM)          │
└──────────┬───────────────────────────────┬───────────────┘
           │                               │
┌──────────▼──────────────┐   ┌───────────▼───────────────┐
│   Laravel 11 (PHP 8.3)  │   │        Redis              │
│                         │   │  (cache, queue, session)  │
│  • Auth (Sanctum)       │   └───────────────────────────┘
│  • ChatService          │
│  • BillingService       │   ┌───────────────────────────┐
│  • PlanValidator        │   │      PostgreSQL 16        │
│  • AIProviderInterface  │◄──┤  users, conversations,    │
│  • PaymentProviderIface │   │  messages, wallets,       │
│  • Queue jobs           │   │  transactions, plans...   │
│  • Admin panel (API)    │   └───────────────────────────┘
└────────┬────────────────┘
         │ HTTPS
┌────────▼─────────────────┐    ┌────────────────────────┐
│  DeepSeek API (default)  │    │  Ollama (lokalny, opt) │
│  ai.deepseek.com         │    │  localhost:11434       │
└──────────────────────────┘    └────────────────────────┘
         │
┌────────▼─────────────────┐
│  Przelewy24 (płatności)  │
│  secure.przelewy24.pl    │
└──────────────────────────┘
```

## Stack technologiczny

| Warstwa | Technologia |
|---------|-------------|
| Frontend | Next.js 14 (App Router), TypeScript, TailwindCSS |
| Backend | Laravel 11, PHP 8.3 |
| Baza danych | PostgreSQL 16 |
| Cache / Queue | Redis 7 |
| Serwer WWW | Apache 2.4 (mod_proxy, mod_rewrite, mod_ssl) |
| Process manager | PM2 (Next.js), systemd (PHP-FPM, Apache) |
| SSL | Let's Encrypt (Certbot) |
| OS | Ubuntu 24.04 LTS |

## Warstwy backendu (DDD-lite)

```
app/
├── Http/
│   ├── Controllers/    # cienkie kontrolery — walidacja + delegacja
│   ├── Middleware/     # admin, geolocation, auth
│   └── Requests/       # walidacja żądań
├── Services/           # logika biznesowa (ChatService, BillingService…)
│   ├── AI/
│   │   ├── AIProviderInterface.php
│   │   └── Providers/DeepSeekProvider.php, OllamaProvider.php
│   └── Payment/
│       ├── PaymentProviderInterface.php
│       └── Providers/Przelewy24Provider.php
└── Models/             # Eloquent — mapowanie tabel
```

## Przepływ żądania czatu

```
POST /api/chat/messages
  → AuthMiddleware (Sanctum lub guest)
  → ChatController@store
    → GuestLimitService  (czy gość przekroczył 2 wiadomości?)
    → PlanValidator      (limity planu: długość, quota)
    → BillingService     (czy jest saldo na ticket?)
    → ChatService->handle()
        → AIProviderInterface->chat()  (DeepSeek / Ollama)
        → Zapis messages (user + assistant, tokens, cost)
        → BillingService->charge()     (per-ticket / monthly)
  → Response: wiadomość asystenta
```

## Przepływ wpłaty

```
POST /api/billing/deposit { amount }
  → BillingController
    → PaymentProviderInterface->createTransaction()
    → Redirect URL do Przelewy24

POST /api/webhooks/p24  (z Przelewy24)
  → WebhookController
    → Weryfikacja podpisu
    → BillingService->confirmDeposit()
    → Wallet balance += amount
    → Transaction status = paid
```

## Katalogi na serwerze

```
/var/www/
├── frontend/     # Next.js  (build → pm2 start)
├── backend/      # Laravel  (PHP-FPM)
├── logs/         # logi aplikacji
└── docs/         # dokumentacja
```

## Reverse proxy Apache

```
/         → http://localhost:3000  (Next.js)
/api      → http://localhost:8000  (Laravel)   [lub PHP-FPM socket]
```
Patrz `docs/deployment.md`.
