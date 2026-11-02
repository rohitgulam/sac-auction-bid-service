# Auction Bid Service

Independent portfolio demonstration using fictional data. This repository currently contains **setup only**: Laravel 13, Livewire 3, Tailwind CSS 4, Vite, Laravel Boost, and PostgreSQL configuration. Bidding screens, API endpoints, demo data, and application migrations have not been built yet.

The project direction is recorded in [the product brief](auction-bid-service-product-brief.md), [build prompt](auction-bid-service-codex-cli-prompt.md), and [Laravel workflow](laravel-production-workflow.md).

## Requirements

- PHP 8.3+ with `pdo_pgsql`
- Composer 2
- Node.js 22.20+ and npm
- PostgreSQL 17 (or a supported PostgreSQL version)

## Local setup

1. Create two empty PostgreSQL databases, `sac_auction` and `sac_auction_test`, owned by a local role that can connect to both.
2. Run `composer install` and `npm ci`.
3. Copy `.env.example` to `.env`. Set `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` for your local PostgreSQL role. The example uses port `55432`; change it if your server uses `5432`.
4. Run `php artisan key:generate` and `npm run build`.
5. Run `php artisan serve`, then open <http://localhost:8000>. The page is Laravel's starter page until application work begins.

No migrations should be run at this stage. Laravel uses file sessions and cache plus a synchronous queue so the app can run against an empty database.

## Current local database

On the setup machine, an isolated PostgreSQL 17 cluster is running on `127.0.0.1:55432`. Its data and password are under `~/.local/share/sac-auction/` and are not in this repository. The two databases are empty. After a reboot, start it with:

```sh
/Library/PostgreSQL/17/bin/pg_ctl -D "$HOME/.local/share/sac-auction/postgres" -l "$HOME/.local/share/sac-auction/postgres.log" -o '-h 127.0.0.1 -p 55432' start
```

## Checks

```sh
php artisan db:show --counts
php artisan test
vendor/bin/pint --test
npm run build
```

`phpunit.xml` points database tests at `sac_auction_test` and PostgreSQL. It uses the local password from `.env`; each developer should set their own local credentials. No bidding path or concurrency tests exist yet.
