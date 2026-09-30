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

## Project conventions

- Every user-facing string goes through `__()`. French source strings are the keys; translations live in `lang/app/{locale}.json` (kept in sync by a test) and enum labels in `lang/{locale}/enums.php`.
- Use Tailwind logical utilities (`ms-`, `me-`, `ps-`, `pe-`, `start-`, `end-`, `text-start`) so the Arabic layout mirrors automatically.
- Distance queries only go through `App\Support\Geo\GeoQuery` / the `HasGeoLocation` scopes, so the database engine can change later (PostGIS).
- Secrets only in `.env`; every key is documented in `.env.example`.
- Ranking/review fields (`rating_avg`, `bayesian_score`, review `status`...) are never mass assignable.
