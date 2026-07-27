# ChittyFund — Chitty Fund Management Platform

A production-oriented, full-stack Chitty Fund management platform for a legally
compliant, secure Chitty Fund company operating in India. Built with Laravel 12,
Inertia.js, and React (TypeScript). Compliance-related rules are configurable
per company because operational rules vary by state and company policy.

> **Status: All 5 phases complete.** Foundation, Operations, the real-time
> auction engine, the notification/staff/support layer, and the React Native
> (Expo) customer mobile app are all built and tested. The web platform covers
> chitty management, onboarding/KYC, installments, payments with PDF receipts,
> reconciliation, Excel reports, live auctions, a multi-channel notification
> engine, staff administration, prize-payout maker-checker, audit logs, and
> support tickets. The mobile app (in `../chittyfund-mobile`) consumes the
> `/api/v1` endpoints.

---

## Tech Stack

| Layer        | Technology |
|--------------|------------|
| Backend      | Laravel 12, PHP 8.3+ |
| Web frontend | Inertia.js + React 19 + TypeScript, Vite, Tailwind CSS 4, shadcn-style UI, Lucide icons, Recharts |
| Auth         | Session auth for web (Inertia); Laravel Sanctum tokens for the mobile/integration API |
| AuthZ        | Spatie Laravel Permission (roles + granular permissions) + Laravel Policies/Gates |
| Audit        | Spatie Activitylog |
| Exports      | Laravel Excel (Maatwebsite) |
| Database     | MySQL 8+ in production (SQLite for local dev) |
| Cache/Queue  | Database drivers locally; Redis in production |
| Real-time    | Laravel Reverb / Pusher with a secure polling fallback (Phase 3) |

Money is stored in `decimal(15,2)` columns — **never floats**. Public-facing
records are addressed by **ULID**, never by auto-increment id.

---

## Requirements

- PHP **8.3+** with extensions: `mbstring, intl, bcmath, pdo, openssl, curl, gd, zip`
- Composer 2.x
- Node.js **20+** and npm
- MySQL 8+ (production) — SQLite works out of the box for local dev
- Redis (optional locally; recommended in production)

---

## Quick Start (Local Development)

```bash
# 1. Install PHP and JS dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database — SQLite is the zero-config default
touch database/database.sqlite
php artisan migrate:fresh --seed

# 4. Build assets (or use the dev server in step 5)
npm run build

# 5. Run the app (two terminals, or use the combined script below)
php artisan serve          # http://localhost:8000
npm run dev                # Vite dev server with HMR
```

Then open **http://localhost:8000**.

### Demo accounts

All seeded accounts use the password **`password`**.

| Role            | Email                          | Lands on          |
|-----------------|--------------------------------|-------------------|
| Super Admin     | `superadmin@chittyfund.test`   | Admin dashboard   |
| Company Owner   | `owner@chittyfund.test`        | Admin dashboard   |
| Branch Manager  | `manager@chittyfund.test`      | Admin dashboard   |
| Accountant      | `accountant@chittyfund.test`   | Admin dashboard   |
| Collection Staff| `collector@chittyfund.test`    | Admin dashboard   |
| Auction Officer | `auction@chittyfund.test`      | Admin dashboard   |
| Customer        | `priya@chittyfund.test`        | Customer portal   |
| Customer        | `rahul@chittyfund.test`        | Customer portal   |

(There are also `customer1@` … `customer14@chittyfund.test`. The first few
customers are left in a *pending* registration/KYC state to exercise the
approval queues.)

---

## Project Structure (Phase 1)

```
app/
  Enums/                 ChittyStatus, KycStatus, RegistrationStatus, UserType, MembershipStatus
  Http/
    Controllers/
      Admin/DashboardController.php      Back-office dashboard (real KPIs)
      Portal/DashboardController.php     Customer dashboard (real data)
      Api/V1/AuthController.php          Sanctum token auth for mobile
    Middleware/EnsureUserType.php        Keeps staff/customer surfaces separate
    Resources/UserResource.php           API resource
  Models/                Company, Branch, User, StaffProfile, CustomerProfile,
                         ChittyScheme, Chitty, ChittyMembership, Setting
    Concerns/            HasUlid, BelongsToCompany
  Policies/              ChittyPolicy, CustomerProfilePolicy, BranchPolicy
  Support/               Rbac (roles/permissions source of truth), ApiResponse
database/
  migrations/            Phase 1 schema
  factories/             Model factories (used by seeders + tests)
  seeders/               RolePermissionSeeder, CompanySeeder, DemoDataSeeder
lang/
  en/messages.php        English UI strings
  ml/messages.php        Malayalam UI strings
resources/js/
  pages/admin/dashboard.tsx
  pages/portal/dashboard.tsx
  components/stat-card.tsx, app-sidebar.tsx (role-aware nav)
  hooks/use-permissions.ts
  lib/format.ts          INR currency + Indian date formatting
  lib/i18n.ts            useTranslations() / t()
routes/
  web.php                / , /dashboard (redirects), /admin/*, /portal/*
  api.php                /api/v1/* (Sanctum)
```

---

## Architecture Notes

- **Tenancy:** Every domain record carries `company_id` (and often `branch_id`).
  The platform runs as a single company today and can grow into a multi-company
  SaaS without schema changes.
- **Authorization is server-side.** Spatie permissions + Laravel policies decide
  access. The frontend `usePermissions()` hook only shows/hides UI — it is never
  the security boundary. Super Admin passes all checks via a `Gate::before` rule.
- **Two audiences, one auth.** `users.type` is `staff` or `customer`.
  `EnsureUserType` middleware and post-login routing keep the admin area and the
  customer portal separate.
- **Versioned API.** The web dashboard uses Inertia (no REST needed). The mobile
  app and integrations use the token-authenticated `/api/v1` surface.
- **Localization.** UI strings live in `lang/{en,ml}/messages.php`, are shared to
  React on every Inertia response, and are resolved with `t('group.key')`. Adding
  a language = adding a `lang/<code>/messages.php` and extending the supported
  list in `HandleInertiaRequests`.

---

## Common Commands

```bash
php artisan migrate:fresh --seed   # rebuild DB with demo data
php artisan route:list             # inspect routes
npm run build                      # production asset build
npm run dev                        # Vite dev server (HMR)
npx tsc --noEmit                   # TypeScript type-check
npm run lint                       # ESLint (autofix)
./vendor/bin/pint                  # PHP code style (if installed)
php artisan test                   # PHP test suite
```

---

## API (v1) — available now

| Method | Endpoint              | Auth        | Purpose                    |
|--------|-----------------------|-------------|----------------------------|
| GET    | `/api/v1/health`                | public   | Health check                     |
| POST   | `/api/v1/auth/login`            | public   | Customer login → token           |
| POST   | `/api/v1/auth/logout`           | sanctum  | Revoke current token             |
| GET    | `/api/v1/profile`               | sanctum  | Current user + profile           |
| GET    | `/api/v1/chitties`              | sanctum  | Customer's memberships           |
| GET    | `/api/v1/chitties/{ulid}`       | sanctum  | Chitty detail + ledger           |
| GET    | `/api/v1/installments`          | sanctum  | Customer's installments          |
| GET    | `/api/v1/payments`              | sanctum  | Payment history                  |
| POST   | `/api/v1/payments/initiate`     | sanctum  | Start an online payment          |
| GET    | `/api/v1/auctions`              | sanctum  | Auctions the customer can join   |
| GET    | `/api/v1/auctions/{ulid}`       | sanctum  | Auction state (poll)             |
| POST   | `/api/v1/auctions/{ulid}/bid`   | sanctum  | Place a bid (validated + rated)  |
| POST   | `/api/v1/auth/register`         | public   | Customer registration → token    |
| POST   | `/api/v1/devices`               | sanctum  | Register a push device token     |
| GET    | `/api/v1/notifications`         | sanctum  | In-app notifications + unread    |
| POST   | `/api/v1/webhooks/{gateway}`    | signature| Gateway webhook (idempotent)     |

Login expects `email`, `password`, `device_name`. Responses use a standard
`{ success, message, data }` envelope. Webhooks are authenticated by the
provider signature verified against the raw body, never a session/token.

---

## Phase 2 — Operations (available now)

**Chitty management:** list/create (3-step wizard)/edit/detail, lifecycle state
machine (draft → open → active → auction → closed/cancelled), subscriber
enrollment (KYC-gated), scheme catalog, and one-click installment-schedule
generation.

**Customer onboarding & KYC:** customer directory, registration
approve/reject, KYC document upload (private disk) with per-document
approve/reject review and overall KYC verification. Customers manage their own
profile, documents, and payout bank accounts (account number encrypted at rest).

**Installments & payments (financial integrity):**
- Money is computed with **bcmath** on decimal strings — never floats
  (`App\Support\Money`).
- Schedule generation and every settlement run inside DB transactions with row
  locks.
- **Offline collections** recorded by staff issue a receipt immediately;
  **online payments** go through a gateway abstraction (`PaymentGateway`
  contract; Razorpay signature verification implemented) and are only marked
  successful by a **signature-verified, idempotent webhook** — never by a client
  redirect.
- Late fees are configurable per chitty (none/fixed/percent + grace period) and
  recomputed at settlement time.
- **PDF receipts** (dompdf) downloadable by customers; **Excel exports** for
  collections and overdue reports (Laravel Excel).

**Reconciliation & reports:** transaction reconciliation queue, collections
report (daily chart + summary) and overdue report, both exportable.

**Mobile API additions:** `/api/v1/chitties`, `/api/v1/installments`,
`/api/v1/payments` (+ initiate), and the public signed `/api/v1/webhooks/{gateway}`.

## Phase 3 — Real-time Auctions (available now)

**Server-authoritative auction engine.** Nothing about a bid is trusted from
the client — the auction row is locked, and status, timer, eligibility, bid
bounds and the increment-over-best rule are all re-verified server-side:

- **Lifecycle:** schedule → start → (pause/resume) → finalize / cancel, each
  transition row-locked, audit-logged and broadcast.
- **Eligibility** re-checked live on entry and on every bid (active membership,
  not already prized, KYC verified, no overdue dues).
- **Bidding:** configurable method (default *max-discount*), bid increment,
  min/max bounds, **idempotency keys**, **rate limits**, server timestamps, and
  **anti-sniping auto-extension** of the close time.
- **Finalization (all server-side):** picks the winning bid, computes
  prize = chit − discount, foreman commission, distributable dividend and
  per-member dividend; flags the winning bid + membership; links the period's
  schedule; and creates a **pending prize payout** (maker-checker).
- **Immutable bid ledger** + published result + officer control room.

**Real-time transport with a polling fallback.** Broadcast events
(`BidPlaced`, `AuctionStateChanged`) fire on a **presence channel**
(`auction.{ulid}`) authorized server-side in `routes/channels.php`. The
frontend `useAuctionState` hook renders a **server-synchronised countdown** and
**polls a state endpoint** by default (works on shared hosting with no
WebSockets). When Laravel Echo + Reverb/Pusher are configured it upgrades to
instant push automatically — same code path.

> **Enabling real-time push:** set `BROADCAST_CONNECTION=reverb` (or `pusher`)
> and the matching keys in `.env` (see `.env.example`), run a Reverb server
> (`php artisan reverb:start`) or point at Pusher, and wire Laravel Echo on the
> client. Without this, the polling fallback keeps auctions fully functional.

## Phase 4 — Notifications, Staff & Support (available now)

**Notification engine.** A configurable, multi-channel engine:
- Channels: **in-app** (database), **email** (SMTP), **WhatsApp** (provider
  abstraction — Meta Cloud API driver + a log driver fallback), and **push**
  (abstraction for the Phase 5 mobile app). All external channels are **queued**.
- **Configurable templates** per event + channel (`{{variable}}` tokens),
  company-overridable, with WhatsApp approval-status fields.
- **Per-user channel preferences**, **delivery logging** (every send/failure
  recorded for the admin log + retries), and an **in-app notification center**
  with an unread badge shared on every page.
- Triggers wired into real flows: welcome, KYC approved/rejected, payment
  received, installment due/overdue, auction scheduled/result/winner, support
  updates.

**Scheduled jobs** (`routes/console.php`):
- `chittyfund:finalize-expired-auctions` — every minute; auto-closes live
  auctions past their server end time.
- `chittyfund:send-reminders` — daily; due-soon + overdue installment reminders.

**Staff management.** Create staff, assign roles + branch, edit, **suspend /
reactivate**, **login-history** tracking, and per-staff recent activity.

**Prize payouts (maker-checker).** Payouts created pending at auction
finalization are **approved** then **marked paid** by `payouts.approve` staff —
distinct from the finalizer.

**Audit-log viewer** (Spatie Activitylog) and **support tickets** (customer
raises + messages; staff replies, assigns, changes status; customer notified).

## Background workers (production)

```bash
# Scheduler (add to crontab)
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1

# Queue worker (systemd/supervisor) — processes queued notifications, etc.
php artisan queue:work --tries=3
```

## Roadmap

- **Phase 1 — Foundation (done):** architecture, schema, RBAC, auth, base UI,
  admin + customer dashboards, seeders.
- **Phase 2 — Operations (done):** chitty management, onboarding, KYC,
  installment schedules, payments & receipts, reconciliation, reports.
- **Phase 3 — Real-time Auctions (done):** auction lifecycle, server-validated
  bidding, finalization + prize/commission/dividend, presence broadcasting +
  polling fallback, live rooms.
- **Phase 4 — Notifications, Staff & Support (done):** multi-channel notification
  engine with templates/preferences/logging, scheduled reminders + auto-finalize,
  staff management + login history, payout maker-checker, audit viewer, support
  tickets.
- **Phase 4:** notification engine (in-app, email, WhatsApp, push), staff
  workflows, audit logs, advanced reporting.
- **Phase 5 — Mobile app (done):** React Native (Expo Router + TS) customer app
  in `../chittyfund-mobile` — login/registration, chitties + installment ledger,
  online payment initiation, live auction bidding, notifications, profile,
  secure token storage, biometric unlock, push registration, deep links.

---

## Security (baseline established in Phase 1; hardened through later phases)

- Passwords hashed with Laravel defaults; strong-password rules on registration.
- CSRF protection on web; Sanctum tokens for mobile.
- Company-scoped policies prevent cross-tenant data access.
- ULIDs avoid leaking sequential ids.
- No secrets in source; all keys via `.env` (see `.env.example`).
- KYC documents and receipts will use a private disk with signed URLs (Phase 2).

---

## License

Proprietary — all rights reserved (update as appropriate for your organization).
