# Pragmatic Laravel Production Workflow: Auction Bidding API

This is a production-minded implementation plan for the fictional Auction Bid Service demo. It favours a single Laravel application and PostgreSQL over premature services or infrastructure. Sources are restricted to official Laravel documentation and OWASP. The product brief defines the current scope and business rules.

## Build only this vertical slice

Implement the documented listing, bid, wallet, and demo top-up endpoints:

- `GET /api/listings` and `GET /api/listings/{listing}` — public auction state.
- `GET /api/listings/{listing}/bids` — public history with bidder codes, not dealer names.
- `POST /api/listings/{listing}/bids` — bid submission for the selected demo dealer.
- `GET /api/wallet` and a demo top-up endpoint — selected dealer's wallet and simulated deposit increase.
- `GET /api/demo/dashboard` — reviewer-friendly overview.

Use a passwordless demo identity chooser backed by the browser session. Listings can be browsed without choosing a dealer; bid submission, wallet access, and simulated top-ups require one. Every open demo session uses Livewire polling about every two seconds for bid and wallet changes; two browsers are a review example, not a limit. On dashboard visit, replenish an offer type only if none remain open; no cron or daily reset is required.

Keep HTTP controllers thin: a Form Request validates input; a policy authorizes it; a `PlaceBid` action owns the business rule and transaction; Eloquent models own persistence and relationships. Put no bidding rules in a controller, model observer, or asynchronous job. This makes the only correctness-critical path easy to test synchronously.

Suggested first-pass shape:

```
app/Http/Requests/PlaceBidRequest.php       input contract
app/Http/Controllers/AuctionBidController.php transport only
app/Actions/PlaceBid.php                     atomic domain operation
app/Policies/AuctionPolicy.php               per-auction authorization
app/Models/{Auction,Bid,IdempotencyKey}.php  persistence
tests/Feature/PlaceBidTest.php               HTTP + concurrency-rule coverage
```

Use route model binding and a policy (`$this->authorize(...)` / `Gate::authorize`) to centralize object-level authorization; Laravel converts an authorization failure to a 403 response. [Laravel authorization](https://laravel.com/framework/docs/12.x/authorization)

## API contract and failure semantics

Use a Form Request for a positive, bounded whole-AED bid amount and a required idempotency header. Validation accepts only the fields and values the operation needs; do not use client-provided dealer identity, auction state, or a “current highest bid”. Laravel Form Requests encapsulate validation and authorization, and JSON requests receive a 422 response for validation failures. [Laravel validation](https://laravel.com/framework/docs/12.x/validation)

Return a stable, small response contract:

| Situation | Response |
| --- | --- |
| accepted | `201` with bid id, amount, and resulting auction high bid |
| malformed / invalid amount | Laravel validation `422` |
| no selected demo dealer | `401` |
| selected dealer cannot bid on the listing | `403` (or policy `404` if resource concealment is intentional) |
| auction closed / amount no longer high enough | `409` with a machine-readable business error code |
| duplicate idempotency key, same request | return the stored prior accepted or business-rule-rejected result |
| duplicate key, different payload | `409` |

Do not expose SQL exceptions, stack traces, token values, or internal rule details. Laravel's exception configuration can render domain exceptions as JSON responses, while `dontReport`/log levels control operational noise. [Laravel errors](https://laravel.com/framework/docs/12.x/errors)

## The correctness path: transaction, lock, idempotency

The race to prevent is two requests both reading the same high bid and both being accepted. Do all of the following in **one short database transaction**:

1. Resolve the selected demo dealer from the session and authorize before the transaction where possible.
2. Look up/create the request’s idempotency record using a unique constraint on `(dealer_id, listing_id, key)`; store a fingerprint of the normalized amount. A unique index is the final duplicate guard. Return a stored accepted or business-rule-rejected result for a matching retry.
3. Select the listing row with `lockForUpdate()`; then re-read its end time and current high bid while holding the lock. A last-second bid does not extend the end time.
4. Lock the relevant wallet rows in a consistent order. Recheck the new dealer's deposit and active reservations, counting a replaced reservation only once when they raise their own bid. Reject a closed listing, a non-increasing bid, or a bid that would exceed the deposit.
5. Otherwise insert the bid, release the displaced dealer's active reservation, create the new reservation, update the listing's current bid, and persist the accepted idempotency response. Keep bid history intact and leave closed winners' reservations active.
6. Commit; only then dispatch non-critical work, if any.

Laravel’s `DB::transaction()` rolls back on an exception and may retry deadlocks with its `attempts` parameter. Its query builder supports pessimistic locks via `lockForUpdate()`, and the docs recommend wrapping pessimistic locking in a transaction. [Laravel database transactions and locks](https://laravel.com/framework/docs/12.x/queries#pessimistic-locking)

Keep the locked region free of remote calls, queue dispatches that must run immediately, and slow computation. Lock the **auction parent row** consistently for every bid so the high-bid invariant has one serialization point. Use a database migration with foreign keys and indexes (for example `bids(auction_id, created_at)` and `idempotency_keys(user_id, key)`); application checks alone cannot establish concurrency safety.

Idempotency keys are an application-level protocol choice, not a Laravel built-in. Store the final HTTP status/body (or enough data to recreate it) for accepted and business-rule-rejected attempts, and return it on a matching retry. A rejection record must not create a bid or reservation. This protects client retries and timeout replays; the row lock protects competing distinct bids. The simulated wallet top-up also needs idempotency and a wallet lock so a repeated request credits the deposit once.

## Authentication, authorization, and abuse controls

This demo has no production authentication. A visitor chooses one of the fictional bidder codes, and the selected dealer ID lives in that browser session. Protect write routes with session and CSRF middleware, resolve the dealer server-side, and authorize the listing through a policy. Do not accept a dealer ID from the request body. This selection is public demo impersonation, not a security boundary for real accounts. [Laravel sessions](https://laravel.com/framework/docs/12.x/session) [Laravel CSRF protection](https://laravel.com/framework/docs/12.x/csrf) [Laravel authorization](https://laravel.com/framework/docs/12.x/authorization)

Apply a named limiter to the bid endpoint, keyed by selected demo dealer and IP. Return Laravel’s standard rate-limit response/headers and choose a conservative product limit (for example, 10–30 submissions/minute), then adjust from real behaviour. Laravel’s `RateLimiter` supports rate rules and route middleware. [Laravel rate limiting](https://laravel.com/framework/docs/12.x/rate-limiting)

OWASP recommends server-side allowlist validation, strict types/ranges and early validation; client validation is usability only. [OWASP Input Validation Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html) For a browser API, require HTTPS, set an explicit CORS allowlist rather than `*` with credentials, and avoid putting sensitive credentials in URLs. [OWASP REST Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/REST_Security_Cheat_Sheet.html)

## Queue and cache choices

Do not queue `PlaceBid`: the client needs its definitive result and lock-protected ordering. Queue only post-commit side effects—winner/outbid notifications, search indexing, analytics, or webhook delivery. Jobs dispatched in a transaction can run before commit on some queue configurations; Laravel supports `after_commit` configuration or `->afterCommit()` for this case. [Laravel queues and database transactions](https://laravel.com/framework/docs/12.x/queues#jobs-and-database-transactions)

For a small project, start with the database queue (or Redis if already available), set explicit job `tries`, `backoff`, `timeout`, and `failed()` handling, and run a supervised worker in production. Jobs must be idempotent because a worker may retry them. [Laravel queues](https://laravel.com/framework/docs/12.x/queues)

Cache only read-heavy public auction details/listings, with a short TTL. Invalidate/refresh after a successful bid **after commit**. Never use cache as the source of truth for bid acceptance or high-bid comparison; the transaction-held database row is authoritative. Laravel supports `Cache::remember` for bounded read-through caching and atomic locks where a separate short-lived coordination lock is genuinely needed. [Laravel cache](https://laravel.com/framework/docs/12.x/cache)

## Tests worth the time

Prioritize feature tests over broad unit-test scaffolding:

1. valid bid creates exactly one bid and advances the auction;
2. bid at/below high bid returns 409 and changes nothing;
3. closed auction returns 409 and changes nothing;
4. no selected demo dealer returns 401; a selected dealer denied by policy returns 403;
5. invalid payload returns 422;
6. same idempotency key returns the original accepted or business-rule-rejected result without a second bid or reservation;
7. raising the selected dealer's own leading bid replaces, rather than adds to, that listing's reservation;
8. a simulated wallet top-up increases deposit and wallet activity once, including when retried;
9. two separate demo sessions see the changed high bid, leader code, and wallet state after Livewire polling;
10. job/event is dispatched only after a successful commit if one is added (fake the queue/event).

Use PostgreSQL in local and CI tests for the bidding path, together with `RefreshDatabase`, factories, separate session states, JSON assertions, and database assertions. SQLite is not an adequate substitute for demonstrating production row-lock behaviour. Laravel documents database refresh strategies plus HTTP-test helpers and JSON assertions. [Laravel database testing](https://laravel.com/framework/docs/12.x/database-testing) [Laravel HTTP tests](https://laravel.com/framework/docs/12.x/http-tests)

For a small take-home, document that the transaction/lock design handles the key race. If local integration infrastructure permits, add a two-process/concurrent-request test; otherwise do not pretend a sequential unit test proves locking behaviour.

## Logging and observability

Add structured, context-rich application logs for `bid.accepted`, `bid.rejected`, `bid.duplicate`, `auction.closed`, and unexpected transaction failure. Include request/correlation id, listing id, selected demo dealer id, idempotency-key hash, amount, result, HTTP status, and elapsed time. Log only the necessary context, never raw authorization headers, cookies, passwords, plaintext idempotency keys, database URLs, or stack traces returned to clients.

Laravel offers named log channels/stacks, contextual information, and `Log::shareContext()` for request/job context. [Laravel logging](https://laravel.com/framework/docs/12.x/logging) Add a slow-query threshold with `DB::whenQueryingForLongerThan()` and alert on sustained 5xx, database/queue failure, and unusual rejection/rate-limit volume. [Laravel database monitoring](https://laravel.com/framework/docs/12.x/database#monitoring-cumulative-query-time)

OWASP advises recording the “when, where, who, and what” of important events, using structured formats and sanitizing untrusted log values to prevent log injection. It also explicitly lists access tokens, session identifiers, passwords, connection strings, and encryption keys among data that normally should not be logged. [OWASP Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)

## Security minimums

- Validate every write server-side; use Laravel query bindings/Eloquent, never concatenate user input into raw SQL. Laravel notes parameter binding protects against SQL injection. [Laravel database queries](https://laravel.com/framework/docs/12.x/database#using-named-bindings)
- Keep secrets in environment/secret management; do not commit `.env`. Set production `APP_DEBUG=false`; force HTTPS at the edge; set a correct `APP_URL` and trusted-proxy configuration for the deployed topology.
- Use policies on every resource-specific mutation; never infer ownership from a request body. Authorization must be server-side. [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)
- Keep the demo identity chooser explicitly labelled as public and fictional; it is not a production login or password-reset flow.
- Keep dependencies patched and run the application’s tests before deploy. Do not add payment handling, websockets, distributed locks, or a microservice boundary unless the product explicitly needs them.

## Deployment and CI checklist

For this project, CI should run on every pull request and push to `main`, using a PostgreSQL service container rather than SQLite. It needs: dependency install from the lock file, frontend asset build if used, `composer validate --strict`, `vendor/bin/pint --test`, `php artisan test` against a fresh PostgreSQL database, and static analysis only if it has been configured cleanly. Make migrations additive and reviewable; take a backup and plan rollback before production schema changes.

Use CI as a reviewer aid, not as decorative configuration: the workflow must be green before merging, and its PostgreSQL connection/configuration should be visible in the repository. Laravel's test tools work with named test environments, while the product's locking claim should be verified against PostgreSQL rather than inferred from SQLite behaviour. [Laravel testing](https://laravel.com/framework/docs/12.x/testing) [Laravel database testing](https://laravel.com/framework/docs/12.x/database-testing)

At deploy time: provide production environment variables/secrets, run `php artisan migrate --force`, build/cache configuration and routes as appropriate, reload PHP workers, and restart queue workers so they use the new code. Laravel documents deployment optimization/caching and explains why queue workers are long-lived and should be restarted after deploy. [Laravel deployment](https://laravel.com/framework/docs/12.x/deployment) [Laravel queue workers](https://laravel.com/framework/docs/12.x/queues#running-the-queue-worker)

Run queue workers under a process monitor and schedule only the minimal close/cleanup tasks required. Before declaring the service ready, hit a health endpoint, submit one controlled bid, verify the log/correlation id, and confirm failed jobs and error alerts have an owner.

### Pull-request standard for this project

Use small, coherent branches/PRs rather than one opaque final commit:

1. foundation: Laravel/Livewire, PostgreSQL, seed/factory setup, formatter and CI;
2. atomic bidding: schema, `PlaceBid`, validation, locking/idempotency, and feature tests;
3. dealer UI and handoff: Livewire interaction, README, screenshots/GIF and deployment instructions.

Each PR should explain the user-facing change, the invariant or risk addressed, exact test commands/results, and non-goals. Keep code-format-only changes separate from domain changes. Add a PR template, `CONTRIBUTING.md`, and `.env.example`; all three should be concise and reflect what the project actually runs.

## Explicit non-goals for the first pass

Do not build proxy bidding, payment capture, websockets, anti-fraud scoring, read replicas, sharding, or event sourcing unless requested. Whole-AED input with a minimum AED 1 increase is sufficient for this demo. Leave clear seams (`PlaceBid`, policy) so those features can be added without changing the transactional invariant.
