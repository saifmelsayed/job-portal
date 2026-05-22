# Job Portal API

Laravel API for a job portal: companies publish jobs, job seekers browse and apply, administrators moderate accounts and listings. Authentication uses **Laravel Sanctum** personal access tokens (**Bearer**). Designed to pair with a separate SPA (for example Vite on port 5173).

Built with [Laravel 13](https://laravel.com/docs).

## Requirements

- **PHP** `^8.3`
- **Composer**
- **Node.js** and **npm** (Vite frontend toolchain)
- **SQLite** (default in `.env.example`) or **MySQL** / **MariaDB**

## Quick start

### 1. Install PHP dependencies

```bash
composer install
```

### 2. Environment

**Windows**

```bash
copy .env.example .env
```

**macOS / Linux**

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 3. Database

**SQLite (default)**

Create the database file if it does not exist:

```bash
touch database/database.sqlite
```

On Windows, create an empty file `database\database.sqlite`.

Ensure `.env` contains:

```env
DB_CONNECTION=sqlite
```

**MySQL / MariaDB**

Set `DB_CONNECTION=mysql` and configure `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.

Then run migrations:

```bash
php artisan migrate
```

### 4. Seed super admin (optional)

Uses `ADMIN_EMAIL` and `ADMIN_PASSWORD` from `.env` (see `.env.example`).

```bash
php artisan db:seed --class=AdminUserSeeder
```

### 5. Storage link

Required for files on the `public` disk (for example profile photos):

```bash
php artisan storage:link
```

### 6. Frontend assets

```bash
npm install
npm run dev
```

Production build:

```bash
npm run build
```

### 7. Run the API

```bash
php artisan serve
```

By default the app is at **http://localhost:8000**. Routes in `routes/api.php` are served under the **`/api`** prefix (for example `POST http://localhost:8000/api/login`).

## One-command setup

Install dependencies, create `.env` if missing, generate the key, migrate, install npm packages, and build assets:

```bash
composer run setup
```

Review `.env` afterward (database, mail, CORS, admin credentials).

## Environment variables (cheat sheet)

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Base URL of this API |
| `FRONTEND_URL` | SPA URL (used for links such as password reset) |
| `CORS_ALLOWED_ORIGINS` | Comma-separated browser origins allowed to call the API |
| `CORS_SUPPORTS_CREDENTIALS` | Set `true` only if the SPA uses cookie-based Sanctum session auth |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Used when seeding the initial super admin |
| `APP_TIMEZONE` | Application timezone (default in `.env.example`: `Africa/Cairo`) |
| `MAIL_*` | Email delivery (`MAIL_MAILER=log` is typical for local dev) |

See **[`.env.example`](.env.example)** for the full list and comments.

## Authentication

- **Job seeker / company:** `POST /api/register`, `POST /api/login` — responses include a **`token`** when login succeeds.
- **Admin:** `POST /api/admin/login`.
- On protected routes, send **`Authorization: Bearer <token>`**.

## CORS and SPA

Align origins with your frontend, for example:

```env
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://127.0.0.1:5173
CORS_SUPPORTS_CREDENTIALS=false
```

Use `CORS_SUPPORTS_CREDENTIALS=true` only when intentionally using cookies with Sanctum SPA authentication.

## Development scripts

```bash
composer run dev    # server, queue, logs, and Vite (see composer.json)
composer run test   # PHPUnit
```

## Production checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, and a strong `APP_KEY`.
- Configure production database and `MAIL_*`.
- Run `php artisan migrate --force`.
- Run `npm run build` when serving Vite-built assets from this application.
- Run `php artisan storage:link` if you use the `public` disk for uploads.
- Restrict `CORS_ALLOWED_ORIGINS` (avoid wildcards in production).

## License

Open source under the [MIT License](https://opensource.org/licenses/MIT).
