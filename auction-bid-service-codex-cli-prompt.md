# Codex CLI build prompt — Auction Bid Service

Copy the prompt below into Codex CLI from the repository directory where you want the project built.

```text
Build a small, independent full-stack portfolio demonstration called “Auction Bid Service.” It must not claim to be, clone, or be affiliated with SellAnyCar. Use fictional vehicles and neutral branding throughout.

Before making changes, read these two local files in full:

- research/auction-bid-service-product-brief.md
- research/laravel-production-workflow.md

They are the source of truth for scope and technical decisions. If they conflict, protect the bid/wallet correctness rules in the product brief and use the Laravel workflow for implementation practices.

## Outcome

Ship a recruiter-friendly demo that can be opened in a browser and used without reading code. A reviewer should be able to view an inspected vehicle, see a demo dealer wallet, submit a higher bid, observe the high bid and reserved/available limit update, try invalid bids, and inspect bid history.

## Work in this order

1. Inspect the current repository and state a concise implementation plan: stack, data model, critical transaction, routes, pages, tests, and local run command.
2. Implement the plan immediately after that summary. Do not wait for confirmation for ordinary engineering choices. Stop only if a decision is truly blocking or an existing codebase makes the requested scope unsafe.
3. Run the focused test suite and formatting/linting commands available in the project. Fix failures caused by your work.
4. Finish with a compact handoff: what works, the command to run it, test evidence, files changed, and any intentional shortcuts.

## Stack and delivery constraints

- Prefer the current project’s Laravel version if one exists; otherwise use current stable Laravel with a supported PHP version.
- Build a Laravel modular monolith. Do not split it into microservices merely to match a job-description keyword.
- Use Laravel Livewire 3 for the interactive UI, Tailwind CSS for styling, and Alpine.js only for small client-only interactions. Do not use plain Blade-only forms, Nuxt/Vue, React, Inertia, or Angular for this project.
- Use PostgreSQL for local development, CI tests, and deployment. Do not use SQLite for the bidding path: the project needs genuine PostgreSQL transaction and row-lock behaviour.
- Seed one demo dealer and exactly three listings: one live, one deal offer, and one closed. Include concise fictional inspection data.
- Avoid real sign-up, payments, uploads, invoices, e-mail, web sockets, vehicle image storage, delivery, proxy bidding, and valuation logic.
- Add a clear footer disclaimer: “Independent portfolio demonstration using fictional data.”

## Correctness requirements (non-negotiable)

Model the deposit-backed bidding limit. For the seeded dealer:

- available limit = deposit minus active reservations;
- a bid must exceed the current high bid;
- a bid cannot exceed the available limit;
- bidding fails after the auction closes;
- an accepted high bid reserves its amount;
- the superseded high bidder’s active reservation is released (even if the seeded UI has one main dealer, design the data model so the rule holds);
- bid history stays intact;
- repeating a request with the same idempotency key returns the original result and creates no extra bid/reservation;
- a rejected/failed bid leaves no partial database state.

Implement `PlaceBid` as a small application action/service. Keep controllers thin and place the state-changing work inside one short database transaction. Lock the auction/listing row with a pessimistic lock before rechecking state and high bid. Lock or otherwise consistently protect the wallet/reservation rows required by the available-limit calculation. Use unique database constraints for idempotency, not only application checks. Store money as integer minor units or a fixed decimal type—never floats.

Use a Form Request for input validation, a policy/authorization seam for object access, stable JSON/API errors for write failures, and a named rate limiter for bid submission. Do not queue the bid itself. If you add an event/job, dispatch it only after commit and make it non-critical.

## Routes and UI

Provide either these JSON endpoints or equivalent well-documented endpoints:

- `GET /api/demo/dashboard`
- `GET /api/listings`
- `GET /api/listings/{listing}`
- `GET /api/listings/{listing}/bids`
- `POST /api/listings/{listing}/bids` with `Idempotency-Key`
- `GET /api/wallet`

Also provide an obvious browser route, such as `/`, with:

- wallet summary (deposit, reserved, available);
- listing cards and an inspection-summary detail view;
- bid form with visible, helpful responses for success and business-rule failures;
- bid-history/audit display;
- a visible demo context rather than an authentication/setup obstacle.

Use only broad visual cues inspired by the public Dealer Portal: pale blue-grey background, dark navy headings, bright blue primary actions/status, white rounded cards and restrained spacing. Do not copy the company logo, wording, layout, assets, or name.

## Tests and documentation

Add focused feature tests for:

1. valid higher bid updates high bid and wallet reservation;
2. at/below-high bid is rejected with no side effects;
3. bid beyond available limit is rejected with no side effects;
4. closed auction is rejected;
5. repeated idempotency key returns the original result and creates one bid;
6. unauthenticated/unauthorized and invalid-payload behavior if authentication is enabled;
7. the key domain invariant is exercised through HTTP/API, not just a mocked unit test.

Add a README containing: the purpose/disclaimer; screenshots or GIF if practical; architecture/data model; the locking and idempotency approach; local setup/run/test commands; the demo URL/credentials if any; and known intentional scope limits. Add a simple health route if it takes little time.

## Quality bar

Use migrations, factories/seeders, clear names, transactions, foreign keys/indexes, Laravel logging with safe context, no secrets in the repository, and production-safe error rendering. Format the code with the project’s formatter (Laravel Pint if configured). Do not claim that sequential tests prove real concurrent locking; document that distinction honestly.
```
