# Codex CLI build prompt — Auction Bid Service

Copy the prompt below into Codex CLI from this already-initialized repository's root.

```text
Build a small, independent full-stack portfolio demonstration called “Auction Bid Service.” It must not claim to be, clone, or be affiliated with SellAnyCar. Use fictional vehicles and neutral branding throughout.

This repository has already been initialized. Do not create a new Laravel project, overwrite its existing setup, or replace its dependency choices blindly. First inspect `AGENTS.md`, `composer.json`, `package.json`, routes, existing tests, and the current Git status.

Then read these local files in full:

- auction-bid-service-product-brief.md
- laravel-production-workflow.md
- GLOSSARY.md

The product brief is the source of truth for scope and bid/wallet correctness, the glossary for domain terms, and the Laravel workflow for implementation practices.

## Outcome

Ship a recruiter-friendly demo that can be opened in a browser and used without reading code. A reviewer should be able to view an inspected vehicle, see a demo dealer wallet, submit a higher bid, observe the high bid and reserved/available limit update, try invalid bids, and inspect bid history.

## Work in this order

1. Inspect the current repository and state a concise implementation plan: stack, data model, critical transaction, routes, pages, tests, and local run command.
2. Implement the plan immediately after that summary. Do not wait for confirmation for ordinary engineering choices. Stop only if a decision is truly blocking or an existing codebase makes the requested scope unsafe.
3. Run the focused test suite and formatting/linting commands available in the project. Fix failures caused by your work.
4. Build the repository-review evidence described below: GitHub Actions, a PR template, CONTRIBUTING guidance, and reviewer-oriented README material.
5. Finish with a compact handoff: what works, the command to run it, test evidence, files changed, and any intentional shortcuts.

## Stack and delivery constraints

- Keep the installed Laravel/PHP versions and existing conventions. Confirm direct package versions before relying on their APIs.
- Build a Laravel modular monolith. Do not split it into microservices merely to match a job-description keyword.
- Use Laravel Livewire 3 for the interactive UI, Tailwind CSS for styling, and Alpine.js only for small client-only interactions. Livewire is an explicitly approved dependency for this project if it is not installed. Do not use plain Blade-only forms, Nuxt/Vue, React, Inertia, or Angular for this project.
- Use PostgreSQL for local development, CI tests, and deployment. Do not use SQLite for the bidding path: the project needs genuine PostgreSQL transaction and row-lock behaviour.
- Seed at least 20 fictional demo dealers with a clearly labelled, passwordless demo identity chooser that shows bidder codes only, not dealer names. Each browser session selects its own dealer. Initially seed 50 listings: 40 fictional vehicles and 10 fictional number plates, spanning open live, open deal, and closed auctions. Live Offers last 12 minutes and Deal Offers last 72 hours. When a visitor opens the dashboard and no listing of an offer type remains open, create a fresh listing of that type; retain closed listings and bid history. Do not require cron or a daily reset. Include concise fictional inspection data for vehicles and suitable details for number plates.
- Allow browsing listings before a dealer is selected. Require a selected demo dealer in the browser session for bids, demo top-ups, and wallet access.
- Seed fictional bids and matching reservations on some listings, while leaving other open listings with no bids.
- Include a clearly labelled simulated AED 10,000 or AED 50,000 deposit top-up for the selected demo dealer. Apply it immediately, show it in wallet activity, and increase available limit without collecting payment details or moving money. Give top-up requests idempotency keys so retries credit the deposit only once. Avoid real sign-up, payments, uploads, invoices, e-mail, web sockets, vehicle image storage, delivery, proxy bidding, and valuation logic.
- Add a clear footer disclaimer: “Independent portfolio demonstration using fictional data.”

## Repository workflow and technical-review evidence

The deployed URL is for a recruiter, but the GitHub repository is also a deliverable for engineering review. Add the following, without fabricating history or implying formal review that did not happen:

- A GitHub Actions workflow that runs on pull requests and pushes to `main` using a PostgreSQL service container. It must install locked dependencies, build assets if applicable, run migrations, run `composer validate --strict`, `vendor/bin/pint --test`, and the Laravel test suite against PostgreSQL. Add Larastan/PHPStan only if it is fully configured and passing.
- `.github/pull_request_template.md` asking for summary, invariant/risk, test evidence, screenshots for UI changes, and intentional non-goals.
- `CONTRIBUTING.md` with exact local PostgreSQL/Docker setup, test and formatter commands, branch naming, and the rule that PRs stay focused.
- `.env.example` containing no secrets.
- A README with a two-minute review path: verified deployed demo URL if one exists, otherwise local browser run instructions; main user flow; architecture/transaction diagram; where `PlaceBid` and tests live; test command; and scope limits. Never invent a deployment URL.

The repository already has a foundation commit. Preserve it and any existing uncommitted documentation edits. Organize the remaining work into coherent, independently reviewable commits, without recreating the foundation commit:

1. `feat: add atomic deposit-backed bidding`
2. `feat: add dealer auction dashboard and review docs`

Do not manufacture dates, approvals, reviewers, or a fake long commit history. The final repository should honestly show a clean, understandable implementation path.

## Correctness requirements (non-negotiable)

Model the deposit-backed bidding limit in AED. For the seeded dealer:

- available limit = deposit minus active reservations;
- a bid must exceed the current high bid;
- a new leading bid cannot make total active reservations exceed the deposit; if the dealer raises their own leading bid, replace their existing reservation for that listing when checking this limit;
- bidding fails after the auction closes;
- an accepted high bid reserves its amount;
- the superseded high bidder’s active reservation is released;
- a winning reservation remains active after the listing closes, until a separate settlement or demo reset;
- bid history stays intact;
- repeating a request with the same idempotency key returns the original accepted or business-rule-rejected result and creates no extra bid/reservation; after a top-up or changed amount, a new attempt uses a new key;
- a rejected/failed bid leaves no partial database state.
- vehicle and number plate listings follow the same bidding, wallet, closing-time, and idempotency rules; only their descriptive details differ.
- last-second bids do not extend the fixed closing time; the server-side end-time check decides acceptance.
- bid input uses whole AED and must increase the current high bid by at least AED 1; store money without floating-point arithmetic.

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

- wallet summary (deposit, reserved, available) and a simulated deposit top-up;
- listing cards and an inspection-summary detail view;
- bid form with visible, helpful responses for success and business-rule failures;
- public bid-history/audit display using stable fictional bidder codes rather than dealer names, with the selected dealer's bids marked “You”;
- a visible demo dealer chooser rather than production authentication;
- Livewire polling about every two seconds so every open browser session sees bids and wallet changes without a manual refresh. Two browsers are a simple review scenario, not a limit. Animate changed high bids, leader labels, and wallet amounts; reorder listing cards by recent bid activity with visible movement.

Use only broad visual cues inspired by the public Dealer Portal: pale blue-grey background, dark navy headings, bright blue primary actions/status, white rounded cards and restrained spacing. Do not copy the company logo, wording, layout, assets, or name.

## Tests and documentation

Add focused feature tests for:

1. valid higher bid updates high bid and wallet reservation;
2. at/below-high bid is rejected with no side effects;
3. bid beyond available limit is rejected with no side effects;
4. closed auction is rejected;
5. repeated idempotency key returns the original result and creates one bid;
6. no selected demo dealer and invalid-payload behavior;
7. raising a dealer's own leading bid replaces that listing's reservation;
8. a repeated simulated top-up credits the deposit once;
9. separate demo sessions see updated high bids, leader codes, and wallet values through Livewire polling;
10. the key domain invariant is exercised through HTTP/API, not just a mocked unit test.

Add a README containing: the purpose/disclaimer; screenshots or GIF if practical; architecture/data model; the locking and idempotency approach; local setup/run/test commands; the demo URL/credentials if any; and known intentional scope limits. Add a simple health route if it takes little time.

## Quality bar

Use migrations, factories/seeders, clear names, transactions, foreign keys/indexes, Laravel logging with safe context, no secrets in the repository, and production-safe error rendering. Format the code with the project’s formatter (Laravel Pint if configured). Do not claim that sequential tests prove real concurrent locking; document that distinction honestly.
```
