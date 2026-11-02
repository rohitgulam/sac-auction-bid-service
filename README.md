# Auction Bid Service

Independent portfolio demonstration using fictional auction and inspection data. The app provides a public Livewire auction browser, 40 demo vehicle lots across six real car models, 10 fictional number plate Listings, code-only Bid history, a passwordless Demo dealer chooser, and deposit-backed bidding. Vehicle photos are representative, not photos of the fictional auction lots. Demo deposit top-ups, polling, and animations are planned in later slices.

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
5. Run `php artisan serve`, then open <http://localhost:8000>. Choose a Demo dealer, open a live Listing, and place a whole-AED bid above the current high bid. Open a second browser session with another code to outbid the first.

Laravel uses file sessions and cache plus a synchronous queue. The application database holds the seeded auction data.

JSON endpoints are versioned under `/api/v1`. Public reads include `/api/v1/listings` and `/api/v1/listings/{listing}/bids`. A selected Demo dealer session is required for `GET /api/v1/wallet` and `POST /api/v1/listings/{listing}/bids`. The POST body is `{"amount_aed": 21000}` and requires an `Idempotency-Key` header. Accepted bids return HTTP 201 with the Bid and Wallet summary; business-rule rejections return HTTP 409 with a stable error code. Invalid input returns 422; missing selection returns 401.

## Bid transaction

`app/Actions/PlaceBid.php` owns the state change. `tests/Feature/BidPlacementTest.php` exercises it through HTTP and Livewire.

```text
POST bid → validate amount/key and selected dealer
         → transaction: lock listing
         → return stored result if key was used
         → recheck closing time and high bid
         → lock relevant wallets in dealer-ID order
         → check deposit − other active reservations
         → create bid, release old leader's reservation, reserve new leader's bid
         → store accepted or rejected outcome by unique dealer/listing/key
         → commit
```

Money is stored as integer AED fils. The available limit is deposit minus active reservations; raising your own high bid substitutes its old reservation when checking capacity. A closed winning bid stays reserved because settlement is outside the demo. PostgreSQL row locks and a partial unique index on active reservations protect the write path. The sequential feature tests demonstrate the rules and the absence of Bid or reservation side effects on rejection, but do not prove real concurrent lock behavior.

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

`phpunit.xml` points database tests at `sac_auction_test` and PostgreSQL. It uses the local password from `.env`; each developer should set their own local credentials.
