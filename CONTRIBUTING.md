# Contributing

Keep pull requests focused on one behavior or documentation change. Use a descriptive branch such as `feat/bid-feedback`, `fix/wallet-limit`, or `docs/local-setup`. Do not combine unrelated refactors with a bid or wallet change.

## Local PostgreSQL setup

Install PHP 8.4 with `pdo_pgsql` and `pcntl`, Composer 2, Node 22.20.0 (see `.nvmrc`), npm, and Docker Compose. From the repository root:

```sh
cp .env.example .env
```

Set `DB_PASSWORD` in your local `.env` to a password you choose. Leave the example host, port, database, and user values in place for the commands below. The password stays in ignored `.env`; it is never committed.

```sh
docker compose up -d --wait
docker compose exec -T postgres sh -c 'createdb -U "$POSTGRES_USER" sac_auction_test'
composer install --no-interaction --prefer-dist
npm ci
php artisan key:generate --no-interaction
php artisan migrate --seed --no-interaction
npm run build
php artisan serve --no-interaction
```

Run `createdb` once for a new Docker volume. If the test database already exists, skip that command. Open <http://localhost:8000>. The application uses `sac_auction`; `phpunit.xml` selects `sac_auction_test` for tests. The test suite migrates its own PostgreSQL database. SQLite is not part of the bidding test path.

## Checks before a PR

```sh
composer validate --strict
vendor/bin/pint --dirty --format agent
vendor/bin/pint --test
php artisan test --compact
npm run build
```

For a focused change, run the narrow feature file while working, for example `php artisan test --compact tests/Feature/BidPlacementTest.php`, then run the full suite once at the end. Mention any skipped check and why in the PR template. Add screenshots or a short GIF for UI changes. Do not claim that sequential bid tests prove simultaneous locking; `TimedOfferConcurrencyTest` covers concurrent dashboard replenishment, not concurrent bids.

`php artisan test` intentionally omits `--no-interaction`: the installed Laravel command forwards that option to PHPUnit 12, which rejects it.
