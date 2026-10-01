# City Services Directory

A local directory for one Moroccan city (multi-city ready): residents and visitors find artisans, mechanics, pharmacies, restaurants and more, read ratings and leave reviews. The platform earns money only through clearly labeled sponsored slots and paid business plans; **organic ranking and reviews can never be bought**.

- Product spec: [`docs/SPEC.md`](docs/SPEC.md)
- Implementation plan and progress: [`docs/PLAN.md`](docs/PLAN.md)

## Stack

Laravel 13 · Livewire 4 + Alpine.js · Tailwind CSS 4 · Filament 5 (`/admin`) · MySQL 8 (spatial `POINT`, `ST_Distance_Sphere`) · Redis + Horizon · Laravel Scout + Meilisearch · Spatie permission / medialibrary / translatable / sitemap / activitylog · Leaflet + OpenStreetMap-compatible tiles · Pest, Pint, Larastan.

Locales: `fr` (default), `ar` (RTL), `en`. Every public URL is prefixed: `/fr/...`, `/ar/...`, `/en/...`.

## Local setup with Laravel Sail (recommended)

Requirements: Docker Desktop. On Windows, Sail needs **WSL2 with a Linux distribution** (e.g. `wsl --install -d Ubuntu`); clone the project inside the WSL filesystem for good performance.

```bash
cp .env.example .env
# Install PHP dependencies with a throwaway container (no local PHP needed)
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed      # reference data + fake dev data (local env only)
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Open http://localhost → redirects to `/{locale}/{city}`.

| Service | URL |
|---|---|
| App | http://localhost |
| Admin panel | http://localhost/admin |
| Horizon (queues) | http://localhost/horizon (admins) |
| Mailpit | http://localhost:8025 |
| Meilisearch | http://localhost:7700 |

### Development accounts

`DevDataSeeder` (runs automatically in the `local` environment only, never in production) creates:

- Admin: `admin@example.test` / `password`
- Moderator: `moderator@example.test` / `password`

Public login uses a Moroccan mobile number + OTP. With `OTP_DRIVER=log` the code is written to `storage/logs/laravel.log`.

For a real staff account: `sail artisan app:make-admin 0612345678 --email=you@example.com` (use `--role=moderator` for moderators).

### The launch city

Edit `database/seeders/data/city.json` (city name in fr/ar/en, center coordinates, quartiers), set `DIRECTORY_DEFAULT_CITY` to its slug, then `sail artisan db:seed --class=CitySeeder`. Seeders are idempotent.

## Without Sail (quick checks on the host)

Horizon requires the `pcntl`/`posix` extensions (Linux/macOS only). On Windows install dependencies with:

```bash
composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
```

The test suite can run against in-memory SQLite (geo queries fall back to a haversine formula):

```bash
composer test:sqlite
```

The MySQL run inside Sail remains the reference (`sail test`), since production uses MySQL spatial functions.

## Quality

```bash
sail test              # Pest (MySQL "testing" database created by Sail)
sail composer lint     # Pint (check) + Larastan level 6
sail composer format   # Pint (fix)
```

## Search (Meilisearch)

```bash
sail artisan search:sync --import   # push index settings + synonyms, then index all published businesses
```

Synonyms edited in the admin (`Contenu › Synonymes`) are pushed automatically by a queued job.

## Pharmacies de garde import

Admin › Contenu › Pharmacies de garde › *Importer un CSV* (max 512 KB / 2,000 rows, all or nothing):

```csv
pharmacie,date,debut,fin
pharmacie-atlas,2026-10-05,20:00,09:00
```

`pharmacie` is the business slug (it must be a published business in the "Pharmacie de garde" category). A `fin` earlier than `debut` ends the next morning.

## Billing

Plans and sponsored placements are paid manually (bank transfer, CashPlus/Wafacash). The flow:

1. The owner orders from **Dashboard → Formules et paiements**. Prices always come from the database (`plans`, `placement_rates`), never from the form.
2. A pending payment is created with a reference (`P-XXXXXXXX`) to write on the transfer. A sponsored slot is held for `PAYMENT_PENDING_HOLD_HOURS` (72 h by default). Uploading a receipt extends the hold to 7 days.
3. An admin (permission `manage payments`; moderators cannot) validates it in **Filament → Monétisation → Paiements**. Validation activates the subscription or placement and issues the invoice (`F-YYYY-NNNNN`, sequential per year, immutable once issued, PDF rendered on first download).
4. `billing:maintain` runs hourly. It cancels unpaid orders that have no receipt, ends finished plans and placements (the business falls back to the free plan; extra photos are hidden, not deleted) and sends reminders 7 days and 1 day before the end.

Sponsored inventory is one business per city × category × (neighborhood or whole city) × position 1–3 at any time. The admin calendar is at **Monétisation → Calendrier des emplacements**, and rates are under **Tarifs sponsorisés**: a city/category rate overrides the default one, and a neighborhood slot costs `neighborhood_percent` % of the city-wide price.

Before selling anything, fill the `PAYMENT_*` and `INVOICE_*` keys in `.env`. The invoice legal details and the 20 % VAT rate are placeholders. The CMI card gateway is a documented stub: its routes return 404 until `CMI_ENABLED` is set and `App\Services\Billing\Gateways\CmiGateway` is implemented.

## Security

- Nonce-based Content-Security-Policy and hardening headers on every response (`App\Http\Middleware\SecurityHeaders`, `config/security.php`). Scripts are bundled by Vite (Livewire and Alpine included), so no inline script runs without the nonce.
- Admin-authored HTML is sanitized on save and on render (`App\Support\Security\RichText`); every user-supplied value is escaped with `{{ }}`.
- Business websites and social links must be absolute `http(s)` URLs (`App\Support\Security\SafeUrl`), phones are normalized to E.164 before they reach an `href`.
- URL/Livewire parameters are whitelisted (`App\Services\Search\SearchFilters`), Livewire updates and the contact event endpoint are rate-limited, city/category slugs cannot shadow application routes.
- Privacy: the visitor location stays in the session (rounded to ~110 m) and never in URLs; statistics use a daily-rotating HMAC instead of IPs; old raw events and OTP codes are pruned daily.
- Billing: amounts, statuses and invoice links can only change through `App\Support\Billing\BillingGate` (admin with payment rights, or the scheduler). Receipts and invoices live on the private disk and are served only to their owner or to payment admins.
- Production: set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `TRUSTED_PROXIES` if behind a load balancer; HTTPS is then forced for generated URLs.

## Project conventions

- Every user-facing string goes through `__()`. French source strings are the keys; translations live in `lang/app/{locale}.json` (kept in sync by a test) and enum labels in `lang/{locale}/enums.php`.
- Use Tailwind logical utilities (`ms-`, `me-`, `ps-`, `pe-`, `start-`, `end-`, `text-start`) so the Arabic layout mirrors automatically.
- Distance queries only go through `App\Support\Geo\GeoQuery` / the `HasGeoLocation` scopes, so the database engine can change later (PostGIS).
- Secrets only in `.env`; every key is documented in `.env.example`.
- Ranking/review fields (`rating_avg`, `bayesian_score`, review `status`...) are never mass assignable.
