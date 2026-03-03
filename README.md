# News Orbit Backend (Laravel)

Backend implementation for a news aggregation challenge.

## What This Project Does

- Aggregates and stores articles from 3 providers:
  - The Guardian
  - New York Times
  - NewsAPI.org
- Exposes backend APIs for:
  - article listing and filtering
  - client preferences
  - NYT Top Stories-compatible endpoint
- Runs scheduled sync hourly.

## Tech Stack

- PHP 8.2+
- Laravel 12
- MariaDB / MySQL

## 1) Prerequisites

Make sure these are installed and available in PATH:

- `php -v` (8.2+)
- `composer -V`
- MySQL or MariaDB server running

Optional:

- Node/NPM (not required for backend API testing)

## 2) Setup

From project root:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Create a database, for example `news_orbit`, then set DB credentials in `.env`:

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=news_orbit
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations:

```bash
php artisan migrate
```

## 3) Required Environment Variables

Set provider keys in `.env`:

```env
GUARDIAN_API_KEY=
NYT_API_KEY=
NEWSAPI_API_KEY=
```

Useful tuning values:

```env
NEWS_DEFAULT_SYNC_WINDOW_HOURS=24
NEWS_MAX_PAGES_PER_SOURCE=2
NEWS_UPSERT_CHUNK_SIZE=50
NEWS_FILTERS_CACHE_TTL_SECONDS=300
```

## 4) Run the Project

Start API server:

```bash
php artisan serve
```

Initial sync:

```bash
php artisan news:sync
```

Sync one provider only:

```bash
php artisan news:sync --source=newsapi
```

Sync from specific datetime:

```bash
php artisan news:sync --from="2026-03-01 00:00:00"
```

## 5) Auto Updates (Scheduler)

- `news:sync` is scheduled hourly in `bootstrap/app.php`.
- For local execution of scheduled jobs, run:

```bash
php artisan schedule:work
```

## 6) Data Behavior

- Articles are stored in `articles` table.
- Sync uses bulk `upsert` with unique key:
  - `source + external_id`
- Existing records are updated when provider data changes.
- New records are inserted.
- Old records are retained unless explicitly deleted.

## 7) Run Tests

```bash
php artisan test
```

## 8) API Endpoints

### Aggregated Articles

- `GET /api/articles`
- `GET /api/articles/filters`

Common query params for `/api/articles`:

- `q`
- `sources[]`
- `categories[]`
- `authors[]`
- `from`
- `to`
- `client_id`
- `per_page`

### Preferences

- `GET /api/preferences/{clientId}`
- `PUT /api/preferences/{clientId}`

### NYT-Compatible Top Stories

- `GET /api/top-stories/{section}.json`

Optional query params:

- `limit`
- `offset`

## 9) API Docs and Postman

- OpenAPI: `docs/openapi.yaml`
- Postman collection: `docs/postman_collection.json`

## 10) Quick Postman Test Flow

1. `PUT /api/preferences/client-123`
2. `GET /api/preferences/client-123`
3. `GET /api/articles`
4. `GET /api/articles?client_id=client-123`
5. `GET /api/articles/filters`
6. `GET /api/top-stories/home.json`
7. `GET /api/top-stories/technology.json?limit=10&offset=0`

## 11) Troubleshooting

### Large packet error during sync

If you see MySQL/MariaDB error:

- `Got a packet bigger than 'max_allowed_packet' bytes`

Lower chunk size:

```env
NEWS_UPSERT_CHUNK_SIZE=25
```

Then clear config cache:

```bash
php artisan optimize:clear
```

Re-run sync:

```bash
php artisan news:sync
```