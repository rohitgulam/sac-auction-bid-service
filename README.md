# Auction Bid Service

Independent portfolio demonstration using fictional auction and inspection data. The first slice provides a public Livewire auction browser, 40 demo vehicle lots across six real car models, 10 fictional number plate Listings, code-only Bid history, and a passwordless Demo dealer chooser. Vehicle photos are representative, not photos of the fictional auction lots. Bid placement and Wallet actions are planned in later slices.

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
4. Run `php artisan key:generate`, `php artisan migrate --seed`, and `npm run build`.
5. Run `php artisan serve`, then open <http://localhost:8000>. Browse Listings, inspect a vehicle or plate, and select a Demo dealer by Bidder code.

Laravel uses file sessions and cache plus a synchronous queue. The application database holds the seeded auction data.

Read-only JSON endpoints are versioned under `/api/v1`, including `/api/v1/listings` and `/api/v1/listings/{listing}/bids`.

## Vehicle photos and optional R2 publishing

Six representative vehicle photos are bundled under `public/images/vehicles` so the demo works without cloud credentials. Their photographers, source pages, and licenses are recorded in `config/auction_photos.php` and linked on each listing detail page. Images were resized to 960px for this demo; the original works remain on Wikimedia Commons. Listings and inspections are fictional.

To publish the bundled images to Cloudflare R2, set `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, and `R2_ENDPOINT` in `.env` (the endpoint is `https://<account-id>.r2.cloudflarestorage.com`). Run `php artisan app:publish-vehicle-photos-to-r2`. After the objects are publicly available, set `R2_PUBLIC_URL` to the bucket's public URL or custom domain and run `php artisan config:clear`. The app then serves vehicle image URLs from R2. Leave `R2_PUBLIC_URL` empty until upload and public access are ready; local images remain the fallback. No credentials or bucket are included in this repository.

## Current local database

On the setup machine, an isolated PostgreSQL 17 cluster is configured on `127.0.0.1:55432`. Its data and password are under `~/.local/share/sac-auction/` and are not in this repository. After a reboot, start it with:

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

`phpunit.xml` points database tests at `sac_auction_test` and PostgreSQL. It uses the local password from `.env`; each developer should set their own local credentials. Browsing and seed-data tests exist; bidding path and concurrency tests belong to the next slice.
