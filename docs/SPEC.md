# Project: City Services Directory for Morocco (Laravel + Livewire)

You are a senior Laravel engineer. You will build a production-quality web platform: a local directory for one Moroccan city where residents and visitors can find any service they need (artisans, mechanics, electricians, delivery, restaurants, pharmacies, etc.), see ratings, and leave reviews with stars. The platform earns money through clearly labeled sponsored placements and paid business plans.

## 0. How to work

1. Read this entire spec first. Save it as `docs/SPEC.md` in the repo.
2. Before writing any code, write `docs/PLAN.md` with the phases in section 18 broken into concrete tasks, then STOP and wait for my approval.
3. Work one phase at a time. At the end of each phase: run the tests, run Pint and Larastan, summarize what was done, and wait for my go before the next phase.
4. When something in the spec is ambiguous, ask me instead of guessing.
5. Commit after each coherent feature with a clear message.
6. Never hardcode secrets. Everything goes in `.env` with documented keys in `.env.example`.
7. Write Pest tests for every model rule, the ranking logic, review rules, permissions, and payments.
8. Keep a `README.md` with setup instructions (Laravel Sail / Docker).

## 1. Product principles (non negotiable)

- Trust is the product. Organic ranking and reviews can NEVER be bought. Paid visibility only happens through slots clearly labeled "Sponsorisé" / "مُموَّل" / "Sponsored".
- A business can never delete, hide, or edit a review, whatever plan it pays for. It can only reply or report.
- Mobile first. Most users will be on phones, sometimes on slow 3G/4G connections.
- WhatsApp is the main contact channel in Morocco. Every business page must have a prominent WhatsApp button (wa.me link with a prefilled message), plus Call and Directions.
- The platform is configured for one city at launch but the data model must support multiple cities from day one.

## 2. Tech stack

- PHP 8.3+ and the latest stable Laravel release (check with composer, do not assume a version).
- Livewire (latest stable, 3 or newer) with Alpine.js. Use `wire:navigate` for SPA-like navigation and lazy loading for heavy components.
- Tailwind CSS (version shipped with the current Laravel starter kit). Use logical properties (`ms-`, `me-`, `ps-`, `pe-`, `start-`, `end-`) everywhere so RTL works automatically.
- Filament (latest stable) for the admin and moderation panel at `/admin`.
- MySQL 8 with a spatial `POINT` column (SRID 4326) on businesses and `ST_Distance_Sphere` for distance queries. Wrap geo queries in a dedicated query scope so we could switch to PostgreSQL + PostGIS later.
- Laravel Scout + Meilisearch for full text search (typo tolerance, synonyms, Arabic support).
- Redis for cache, sessions, and queues. Laravel Horizon for queue monitoring.
- Spatie packages: `laravel-permission`, `laravel-medialibrary` (with WebP conversions and responsive images), `laravel-translatable`, `laravel-sitemap`, `laravel-activitylog`.
- Maps: Leaflet.js with OpenStreetMap data. Make the tile provider URL configurable in `.env`, because the public OSM tile servers do not allow heavy production usage (plan for MapTiler, Stadia, or similar).
- Testing and quality: Pest, Laravel Pint, Larastan (level 6 minimum).
- Dev environment: Laravel Sail.

## 3. Localization

- Three locales: `fr` (default), `ar` (RTL), `en` (tourists).
- Locale prefix in URLs: `/fr/...`, `/ar/...`, `/en/...`, with `hreflang` tags between versions.
- All user-facing strings in translation files. Category names, city names, and neighborhood names are translatable fields (spatie/laravel-translatable).
- Layout switches `dir="rtl"` and loads an Arabic-friendly font (e.g. Noto Sans Arabic / IBM Plex Sans Arabic) for `ar`.
- Search synonyms: a `search_synonyms` table managed in the admin, synced to Meilisearch, so that French, Arabic, and Darija written in Latin letters all match (examples: "plombier", "سباك", "plombi"; "mécanicien", "ميكانيكي", "mikanisyan"; "électricien", "تريسيان", "trisyan").
- Dates, numbers, and prices formatted per locale. Currency is MAD (DH).

## 4. Users, roles, authentication

Roles (spatie/laravel-permission): `member`, `business_owner`, `moderator`, `admin`.

- Visitors can browse and search without an account.
- Leaving a review, favoriting, or adding a business requires an account.
- Authentication: Moroccan phone number (+212, validate 06/07 mobile formats) + OTP code. Build an `OtpSender` interface with drivers: `log` (dev), `sms` (provider to be configured later), `whatsapp` (provider to be configured later). Rate limit OTP requests strictly (per phone and per IP).
- Optional email + password login and optional Google login (Socialite), both configurable.
- A user must have a verified phone number to publish a review.
- Account settings: edit profile, change language, download my data, delete my account (required by Moroccan Law 09-08).

## 5. Data model

Design migrations, models, factories, and relationships for at least:

- `cities`: name (translatable), slug, center lat/lng, default zoom, is_active.
- `neighborhoods` (quartiers): city_id, name (translatable), slug, optional polygon or center point.
- `categories`: parent_id (2 levels max), name (translatable), slug, icon, sort_order, is_emergency, is_active.
- `businesses`: city_id, neighborhood_id, owner_id (nullable until claimed), name, slug, description (translatable), address, location POINT, phone, whatsapp, email, website, social links, price_range (1 to 4), status (`pending`, `published`, `suspended`), is_verified, is_claimed, plan_id, plan_expires_at, profile_completeness (computed), rating_avg, rating_count, bayesian_score, last_activity_at, timestamps, soft deletes.
- `business_category` pivot (a business can have several categories, one primary).
- `business_hours`: business_id, day_of_week, opens_at, closes_at, is_closed. Support split hours (e.g. 9:00 to 13:00 and 15:00 to 20:00) and "24/7".
- `business_special_hours`: exceptions (Ramadan hours, holidays, Eid).
- Media via medialibrary: logo, cover, gallery (limit depends on plan).
- `reviews`: business_id, user_id, rating (1 to 5, required), body, status (`pending`, `published`, `rejected`, `hidden_by_moderation`), helpful_count, published_at, edited_at, ip_hash, device_hash. Unique constraint on (business_id, user_id).
- `review_replies`: review_id, user_id (owner), body, status.
- `review_votes`: helpful votes, unique per user and review.
- `reports`: polymorphic (review, reply, business, photo), reporter_id, reason enum, details, status, handled_by.
- `business_claims`: business_id, user_id, method (`phone_otp_to_business_number`, `document_upload`, `manual`), documents, status, reviewed_by.
- `favorites`: user_id, business_id.
- `plans`: name (translatable), slug, price_monthly, price_yearly (MAD), features JSON (max_photos, show_stats, highlighted_card, remove_competitor_ads, priority_support), is_active. Prices are editable in the admin, never hardcoded.
- `subscriptions`: business_id, plan_id, starts_at, ends_at, status, payment_id.
- `sponsored_placements`: business_id, city_id, category_id, neighborhood_id (nullable), position (1 to 3), starts_at, ends_at, status, payment_id, impressions, clicks.
- `payments`: business_id, user_id, amount, currency, method (`bank_transfer`, `cash_agency`, `cmi`, `manual`), reference, receipt (media), status (`pending`, `validated`, `rejected`, `refunded`), validated_by, invoice_id.
- `invoices`: sequential number per year, business legal info (company name, ICE, address), lines, totals, PDF.
- `contact_events`: business_id, type (`profile_view`, `call_click`, `whatsapp_click`, `directions_click`, `website_click`), source (`organic`, `sponsored`, `search`, `map`), session_hash, created_at. Used for owner statistics and a possible pay-per-lead model later. Aggregate daily into `business_stats_daily` via a scheduled job.
- `pharmacies_de_garde`: business_id, date, starts_at, ends_at (managed by admin, bulk import from CSV).
- `pages`: editable static pages (CGU, privacy policy, review guidelines, about, contact).
- `search_synonyms`: term, synonyms, locale.

## 6. Public features (Livewire components)

### Home
- Big search bar ("What do you need?") with autocomplete on categories and businesses.
- "Use my location" button (browser geolocation, optional, never required).
- Category grid with icons, emergency categories highlighted.
- "Pharmacies de garde tonight" block.
- "Open now near you" block when location is known.
- Recently reviewed businesses.

### Category / search results page
- Filters: category, neighborhood, open now, minimum rating, price range, verified only, distance radius (when location known).
- Sorting: relevance (default), best rated (Bayesian), nearest, most reviewed, newest.
- List view and map view toggle (Leaflet, markers clustered).
- Up to 3 sponsored slots at the top, visually distinct and labeled "Sponsorisé". Sponsored businesses must NOT be duplicated in the organic list right below.
- Paginated or infinite scroll with proper SEO pagination links.
- Filters reflected in the URL (Livewire `#[Url]` attributes) so results are shareable.

### Business page
- Name, categories, verified badge, "Open now / Closed / Opens at 15:00" badge computed in the city's timezone (Africa/Casablanca).
- Action buttons: WhatsApp (prefilled message mentioning the platform), Call, Directions (opens Google Maps / Waze with coordinates), Share.
- Photos gallery, description, hours table, special hours, address with mini map, price range.
- Rating summary: average, count, distribution bars for 1 to 5 stars.
- Reviews list with sorting (newest, most helpful, highest, lowest), owner replies shown under each review.
- "Report" link on the business, each review, and each photo.
- "Is this your business? Claim it" call to action when not claimed.
- "Suggest an edit" for users (goes to moderation).
- Similar businesses nearby (organic only).
- Every visit and button click logs a `contact_event` (queued, lightweight).

### Reviews
- Only for logged in users with verified phone.
- One review per user per business, editable (keep edit history).
- Rating required, text optional but a minimum of 20 characters if provided, optional photos (max 5).
- A business owner cannot review their own business or businesses in the same primary category within the same city (conflict of interest).
- Reviews from accounts less than 24h old, or triggering the abuse rules in section 11, go to `pending` for moderation. Others publish immediately.
- Businesses get notified of new reviews and can reply once per review (reply editable).
- Recalculate `rating_avg`, `rating_count`, and `bayesian_score` via a queued job on every review publish, edit, or removal.

### Add a business
- Any logged in user can submit a business (free). Status `pending` until a moderator approves it.
- Duplicate detection before submission: same phone number, or similar name within 200 m.

### Claim flow
- Owner claims a business, verification by OTP sent to the business phone number, or by document upload (registre de commerce, patente, or CIN of an artisan) reviewed by a moderator.
- Once approved, role `business_owner` is granted and the owner dashboard is unlocked.

## 7. Ranking algorithm (organic)

Implement in a dedicated, fully tested service class `RankingService`.

- Bayesian average: `score = (v / (v + m)) * R + (m / (v + m)) * C` where `R` = business average, `v` = number of reviews, `C` = average rating across the category in the city, `m` = configurable constant (default 5). This stops a business with one 5 star review from beating one with 80 reviews at 4.7.
- Final relevance score combines: Bayesian score, text relevance (from Meilisearch), distance (when location known), profile completeness, recency of activity, verified status. Weights configurable in `config/ranking.php`.
- Paid plan status has NO effect on organic score. Write a test that proves it.

## 8. Monetization

### Free plan
Listing, contact buttons, up to 3 photos, reply to reviews.

### Paid plans (Pro, Premium: names, features, and prices editable in admin)
- More photos, logo and cover, highlighted card design in results (visually richer but same organic position), statistics dashboard, no competitor sponsored suggestions on their own page, priority moderation of their claims and edits.

### Sponsored placements
- Sold per city + category (+ optional neighborhood), per week or month, positions 1 to 3.
- Limited inventory: the admin sees an availability calendar per category and position.
- If a slot has more demand than supply later, prepare the code for rotation between sponsors (not needed in v1, but do not block it).
- Track impressions and clicks per placement and show them to the owner.

### Payments
- `PaymentGateway` interface with drivers:
  - `manual` (v1): the owner chooses bank transfer or cash payment at an agency (CashPlus, Wafacash), sees our payment details, uploads a receipt photo. An admin validates in Filament, which activates the subscription or placement automatically.
  - `cmi` (stub for later): Moroccan interbank card gateway. Create the driver skeleton, config keys, and callback routes, but do not implement the full integration yet.
- Generate a PDF invoice after validation, with sequential numbering and a field for the client's ICE.
- Scheduled jobs: expiry of plans and placements, reminders 7 days and 1 day before expiry (email + in-app notification, WhatsApp later).
- Downgrade gracefully on expiry: extra photos are hidden, not deleted.

## 9. Owner dashboard (Livewire, `/dashboard`)

- Edit business info, hours, special hours, photos (within plan limits).
- Reviews inbox with reply form and report button.
- Statistics (paid plans): views, WhatsApp clicks, calls, directions, per day/week/month, organic vs sponsored, charts.
- Current plan, upgrade page, payment history, invoices download.
- Buy a sponsored placement: choose category, neighborhood, duration, see availability and price, then pay.
- Manage multiple businesses from one account.

## 10. Admin panel (Filament, `/admin`)

- Dashboard: new businesses, pending reviews, pending claims, pending payments, open reports, revenue this month.
- Moderation queues: businesses, reviews, replies, photos, edit suggestions, reports. One click approve / reject with reason (reason sent to the user).
- Claims verification with document viewer.
- CRUD: cities, neighborhoods, categories (drag and drop ordering), plans, pages, search synonyms.
- Sponsored placements calendar and management.
- Payments validation with receipt preview.
- Pharmacies de garde: calendar + CSV import.
- Users management, ban/suspend, activity log.
- Moderators can moderate but cannot touch payments or plans.

## 11. Anti abuse and trust

- Rate limiting on OTP, login, reviews (max N per day per user), reports, and business submissions.
- Store hashed IP and device fingerprint on reviews. Flag for moderation when: several reviews for the same business from the same IP/device, a burst of reviews on one business in a short time, the reviewer's phone matches the business phone, or the text matches a configurable list of banned words.
- Review guidelines page linked from the review form.
- Every moderation action is logged (who, when, why).
- Hard rule enforced in code and tested: no role other than moderator/admin can change a review's status, and payment status never influences review visibility.

## 12. Legal and compliance features

- Editable pages: CGU (terms), privacy policy, review guidelines, cookie policy, legal notice.
- Footer displays the CNDP declaration/authorization number (config value) as required under Law 09-08.
- Cookie consent banner, non-essential cookies off by default.
- User data export (JSON) and account deletion flow. On deletion, reviews are anonymized ("Utilisateur supprimé") rather than removed, unless the user asks for removal.
- Businesses can request correction of their information.
- All sponsored content labeled in all three languages.

## 13. SEO and performance

- Clean URLs:
  - `/{locale}/{city}` (city home)
  - `/{locale}/{city}/{category}` and `/{locale}/{city}/{category}/{neighborhood}`
  - `/{locale}/{city}/b/{business-slug}`
- Unique title and meta description per page, Open Graph tags, canonical URLs, hreflang.
- schema.org JSON-LD: `LocalBusiness` (or the most specific subtype) with address, geo, openingHours, and `AggregateRating` built from user reviews; `BreadcrumbList` on all pages.
- XML sitemap generated daily (spatie/laravel-sitemap), split by type.
- Images: WebP conversions, responsive sizes, lazy loading, explicit width and height.
- Cache category pages and counts in Redis, invalidate on relevant changes.
- Eager load relationships, no N+1 (enable `Model::preventLazyLoading()` in non-production).
- Target: good Core Web Vitals on a mid-range Android phone over 4G.

## 14. PWA

- Web app manifest (name, icons, theme color), installable on Android.
- Basic service worker: cache static assets and an offline fallback page. No aggressive caching of dynamic pages.

## 15. Notifications

- Laravel notifications with database + mail channels in v1.
- Create a `WhatsAppChannel` interface stub to plug a WhatsApp Business API provider later.
- Events: new review on my business, reply to my review, claim approved/rejected, payment validated, plan expiring, content rejected with reason.

## 16. Seed data

- City and its neighborhoods configurable via a seeder reading `database/seeders/data/city.json` (I will fill the real city and quartiers).
- Category seeder with names in French, Arabic, and English. Initial list:
  - Urgences: pharmacie de garde, médecin, clinique, dépannage auto, serrurier
  - Maison: plombier, électricien, peintre, menuisier, climatisation, femme de ménage, déménagement, jardinier, vitrier, réparation électroménager
  - Auto et moto: mécanicien, électricien auto, vulcanisation / pneus, lavage auto, carrosserie, pièces détachées
  - Transport: stations de taxis, location de voitures, transport de marchandises, transport scolaire
  - Livraison: livreurs indépendants, coursiers
  - Food: restaurants, snacks, cafés, boulangeries, pâtisseries, traiteurs
  - Beauté et bien-être: coiffeur hommes, coiffeuse, hammam, esthéticienne
  - Santé: dentiste, opticien, kinésithérapeute, laboratoire, vétérinaire
  - Services: couturier, cordonnier, réparation téléphone, informatique, imprimerie / photocopie, cours particuliers, serrurerie, pressing
- Taxi category contains only taxi stations and licensed transport companies, not individual drivers offering private rides.
- Factories generating realistic fake businesses, hours, and reviews for local development only (never run in production).

## 17. Deployment readiness

- Production checklist in `docs/DEPLOY.md`: queue workers, Horizon, scheduler cron, Meilisearch, Redis, backups (database + media, daily, offsite), log rotation, HTTPS, error tracking (configurable, e.g. Sentry).
- Health check route.
- Config for hosting on a VPS (Nginx + PHP-FPM) or Laravel Forge.

## 18. Phases

1. **Foundation**: project setup with Sail, auth with phone OTP (log driver), roles, localization and RTL layout, all migrations, models, factories, seeders, Filament installed with basic resources.
2. **Directory**: home, categories, search with Meilisearch and synonyms, filters, list/map views, business page with open/closed logic, contact buttons, contact event logging, pharmacies de garde.
3. **Reviews and moderation**: review creation and editing, replies, votes, reports, abuse rules, rating recalculation, Bayesian ranking service, moderation queues in Filament.
4. **Owners**: add a business, duplicate detection, claim flow, owner dashboard, statistics.
5. **Monetization**: plans, manual payments, admin validation, subscriptions, sponsored placements with inventory calendar, invoices, expiry jobs and reminders, CMI driver stub.
6. **Polish and launch**: SEO (JSON-LD, sitemaps, meta), PWA, performance pass, legal pages, cookie consent, data export/deletion, deployment docs.

Start now with step 0: save the spec, write `docs/PLAN.md`, and wait for my approval.
