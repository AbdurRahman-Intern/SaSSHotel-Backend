# Hotel SaaS API — Laravel 12 + MySQL boilerplate

Backend for the React prototype (`hotel-saas`). It is an **overlay**: this folder contains only the files
we wrote. You copy it on top of a fresh Laravel install. Every file has comments + TODOs for beginners.

> **Status (be honest with your team):** written and PHP-syntax-checked, but **never executed** — the build
> sandbox could not download Laravel (Composer/Packagist blocked). Expect small fixes on first run. Run
> `php artisan test` after setup; `tests/Feature/TenantIsolationTest.php` is the most important test.

## Requirements
PHP 8.3+, Composer 2, MySQL 8 (or MariaDB 10.6+), Node only for the React app.

## Setup (≈ 10 minutes)
```bash
# 1. Create a fresh Laravel 12 project
composer create-project laravel/laravel hotel-saas-api
cd hotel-saas-api

# 2. Copy THIS overlay on top (accept overwrite). From the folder that holds the overlay:
#    cp -R /path/to/overlay/. .
#    Overwritten on purpose: bootstrap/app.php, app/Models/User.php, app/Providers/AppServiceProvider.php

# 3. Sanctum (API tokens) + remove the default users migration clash check
php artisan install:api          # installs Sanctum, answer "yes" to run migrations if asked

# 4. Database: create it in MySQL
mysql -u root -p -e "CREATE DATABASE hotel_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Environment
cp .env.example .env             # then edit DB_USERNAME / DB_PASSWORD
php artisan key:generate

# 6. Tables + demo data
php artisan migrate --seed       # use migrate:fresh --seed to reset (DEV ONLY)

# 7. Run
php artisan serve                # http://localhost:8000   (health check: /up)
```
Demo logins (password `Demo@123456`): `admin@hotelsaas.demo`, `owner@royal.demo`, `owner@kabulgrand.demo`, `owner@palace.demo` (suspended).

Quick test:
```bash
curl -X POST http://localhost:8000/api/auth/login -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"owner@royal.demo","password":"Demo@123456"}'
```

## Folder map
| Path | What it is |
|---|---|
| `routes/api.php` | **Start here.** Every URL, grouped by who may call it |
| `bootstrap/app.php` | Middleware aliases + JSON error handling |
| `app/Http/Middleware/` | `EnsureRole` (role lock), `EnsureHotelIsActive` (suspension) |
| `app/Models/Concerns/BelongsToTenant.php` | **Security core**: hotel isolation |
| `app/Support/Tenancy.php` | `Tenancy::exists()` validation rule that respects tenants |
| `app/Models/` | One Eloquent model per table |
| `app/Http/Resources/` | JSON shapes (camelCase) the React app receives |
| `app/Http/Controllers/Api/TenantCrudController.php` | Reusable CRUD base class |
| `app/Http/Controllers/Api/*Controller.php` | One per module; `HallController` is the simplest example |
| `app/Services/` | Business logic over several tables: stock, booking, requisition, dashboard |
| `database/migrations/` | Table definitions (run in file-name order) |
| `database/seeders/DatabaseSeeder.php` | Demo data |
| `tests/Feature/TenantIsolationTest.php` | Proves hotels cannot see each other |
| `docs/` | `API.md`, `FRONTEND_INTEGRATION.md`, `LEARNING_GUIDE.md` |

## Done vs TODO
Done (boilerplate): auth, roles, tenancy, all CRUD modules, booking reference + hall clash, stock ledger,
requisition, loans/partners sub-records, hotel management, seeders.
Left for you (search `TODO` in the code): dashboard fields (`monthly`, `recentBookings`, `upcoming`,
`eventDistribution`, `pendingPayments`, `upcomingGuests`, `dailyNetProfit`), reports, plan CRUD, logo upload,
pagination, partner share ≤ 100% rule, refund policy, double-issue protection on requisitions, public hotel website API.

Find every TODO: `grep -rn "TODO" app routes database tests`
