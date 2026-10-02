# Implementation Plan — City Services Directory

Source of truth: [`SPEC.md`](SPEC.md). Section numbers below (§) refer to it.

## Working rules (apply to every phase)

- One phase at a time. End of phase = `sail artisan test` green + `sail pint --test` clean + `sail php vendor/bin/phpstan analyse` (Larastan level 6+) clean + written summary → wait for approval.
- Small commits per coherent feature (`feat(reviews): …`, `test(ranking): …`).
- No secrets in code; every new key goes into `.env.example` with a comment.
- Pest tests for every model rule, ranking logic, review rules, permissions and payments (§0.7).
- Package versions: resolve the latest stable with Composer/npm at setup time and pin them in `composer.lock` / `package-lock.json`. No assumed versions.
- All UI uses Tailwind logical utilities (`ms-/me-/ps-/pe-/start-/end-`) so RTL works automatically.

## Environment notes (checked on this machine)

- Available: PHP 8.4 (host), Composer, Node, Git, Docker Desktop 29.
- **WSL has no Linux distro installed.** Sail's `sail` script is bash and runs best from WSL2 (the project should also live on the WSL filesystem for acceptable Docker I/O speed). Recommended: `wsl --install -d Ubuntu` before Phase 1. Fallback: run `docker compose` directly from Windows with the Sail-generated `docker-compose.yml` (slower bind mounts).
- The folder was not a git repository; Phase 1 started with `git init` (branch `main`).
- Docker Desktop fails to start ("Docker Desktop is unable to start") because no WSL2 distribution is installed.
- Latest stable versions on Packagist (checked 2026-09-30): Laravel framework v13.34.0, Livewire v4.4.7, Filament v5.9.0, Scout v11.8.0, Pest v5.2.1, Larastan v3.12.2. Re-checked and pinned via `composer.lock` at Phase 1 setup.

---

## Progress

| Phase | Status |
|---|---|
| 1. Foundation | Done (production database: PostgreSQL, see "Production data") |
| 2. Directory | Done (security-first pass included) |
| 3. Reviews & moderation | Done |
| 4. Owners | Done |
| 5. Monetization | Done |
| 6. Polish & launch | Done, awaiting review |

### Phase 1 decisions (defaults taken while questions were open)

- **Bare Laravel + Livewire** instead of the official starter kit: auth is phone + OTP, so the starter kit's Fortify email flows would have been stripped anyway.
- **Latitude/longitude columns + derived POINT**: `latitude`/`longitude` decimals are the source of truth (easy JSON/Meilisearch `_geo`, SQLite tests); the `POINT SRID 4326` column is kept in sync on save and used by `ST_Distance_Sphere` on MySQL.
- **Admin login** uses email + password (Filament); public login uses phone + OTP. Staff accounts are created with `php artisan app:make-admin`.
- **Translations**: French strings are the keys, `lang/app/{fr,ar,en}.json` holds app strings, `laravel-lang/common` provides framework strings; fallback locale is `en` so missing vendor keys never show raw.
- **Reviews per day** default and **reply moderation** are decided in Phase 3 (proposal kept: 5/day, replies publish immediately with banned-word filter).
- **Docker could not start on this machine** (WSL is not installed), so Phase 1 was verified with host PHP 8.4 + SQLite. The MySQL-specific code (spatial column/index, `ST_Distance_Sphere`) must still be run once under Sail: `sail artisan migrate:fresh --seed && sail test`.

---

## Phase 1 — Foundation

**Goal:** a booting app with auth, roles, locales/RTL, the full schema, seed data and an empty Filament panel.

1. **Project bootstrap**
   - `git init`; create Laravel app (latest stable) with Livewire starter kit; add Sail with services `mysql`, `redis`, `meilisearch`, `mailpit`.
   - Keep `docs/SPEC.md`, `docs/PLAN.md` in repo; add `README.md` (Sail setup, common commands).
   - Install and configure: Pest, Pint, Larastan (`phpstan.neon`, level 6), Horizon, Scout + Meilisearch driver, Filament, Spatie `permission`, `medialibrary`, `translatable`, `sitemap`, `activitylog`.
   - `Model::preventLazyLoading()`, `preventSilentlyDiscardingAttributes()` outside production.
   - `.env.example` documented: DB, Redis, Meilisearch, map tiles URL/attribution, OTP driver, CNDP number, ranking constants, payment details, Socialite keys.
   - GitHub Actions–ready `composer test` / `composer lint` scripts (CI file optional, ask).
2. **Localization & layout**
   - `SetLocale` middleware + route group `/{locale}` (`fr` default, `ar`, `en`); redirect `/` → `/fr`.
   - `lang/{fr,ar,en}` translation files; locale switcher preserving the current route.
   - Base Blade layout: `lang` + `dir` attributes, Arabic font (IBM Plex Sans Arabic / Noto Sans Arabic) only for `ar`, hreflang component, mobile-first shell (header, bottom nav, footer with CNDP number).
   - Locale-aware formatting helpers (dates, numbers, MAD price).
3. **Schema: migrations, models, enums, factories** (§5) — all tables in one pass:
   - Geography: `cities`, `neighborhoods` (translatable names, optional `POINT`/`POLYGON`).
   - Catalogue: `categories` (2-level guard), `businesses` (`POINT SRID 4326` + spatial index, soft deletes), `business_category` (with `is_primary`), `business_hours` (multiple rows per day for split hours, 24/7 flag), `business_special_hours`.
   - Reviews: `reviews` (+ `review_revisions` for edit history), `review_replies`, `review_votes`, `reports` (morph), `business_claims`, `favorites`, `edit_suggestions`.
   - Money: `plans`, `subscriptions`, `sponsored_placements`, `payments`, `invoices` (+ `invoice_sequences` per year).
   - Analytics: `contact_events`, `business_stats_daily`.
   - Misc: `pharmacies_de_garde`, `pages` (translatable), `search_synonyms`, `otp_codes`.
   - PHP backed enums for every status/type/method column; model casts; relationships; factories for all models.
   - `HasGeoLocation` trait + `scopeWithinDistance()/orderByDistance()/selectDistance()` wrapping `ST_Distance_Sphere` behind a `GeoQuery` class (swappable for PostGIS).
4. **Auth: phone + OTP**
   - Moroccan phone value object: normalize `06…/07…/+2126…/+2127…` → E.164, validation rule.
   - `OtpSender` contract + drivers `log`, `sms` (stub), `whatsapp` (stub) selected via config.
   - OTP issue/verify service (hashed codes, expiry, max attempts); rate limits per phone and per IP.
   - Livewire login/register-by-phone screens; `phone_verified_at`; optional email/password and Google (Socialite) behind config flags.
5. **Roles & permissions**
   - Seeder for roles `member`, `business_owner`, `moderator`, `admin` and granular permissions; policies skeleton (`BusinessPolicy`, `ReviewPolicy`, `PaymentPolicy`…).
6. **Seeders**
   - `CitySeeder` reading `database/seeders/data/city.json` (sample file with placeholder city + quartiers for you to fill).
   - `CategorySeeder` with the full §16 list in fr/ar/en, icons, `is_emergency` flags.
   - `PlanSeeder` (Free / Pro / Premium with placeholder prices, editable later), `PageSeeder` (legal page stubs), `SearchSynonymSeeder` (spec examples).
   - `DevDataSeeder` (fake businesses/hours/reviews) guarded so it refuses to run in production.
7. **Filament**
   - Panel at `/admin`, access only for `admin`/`moderator`.
   - Basic resources: Cities, Neighborhoods, Categories (reorderable), Plans, Pages, Search synonyms, Users.
8. **Tests:** phone normalization, OTP flow + rate limits, locale routing/RTL attribute, category depth rule, geo scope (MySQL), role access to `/admin`, seeders run cleanly.

## Phase 2 — Directory

**Goal:** people can find and contact businesses.

**Deferred on purpose** (need later phases): claim flow and "suggest an edit" (phase 4; the CTA links to the contact page for now), report links (phase 3), admin CRUD for businesses and sponsored placements (phases 3/5), the full RankingService (phase 3; relevance currently = Meilisearch rank, then Bayesian score).

1. **Routing & SEO skeleton:** `/{locale}/{city}`, `/{locale}/{city}/{category}`, `/{locale}/{city}/{category}/{neighborhood}`, `/{locale}/{city}/b/{slug}`; slug binding scoped to city; breadcrumbs data.
2. **Opening-hours engine:** `OpeningHours` service in `Africa/Casablanca`: split hours, overnight ranges, 24/7, special hours override (Ramadan/Eid/holidays); outputs `open now`, `closes at`, `opens at 15:00`, `opens tomorrow`. Heavily unit-tested.
3. **Search:** Scout `Searchable` on businesses (+ categories for autocomplete) with filterable/sortable attributes and `_geo`; command to push index settings and `search_synonyms` to Meilisearch; admin changes re-sync via queued job.
4. **Home (Livewire):** search bar + autocomplete, "use my location" (optional), category grid (emergency highlighted), pharmacies de garde tonight, open-now-near-you (when located), recently reviewed.
5. **Results page (Livewire, `#[Url]` filters):** category, neighborhood, open now, min rating, price range, verified only, radius; sorts relevance/best rated/nearest/most reviewed/newest; pagination with SEO `rel` links; list/map toggle (Leaflet lazy-loaded, marker clustering, tile URL from `.env`).
   - Sponsored slot component (reads active `sponsored_placements`, labeled in 3 languages, excluded from organic list below). Placements are seeded manually until Phase 5.
6. **Business page:** header with categories/verified/open badge, WhatsApp (`wa.me` prefilled), Call, Directions (Google Maps/Waze), Share (Web Share API fallback), gallery, description, hours + special hours, mini map, price range, rating summary + distribution, reviews list (read-only for now), claim CTA, similar nearby (organic only).
7. **Contact events:** lightweight endpoint/Livewire action → queued job writing `contact_events` with source + session hash; nightly `AggregateContactEvents` job → `business_stats_daily`.
8. **Pharmacies de garde:** model queries + home block + dedicated page; Filament calendar resource + CSV import.
9. **Caching:** Redis cache for category counts/pages with tag or key-based invalidation via model observers.
10. **Tests:** opening-hours matrix, URL filter round-trip, sponsored not duplicated in organic list, sponsored label present in all locales, contact event logging, pharmacy-de-garde tonight query, CSV import.

## Phase 3 — Reviews & moderation

**Goal:** trustworthy reviews and a working moderation workflow.

1. **Review form (Livewire):** verified-phone gate, 1–5 stars required, optional text (min 20 chars), up to 5 photos, link to guidelines; one review per user/business; edit keeps `review_revisions`.
2. **Eligibility rules (`ReviewEligibility` service):** owner can't review own business nor same primary category in same city; rate limit N/day.
3. **Abuse detection (`ReviewAbuseDetector`):** account < 24h, same IP/device hash on same business, burst on one business, reviewer phone = business phone, banned-words list (config/admin) → `pending`; else `published`.
4. **Replies & votes:** owner reply once per review (editable, moderated status); helpful votes unique per user.
5. **Reports:** polymorphic report modal for business/review/reply/photo, rate limited.
6. **Rating recalculation:** `RecalculateBusinessRating` queued job on publish/edit/remove → `rating_avg`, `rating_count`, `bayesian_score`.
7. **`RankingService` (§7):** Bayesian average with per-city-category `C`, `m` from `config/ranking.php`; combined relevance score (Bayesian, text relevance, distance, completeness, recency, verified) with configurable weights; wired into result sorting.
8. **Filament moderation queues:** reviews, replies, photos, reports, edit suggestions, businesses; one-click approve/reject with reason → notification to user; all actions logged via activitylog.
9. **Notifications (database + mail):** new review on my business, reply to my review, content rejected with reason; `WhatsAppChannel` interface stub.
10. **Tests:** every eligibility and abuse rule, unique constraint, edit history, rating recalculation, Bayesian examples (1×5★ vs 80×4.7★), **paid plan has zero effect on organic score**, **only moderator/admin can change review status**, **payment status never affects review visibility**, moderation logging.

## Phase 4 — Owners

**Goal:** businesses can be added, claimed and managed.

1. **Add a business (Livewire wizard):** info, categories (one primary), location picker on Leaflet, hours; status `pending`.
2. **Duplicate detection:** same normalized phone, or similar name (normalized + similarity threshold) within 200 m via geo scope; shown to user before submit.
3. **Suggest an edit:** user-submitted diff → moderation queue.
4. **Claim flow:** OTP to the business phone, or document upload (RC, patente, CIN) → moderator review in Filament with document viewer; approval grants `business_owner`, sets `owner_id`, `is_claimed`; notifications.
5. **Owner dashboard `/dashboard`:** business switcher (multiple businesses), edit info/hours/special hours, media manager with plan-based photo limit, reviews inbox with reply + report.
6. **Profile completeness:** computed attribute/job feeding ranking.
7. **Statistics (paid plans):** views, WhatsApp/calls/directions/website clicks per day/week/month, organic vs sponsored, charts (lightweight chart lib, lazy loaded).
8. **Tests:** duplicate detection cases, claim approval grants role, owner can't edit/delete reviews, stats gated by plan, multiple-business isolation (owner A can't touch business B).

## Phase 5 — Monetization

**Goal:** plans and sponsored placements can be sold and paid manually.

**Done.** Decisions taken while questions are open:
- Invoice legal details and VAT (20 %, prices include VAT) are placeholders in `config/billing.php` / `.env`.
- Unpaid orders hold a sponsored slot for 72 h (7 days once a receipt is uploaded). Validation re-checks the slot.
- Placements are sold per week (max 12) or month (max 6) and start up to 6 months ahead. A neighborhood slot defaults to 60 % of the city-wide price.
- Renewing the same plan extends it from its current end. Switching plans starts now and cancels the old one (any proration is handled manually).
- Issued invoices are immutable and always written in French. Corrections go through a refund.
- CMI is a stub: its routes return 404 until it is implemented and enabled.

1. **Plans & features:** feature flags read from `plans.features`; `PlanFeatures` helper used across app (photo limits, stats, highlighted card, hide competitor sponsored on own page, priority moderation).
2. **`PaymentGateway` contract:** `manual` driver (bank transfer / CashPlus / Wafacash instructions from config, receipt upload) and `cmi` stub (config keys, callback/ok/fail routes, TODO skeleton).
3. **Upgrade & checkout flow** in owner dashboard; payment history.
4. **Admin payment validation** (Filament, receipt preview; admin only, moderators excluded) → activates subscription or placement via domain action, logged.
5. **Sponsored placements:** inventory per city + category (+ neighborhood) × position 1–3 × period; availability calendar in Filament and owner purchase flow; overlap prevention; impression/click tracking; rotation-ready design (`PlacementSelector` strategy).
6. **Invoices:** sequential number per year (locked row / transaction-safe), client legal info + ICE, PDF generation (package chosen at the time, e.g. DomPDF), download in dashboard.
7. **Scheduled jobs:** expiry of plans/placements, reminders at 7 days and 1 day (mail + database), graceful downgrade (extra photos hidden, not deleted).
8. **Tests:** payment validation activates correct product, rejected payment activates nothing, moderator cannot validate payments, invoice numbering (concurrency + year rollover), placement overlap, expiry/downgrade, reminders timing, organic ranking unchanged by paid plan (regression).

## Phase 6 — Polish & launch

**Done.** Notes:
- Legal pages ship with French drafts (`database/seeders/data/legal.php`) to be reviewed by a lawyer; Arabic/English readers see the French text until translated in the admin.
- No non-essential cookie exists yet: the consent banner records the choice and exposes `window.cookieConsent.allows('analytics')` for any future measurement script.
- Account deletion keeps reviews as "Utilisateur supprimé" by default (option to remove them), keeps payments/invoices (accounting), and is confirmed by an SMS code (or a sign-in < 15 min for accounts without a phone). Staff accounts cannot self-delete.
- Lighthouse could not be run from this machine; it is on the launch checklist in `docs/DEPLOY.md`.

1. **SEO:** per-page title/description, Open Graph, canonical, hreflang; JSON-LD `LocalBusiness` (specific subtype map per category) with geo, openingHours, `AggregateRating`, and `BreadcrumbList`; daily split sitemaps.
2. **PWA:** manifest + icons, service worker (static asset cache + offline fallback only).
3. **Performance pass:** responsive WebP images with width/height, lazy loading, N+1 audit, query/index review, cache review, Lighthouse run on mobile profile.
4. **Legal:** editable CGU, privacy, review guidelines, cookie policy, legal notice; cookie consent banner (non-essential off by default); CNDP number in footer.
5. **Account data rights:** JSON data export (queued, download link), account deletion with review anonymization ("Utilisateur supprimé") or removal on request; business correction requests.
6. **Ops:** `/health` route (DB, Redis, Meilisearch, queue), `docs/DEPLOY.md` (Nginx + PHP-FPM / Forge, Horizon, scheduler, Meilisearch, Redis, offsite daily backups, log rotation, HTTPS, Sentry config).
7. **Tests:** JSON-LD shape, sitemap generation, data export content, anonymization on deletion, health check.

---

## Open questions (please answer before or during Phase 1)

1. **Dev environment:** OK to install an Ubuntu WSL2 distro for Sail, or should I work with `docker compose` from Windows directly?
2. **City:** which city is the launch city? (I'll put a placeholder in `city.json` otherwise.)
3. **Starter kit:** use the official Laravel Livewire starter kit (includes its UI components), or a bare Laravel install + Livewire only?
4. **Review text minimum:** "min 20 characters if provided" — should whitespace/emoji count? (Default: trimmed, multibyte-aware length.)
5. **Reviews rate limit N per day:** default value? (Proposal: 5/day, configurable.)
6. **Reply moderation:** do owner replies publish immediately (with post-moderation via reports) or go to `pending`? (Proposal: publish immediately, same abuse word filter as reviews.)
7. **Invoices:** your company's legal details (name, ICE, IF, RC, address) and whether VAT (TVA 20%) applies — placeholders in config until provided.
8. **CI:** do you want a GitHub Actions workflow and a remote repository?

## Production data (kelaa.ma)

- App name **kelaa.ma**, launch city **El Kelaa des Sraghna** (slug `el-kelaa`, centre 32.04806, -7.40833).
- Database: **PostgreSQL** (local 18, production 16+). JSON columns are `jsonb`. Distances use the latitude/longitude formula on PostgreSQL; the MySQL POINT path stays for MySQL installs.
- 19 category groups / 168 categories written for Kelaa residents (agriculture and olive mills, money transfer, adouls and public writers, weddings, public services…), with Darija/Arabic/French search synonyms.
- Neighbourhood names come from public listings and must be checked with the commune; their coordinates are added from the admin panel.