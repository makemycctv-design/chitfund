# ChittyFund Mobile (Expo)

The customer companion app for the ChittyFund platform, built with **Expo
(React Native) + TypeScript + Expo Router**. It consumes the Laravel backend's
versioned **`/api/v1`** endpoints.

## Features

- **Auth:** email/password login + self-registration (issues a Sanctum token).
- **Secure token storage** in the device keychain/keystore (`expo-secure-store`).
- **Biometric unlock** (Face ID / fingerprint) on app entry where supported
  (`expo-local-authentication`).
- **Home dashboard:** next due amount, overdue total, active chitties.
- **Chitties:** list + detail with the per-period installment ledger.
- **Payments:** history + online payment initiation (gateway-backed).
- **Auctions:** list + **live bidding room** with a server-synced countdown,
  best-bid display, quick/custom bids, and results — via polling (upgrades to
  WebSockets when the backend broadcaster is configured).
- **Notifications:** in-app list + **push registration** (`expo-notifications`),
  with **deep links** routing notification taps into the app.
- **Profile:** account + KYC status, sign out.

## Project structure

```
app/                         Expo Router routes
  _layout.tsx                Providers + push deep-link handling
  index.tsx                  Auth gate → app or login
  (auth)/login.tsx, register.tsx
  (app)/_layout.tsx          Tabs + auth guard + biometric lock
  (app)/index.tsx            Home
  (app)/chitties/            list + [id] detail (ledger + pay)
  (app)/auctions/            list + [id] live bidding room
  (app)/payments.tsx, notifications.tsx, profile.tsx
src/
  api/                       client (bearer + envelope), typed endpoints, types
  auth/                      AuthContext, secure storage, biometric
  components/ui.tsx          shared UI primitives
  hooks/useAuctionState.ts   polling + countdown
  lib/push.ts                push token registration
  theme.ts, config.ts
```

## Configuration

Set the API base URL in `app.json` → `expo.extra.apiUrl` (defaults to
`http://localhost:8000/api/v1`). On a physical device, use your machine's LAN IP
instead of `localhost`.

## Run

```bash
npm install
npm run start        # Expo dev server (open in Expo Go or a dev build)
npm run typecheck    # tsc --noEmit
```

> Push notifications and biometrics require a physical device / dev build; they
> no-op gracefully on simulators. The app talks to the running Laravel backend.
