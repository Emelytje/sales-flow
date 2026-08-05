# SalesFlow Enterprise

A premium, production-ready **sales CRM** built in modern PHP 8.3 + MySQL 8 with
a vanilla-JS PWA front end. No frameworks, no Composer required — it runs on
budget shared hosting (InfinityFree) and scales cleanly to a VPS.

Soft rose · warm beige · dark rose design system, glassmorphism, light/dark
mode, mobile-first.

---

## Highlights

- **CRM** — customers, contacts, notes, activities, timeline, pipeline, lead
  score, priority, duplicate detection, sectors, labels, custom fields.
- **Callboard** — one-customer-at-a-time calling screen with auto stopwatch,
  9 outcome actions, auto-scheduled follow-ups, daily goal and leaderboard.
- **CTI / screen-pop** — inbound calls resolve the caller against your database
  and pop the name to the right rep. Works with **MicroSIP (free)**, 3CX,
  Zadarma, Twilio. Click-to-call via `tel:` links. See
  [`docs/CTI-SETUP.md`](docs/CTI-SETUP.md).
- **Agenda** — month/week/day calendar, conflict + travel-time checks,
  colour-coded events, free video meetings.
- **Self-booking** — public booking page per rep, availability slots, admin
  approval, confirmation emails, auto-created meeting.
- **Quotations** — line-item editor with live totals, branded printable PDF,
  tokenised public page with **digital signature** capture.
- **Map & routes** — all customers on an interactive map (**free
  OpenStreetMap**, no billing), navigate links, geocoding.
- **Reports** — team performance, revenue/activity charts, pipeline, CSV
  (Excel) + PDF export.
- **REST API** — bearer-token auth, customers/contacts/quotations/activities,
  signed outbound webhooks.
- **PWA** — installable, offline app-shell, Web Push (VAPID).
- **Security** — PDO prepared statements, CSRF, XSS escaping, bcrypt, 2FA
  (TOTP), rate limiting, RBAC (admin/manager/sales), audit log.

## Every integration has a **free** path

| Capability | Free option (default) | Paid/optional upgrade |
|---|---|---|
| Telephony / screen-pop | MicroSIP + our CTI endpoints | Twilio, 3CX, Zadarma |
| Video meetings | **Jitsi Meet** (no account) | Microsoft Teams |
| Map & routes | **OpenStreetMap + Leaflet** | Google Maps |
| Geocoding | **Nominatim** | Google Geocoding |
| VAT validation | **VIES** (EU) | — |
| Email | SMTP (e.g. Gmail) | any provider |
| Push | self-generated VAPID keys | — |

## Tech stack

PHP 8.3 · MySQL 8 · vanilla JS · HTML5/CSS · PWA · MVC · no frameworks.

## Folder structure

```
public/              Web root (front controller, assets, PWA, installer)
  index.php          Single entry point
  install.php        Installation wizard
  assets/            css · js · icons
  manifest.json · service-worker.js · offline.html
app/
  Core/              Router, Database (PDO), Auth, Session, Csrf, Validator,
                     RateLimiter, Mailer (SMTP), Totp, View, Icons, helpers…
  Controllers/       Web + Api/ controllers
  Models/            Customer, Contact, Activity, Quotation, Event, User…
  Services/          Cti, Stats, Availability, Meeting, Geo, WebPush, Teams,
                     MicrosoftGraph, Webhook, Notification
  Middleware/        Auth, Guest, ApiAuth
  Views/             PHP templates (layouts, pages, emails, partials)
config/              config.php
database/            schema.sql · seed.sql · migrations/
cron/                generate_vapid.php · geocode.php
routes/              web.php · api.php
storage/             logs · cache · uploads
docs/                CTI-SETUP.md
```

## Installation

### Option A — Installation wizard (recommended)

1. Upload the project. Point your web root at `/public` (VPS) or upload
   `public/`'s contents as your web root with `app/`, `config/`, etc. one
   level above.
2. Visit `https://your-domain.tld/install.php`.
3. The wizard checks requirements, tests the database, imports the schema +
   seed data, and creates your admin account and `.env`.
4. **Delete `public/install.php`** afterwards.

### Option B — Manual

```bash
cp .env.example .env      # fill in DB + app settings
mysql your_db < database/schema.sql
mysql your_db < database/seed.sql
# create an admin user (bcrypt hash), then log in
```

## Configuration

All settings live in `.env` (see `.env.example`). Integrations activate only
when their keys are present; the app is fully usable with none of them.

Optional cron jobs:

```cron
*/15 * * * *  php /path/cron/geocode.php     # fill customer coordinates (free)
```

Web Push keys:

```bash
php cron/generate_vapid.php   # prints VAPID_* lines for your .env
```

## Security notes

- Serve over HTTPS (enforced in `public/.htaccess`).
- Keep `app/`, `config/`, `database/`, `storage/` outside the web root, or rely
  on the shipped `.htaccess` deny rules.
- Set a `cti_webhook_secret` in Settings → telephony before exposing the CTI
  webhook.

## REST API

```
POST /api/v1/auth/token     { email, password } → { token }
GET  /api/v1/customers       Authorization: Bearer <token>
POST /api/v1/customers
GET  /api/v1/quotations
POST /api/v1/activities
POST /api/v1/webhooks        register signed outbound webhooks
```

## License

Proprietary — © SalesFlow Enterprise. All rights reserved.
