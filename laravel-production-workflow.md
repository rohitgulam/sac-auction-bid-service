# Pragmatic Laravel Production Workflow: Auction Bidding API

This is a deliberately small, production-minded plan for a 1–2 hour Laravel build. It favours a single Laravel application and PostgreSQL over premature services or infrastructure. Sources are restricted to official Laravel documentation and OWASP.

## Build only this vertical slice

Implement four resources/endpoints:

- `GET /api/auctions/{auction}` — public auction state.
- `POST /api/auctions/{auction}/bids` — authenticated bid submission.
- `GET /api/auctions/{auction}/bids` — authenticated, policy-protected bid history as appropriate.
- A small scheduled/queued close-auction action only if the product brief requires it.

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

Use a Form Request for `amount` (required numeric/decimal, positive, bounded) and an optional idempotency header. Validation accepts only the fields and values the operation needs; do not use client-provided `user_id`, auction state, or a “current highest bid”. Laravel Form Requests encapsulate validation and authorization, and JSON requests receive a 422 response for validation failures. [Laravel validation](https://laravel.com/framework/docs/12.x/validation)

Return a stable, small response contract:

| Situation | Response |
| --- | --- |
| accepted | `201` with bid id, amount, and resulting auction high bid |
| malformed / invalid amount | Laravel validation `422` |
| unauthenticated | `401` |
| authenticated but cannot bid | `403` (or policy `404` if resource concealment is intentional) |
| auction closed / amount no longer high enough | `409` with a machine-readable business error code |
| duplicate idempotency key, same request | return the stored prior result (`201` or equivalent) |
| duplicate key, different payload | `409` |

Do not expose SQL exceptions, stack traces, token values, or internal rule details. Laravel's exception configuration can render domain exceptions as JSON responses, while `dontReport`/log levels control operational noise. [Laravel errors](https://laravel.com/framework/docs/12.x/errors)

## The correctness path: transaction, lock, idempotency

The race to prevent is two requests both reading the same high bid and both being accepted. Do all of the following in **one short database transaction**:

1. Authenticate and authorize before the transaction where possible.
2. Look up/create the request’s idempotency record using a unique constraint on `(user_id, key)`; store a request fingerprint (auction id + normalized amount). A unique index is the final duplicate guard.
3. Select the auction row with `lockForUpdate()`; then re-read its end time and current high amount while holding the lock.
4. Reject closed auctions or non-increasing bids. Otherwise insert the bid, update the auction’s denormalized `current_bid_amount`/`current_bid_id`, and persist the successful idempotency response.
5. Commit; only then dispatch non-critical work (notifications, analytics).

Laravel’s `DB::transaction()` rolls back on an exception and may retry deadlocks with its `attempts` parameter. Its query builder supports pessimistic locks via `lockForUpdate()`, and the docs recommend wrapping pessimistic locking in a transaction. [Laravel database transactions and locks](https://laravel.com/framework/docs/12.x/queries#pessimistic-locking)

Keep the locked region free of remote calls, queue dispatches that must run immediately, and slow computation. Lock the **auction parent row** consistently for every bid so the high-bid invariant has one serialization point. Use a database migration with foreign keys and indexes (for example `bids(auction_id, created_at)` and `idempotency_keys(user_id, key)`); application checks alone cannot establish concurrency safety.

Idempotency keys are an application-level protocol choice, not a Laravel built-in. Store the final HTTP status/body (or enough data to recreate it) transactionally, return it on a matching retry, and expire records later. This protects client retries and timeout replays; the row lock protects competing distinct bids.

## Authentication, authorization, and abuse controls

For a first-party SPA, use Sanctum’s cookie/session mode—not a long-lived API token in browser storage. It supplies CSRF protection and avoids credential leakage through XSS; configure only the known stateful domains and credentialed CORS. For third-party/mobile clients, use scoped Sanctum tokens, short expirations where suitable, and revocation. [Laravel Sanctum](https://laravel.com/framework/docs/12.x/sanctum)

Protect bid routes with `auth:sanctum`, then authorize the specific auction in an `AuctionPolicy` (for example, bidder is allowed and auction is live). A token ability is supplementary to, not a replacement for, object-level policy checks. [Laravel Sanctum token abilities](https://laravel.com/framework/docs/12.x/sanctum#token-abilities)

Apply a named limiter to the bid endpoint, keyed primarily by authenticated user and secondarily by IP for unauthenticated/login routes. Return Laravel’s standard rate-limit response/headers and choose a conservative product limit (for example, 10–30 submissions/minute), then adjust from real behaviour. Laravel’s `RateLimiter` supports rate rules and route middleware. [Laravel rate limiting](https://laravel.com/framework/docs/12.x/rate-limiting)

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
4. unauthenticated returns 401; unauthorized user returns 403;
5. invalid payload returns 422;
6. same idempotency key returns the original result without a second bid;
7. job/event is dispatched only after a successful commit (fake the queue/event).

Use PostgreSQL in local and CI tests for the bidding path, together with `RefreshDatabase`, factories, `actingAs`, JSON assertions, and database assertions. SQLite is not an adequate substitute for demonstrating production row-lock behaviour. Laravel documents database refresh strategies plus HTTP-test helpers and JSON assertions. [Laravel database testing](https://laravel.com/framework/docs/12.x/database-testing) [Laravel HTTP tests](https://laravel.com/framework/docs/12.x/http-tests)

For a small take-home, document that the transaction/lock design handles the key race. If local integration infrastructure permits, add a two-process/concurrent-request test; otherwise do not pretend a sequential unit test proves locking behaviour.

## Logging and observability

Add structured, context-rich application logs for `bid.accepted`, `bid.rejected`, `bid.duplicate`, `auction.closed`, and unexpected transaction failure. Include request/correlation id, auction id, authenticated user id, idempotency-key hash, amount, result, HTTP status, and elapsed time. Log only the necessary context, never raw authorization headers, cookies, passwords, plaintext idempotency keys, database URLs, or stack traces returned to clients.

Laravel offers named log channels/stacks, contextual information, and `Log::shareContext()` for request/job context. [Laravel logging](https://laravel.com/framework/docs/12.x/logging) Add a slow-query threshold with `DB::whenQueryingForLongerThan()` and alert on sustained 5xx, database/queue failure, and unusual rejection/rate-limit volume. [Laravel database monitoring](https://laravel.com/framework/docs/12.x/database#monitoring-cumulative-query-time)

OWASP advises recording the “when, where, who, and what” of important events, using structured formats and sanitizing untrusted log values to prevent log injection. It also explicitly lists access tokens, session identifiers, passwords, connection strings, and encryption keys among data that normally should not be logged. [OWASP Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)

## Security minimums

- Validate every write server-side; use Laravel query bindings/Eloquent, never concatenate user input into raw SQL. Laravel notes parameter binding protects against SQL injection. [Laravel database queries](https://laravel.com/framework/docs/12.x/database#using-named-bindings)
- Keep secrets in environment/secret management; do not commit `.env`. Set production `APP_DEBUG=false`; force HTTPS at the edge; set a correct `APP_URL` and trusted-proxy configuration for the deployed topology.
- Use policies on every resource-specific mutation; never infer ownership from a request body. Authorization must be server-side. [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)
- Use generic, rate-limited login failures and safe password-reset flows if authentication is in scope. [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- Keep dependencies patched and run the application’s tests before deploy. Do not add payment handling, websockets, distributed locks, or a microservice boundary unless the product explicitly needs them.

## Deployment and CI checklist

For this project, CI needs only: dependency install from lock file, formatting/static analysis if configured, `php artisan test`, and migration validation against an empty disposable database. Make migrations additive and reviewable; take a backup and plan rollback before production schema changes.

At deploy time: provide production environment variables/secrets, run `php artisan migrate --force`, build/cache configuration and routes as appropriate, reload PHP workers, and restart queue workers so they use the new code. Laravel documents deployment optimization/caching and explains why queue workers are long-lived and should be restarted after deploy. [Laravel deployment](https://laravel.com/framework/docs/12.x/deployment) [Laravel queue workers](https://laravel.com/framework/docs/12.x/queues#running-the-queue-worker)

Run queue workers under a process monitor and schedule only the minimal close/cleanup tasks required. Before declaring the service ready, hit a health endpoint, submit one controlled bid, verify the log/correlation id, and confirm failed jobs and error alerts have an owner.

## Explicit non-goals for the first 1–2 hours

Do not build proxy bidding, bid increments by currency, payment capture, websockets, anti-fraud scoring, read replicas, sharding, or event sourcing unless requested. Leave clear seams (`PlaceBid`, policy, queue notifications) so those features can be added without changing the transactional invariant.
