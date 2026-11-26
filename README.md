# Hotel SaaS API — Laravel 13 + MySQL

Backend for the Hotel & Wedding Hall Management SaaS. The React app lives in the `hotel-MIS-Frontend` repository.

## Requirements
PHP **8.4+**, Composer 2, MySQL 8 (or MariaDB 10.6+). Node is only needed for the React app.

## First-time setup (after cloning)
```bash
# 1. Install PHP packages
composer install

# 2. Create your environment file, then edit DB_USERNAME / DB_PASSWORD
cp .env.example .env
php artisan key:generate

# 3. Create an empty database (once)
mysql -u root -p -e "CREATE DATABASE hotel_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Tables + demo data  (migrate:fresh --seed resets everything, DEV ONLY)
php artisan migrate --seed

# 5. Run
php artisan serve        # http://localhost:8000   (health check: /up)
```
Windows PowerShell: use `Copy-Item .env.example .env` instead of `cp`.

Demo logins (password `Demo@123456`): `admin@hotelsaas.demo`, `owner@royal.demo`, `owner@kabulgrand.demo`, `owner@palace.demo` (suspended).

Quick test:
```bash
curl -X POST http://localhost:8000/api/auth/login -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"owner@royal.demo","password":"Demo@123456"}'
```

## Run the tests
```bash
php artisan test
```

## Connecting the React app
Set `FRONTEND_URL` in `.env` to the exact address of the React app (default `http://localhost:5173`), and in the frontend `.env` set `VITE_API_BASE_URL=http://127.0.0.1:8000/api`. The full endpoint list is in [`docs/API.md`](docs/API.md).

## Team workflow
Branch from `develop`, open a Pull Request into `develop`, CI must be green, then `develop` → `main`. Details are in `.github/workflows/back-ci.yml`.

Find every TODO: `grep -rn "TODO" app routes database tests`
