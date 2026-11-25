# Hotel SaaS API — Laravel 12 + MySQL 

## Requirements
PHP 8.3+, Composer 2, MySQL 8 (or MariaDB 10.6+), Node only for the React app.


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

Find every TODO: `grep -rn "TODO" app routes database tests`
