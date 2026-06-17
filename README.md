# IT Expert Chat

Wirtualny ekspert informatyczny — platforma chatowa (ChatGPT-like) z systemem kont, portfela, subskrypcji i panelem administracyjnym.

## Stack

| Warstwa | Technologia |
|---------|-------------|
| Frontend | Next.js 14, TypeScript, TailwindCSS |
| Backend | Laravel 11, PHP 8.3 |
| Baza | PostgreSQL 16 |
| Cache/Queue | Redis 7 |
| Serwer | Ubuntu 24.04 + Apache 2.4 |
| AI | DeepSeek (domyślnie) / Ollama (lokalny) |
| Płatności | Przelewy24 (MVP) |

## Struktura repozytorium

```
.
├── docs/                    # Dokumentacja (business-rules = źródło prawdy)
│   ├── business-rules.md    # ★ Najważniejszy plik — wszystkie reguły biznesowe
│   ├── architecture.md      # Diagram + przepływy
│   ├── database.md          # Schemat bazy danych
│   ├── api.md               # Dokumentacja API
│   └── deployment.md        # Instrukcja wdrożenia (Apache, PM2, SSL)
│
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── ChatController.php
│   │   │   ├── BillingController.php
│   │   │   ├── AdminController.php
│   │   │   ├── UserController.php
│   │   │   ├── WebhookController.php
│   │   │   └── GeoController.php
│   │   ├── Http/Middleware/
│   │   │   ├── IsAdmin.php
│   │   │   └── SetLocale.php
│   │   ├── Models/
│   │   │   ├── User.php, Conversation.php, Message.php
│   │   │   ├── Wallet.php, Transaction.php
│   │   │   └── Plan.php, Subscription.php
│   │   ├── Policies/ConversationPolicy.php
│   │   └── Services/
│   │       ├── ChatService.php          # orkiestracja AI + billing
│   │       ├── BillingService.php       # portfel, transakcje (atomowe)
│   │       ├── PlanValidator.php        # limity planu
│   │       ├── GeoService.php           # IP → język + waluta
│   │       ├── GuestChatService.php     # limit 2 wiadomości gościa
│   │       ├── AI/                      # ← warstwa abstrakcji modelu
│   │       │   ├── AIProviderInterface.php
│   │       │   ├── AIManager.php
│   │       │   ├── AIResponse.php
│   │       │   ├── DeepSeekProvider.php
│   │       │   └── OllamaProvider.php
│   │       └── Payment/                 # ← warstwa abstrakcji płatności
│   │           ├── PaymentProviderInterface.php
│   │           ├── PaymentManager.php
│   │           ├── PaymentRequest.php / PaymentRedirect.php
│   │           └── Przelewy24Provider.php
│   ├── config/
│   │   ├── ai.php        # dostawcy AI + ceny tokenów
│   │   ├── currency.php  # waluty, kursy, mapowanie krajów
│   │   ├── services.php  # konfiguracja Przelewy24
│   │   └── cors.php
│   ├── database/
│   │   ├── migrations/   # users, plans, conversations, messages, wallets, transactions...
│   │   └── seeders/      # PlansSeeder (Free/Basic/Pro/Enterprise) + admin
│   └── routes/
│       ├── api.php       # wszystkie endpointy
│       ├── web.php
│       └── console.php   # schedulery (reset limitów)
│
└── frontend/              # Next.js
    └── src/
        ├── app/
        │   ├── page.tsx              # Landing page (Hero, Jak działa, FAQ, CTA)
        │   ├── chat/page.tsx         # Interfejs czatu (ChatGPT-like)
        │   ├── login/page.tsx
        │   ├── register/page.tsx     # rejestracja z przeniesieniem chatu gościa
        │   ├── dashboard/page.tsx    # panel użytkownika (profil, saldo, plany)
        │   └── admin/page.tsx        # panel administratora
        ├── components/
        │   ├── Hero.tsx
        │   ├── ChatInterface.tsx     # ★ chat + logika paywalla gościa
        │   ├── Sidebar.tsx           # historia rozmów
        │   └── PaywallModal.tsx      # modal "Opłać aby kontynuować"
        ├── lib/
        │   ├── api.ts                # klient API (Sanctum)
        │   ├── i18n.ts               # system tłumaczeń
        │   └── currency.ts           # formatowanie walut
        └── locales/
            ├── pl.json, en.json, de.json
```

## Szybki start (lokalnie)

### Backend
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
# Skonfiguruj DB w .env (PostgreSQL)
php artisan migrate --seed
php artisan serve   # http://localhost:8000
```
Domyślne konto admin: `admin@itchat.local` / `admin123`

### Frontend
```bash
cd frontend
cp .env.example .env.local
npm install
npm run dev   # http://localhost:3000
```

## Kluczowe decyzje architektoniczne

1. **`docs/business-rules.md` jest jedynym źródłem prawdy** dla logiki biznesowej.
2. **AI Provider Interface** — wymiana DeepSeek ⇄ Ollama ⇄ OpenAI bez zmian kodu (config).
3. **Payment Provider Interface** — wymiana Przelewy24 ⇄ PayU ⇄ Stripe bez przebudowy.
4. **BillingService** — jedyna brama do portfela. Saldo atomowo blokowane (`lockForUpdate`), nigdy < 0.
5. **Paywall po 2 wiadomościach** — rozmowa gościa w LocalStorage, przepisywana na konto po rejestracji.
6. **Eksport pytań anonimowy** — zgodność z RODO od początku.

## Wdrożenie produkcyjne
Pełna instrukcja: [`docs/deployment.md`](docs/deployment.md) (Apache reverse proxy, PM2, Let's Encrypt, backup).
