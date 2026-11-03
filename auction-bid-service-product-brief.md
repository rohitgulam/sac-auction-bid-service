# Auction Bidding Demonstrator — Product Brief

## Purpose

Build a small, standalone full-stack application that demonstrates the backend reasoning required for a deposit-backed dealer auction. It is a portfolio exercise, **not** a SellAnyCar product, implementation, or affiliation.

The application should be polished enough for a recruiter to use without setup and technical enough for an engineer to inspect. Its primary evidence is correctness under competing bids, not visual complexity.

## Public research

SellAnyCar's public Dealer Portal describes a professional-dealer auction in the UAE:

- Dealers register, verify their account, complete a profile, and make a refundable security deposit that sets their bidding limit.
- Vehicles are inspected before listing; dealers see a condition report and photos.
- The portal offers a digital wallet, report and VAT-invoice downloads, payment-proof upload, and bidding through web or mobile.
- It describes three auction states: a 12-minute live offer, a 72-hour deal offer, and upcoming offers for vehicles still being inspected.
- It states that more than 150 cars and plates can be listed daily.

Sources: [Dealer Portal home](https://dealers.sellanycar.com/) and [Dealer Portal FAQ](https://dealers.sellanycar.com/#/en/faq).

The consumer flow is separate: online valuation, branch appointment, inspection, offer, and payment after a sale. This project deliberately focuses on the dealer-auction workflow rather than inventing a vehicle valuation model. Source: [SellAnyCar UAE FAQ](https://uae.sellanycar.com/faq).

The Backend (PHP) role explicitly calls for PHP or Node.js, microservices experience, scalable web applications, Laravel/Symfony, refactoring, caching, architecture changes, code review, debugging, and performance. Source: [job listing](https://jobs.smartrecruiters.com/SellAnyCarcom/744000153727459).

## Recommended concept

**Project name:** Auction Bid Service

A dealer-facing web application for bidding on inspected vehicles, backed by a standalone Laravel API/service. It uses fictional data and neutral branding.

## What a reviewer can do

1. Choose a seeded demo dealer from a clearly labelled demo login screen.
2. See the dealer's deposit, reserved amount, and available bidding limit.
   Increase the fictional deposit with a clearly labelled demo top-up.
3. Open an inspected vehicle listing or a number plate listing and view its concise details.
4. Place a bid.
5. See the current highest bid update and the wallet reservation change.
6. Try an invalid bid:
   - below the current highest bid;
   - above the available limit;
   - after the auction is closed.
7. Open a simple bid history/audit view.

## Scope boundary

### Must build

- At least 20 seeded demo dealers, selectable by bidder code into separate browser sessions without passwords. Do not show dealer names in the UI.
- Visitors can browse listings without selecting a dealer. Bidding, demo top-ups, and wallet access require a selected dealer in that browser session.
- Fifty initially seeded listings: 40 fictional vehicles and 10 fictional number plates, spanning open live auctions, open deal auctions, and closed auctions. Live Offers last 12 minutes; Deal Offers last 72 hours. When a visitor opens the dashboard and no listing of an offer type remains open, create a fresh listing of that type. Retain closed listings and bid history; do not require cron or a daily reset.
- Seed fictional bid histories and active reservations on some listings, leaving others open with no bids so reviewers can inspect both states immediately.
- Listing detail with price, end time, a bid form, and either vehicle inspection highlights or number plate details.
- Wallet summary: deposit, amount reserved, available limit.
- An immediate simulated AED 10,000 or AED 50,000 deposit top-up that increases the selected dealer's available limit, appears in wallet activity, and collects no payment details or money.
- Idempotent demo top-ups: retrying the same top-up request credits the deposit once and returns the original result.
- Bid placement with transaction safety.
- Bid history.
- Public bid history uses stable bidder codes, such as `D-014`, instead of dealer names; the selected dealer's own bids are also marked “You”.
- Livewire polling so every open demo session can see bid and wallet changes within a few seconds; two browsers are only a simple review scenario, not a limit. Animate changed high bids, leader labels, and wallet amounts; reorder listing cards by recent bid activity with visible movement.
- Focused tests and a clear README.

### Explicitly do not build

- Real payments, payment-proof uploads, bank integrations, VAT invoices, delivery, production authentication, notifications, vehicle image storage, real-time sockets, or a valuation algorithm. The demo top-up is explicitly simulated.
- Any claim that the app reproduces SellAnyCar's actual systems.

## Business rules

1. Monetary amounts are in AED. A dealer's available limit is `deposit - total active reservations`.
2. A bid must be greater than the current highest bid.
3. A new leading bid cannot make the dealer's total active reservations exceed the deposit. When the dealer raises their own leading bid, replace their existing reservation for that listing when checking this limit; the wallet's displayed available limit remains `deposit - total active reservations`.
4. A bid cannot be accepted after the auction closes.
5. When a bid becomes the highest:
   - reserve that bid amount for the new highest bidder;
   - release the immediately displaced highest bidder's reservation;
   - preserve the bid history.
   A winning reservation remains active when the listing closes, until a separate settlement or demo reset.
6. A repeated request with the same idempotency key must return the original accepted or business-rule-rejected outcome without creating a second bid or reservation. A dealer who changes their wallet or bid amount must use a new key for a new attempt.
7. A failed request must not leave a partial reservation or bid. A stored rejection outcome is not a bid or reservation.
8. Vehicle and number plate listings use the same bid, reservation, closing-time, and idempotency rules; only their descriptive details differ.
9. A last-second bid does not extend a listing's fixed closing time. The server decides acceptance using the listing's end time.
10. Enter and display bids in whole AED; a new bid must increase the current high bid by at least AED 1. Store money without floating-point arithmetic.

## Backend design

### Suggested domain entities

- `Dealer`
- `Wallet` — deposit amount
- `AuctionListing` — vehicle summary, type, starts/ends at, current highest bid
- `Bid` — dealer, amount, idempotency key, accepted/rejected result
- `BidReservation` — active reserved amount linked to the current highest bid
- `InspectionSummary` — simple embedded/related data for the UI

### Suggested API

- `GET /api/demo/dashboard`
- `GET /api/listings`
- `GET /api/listings/{listing}`
- `GET /api/listings/{listing}/bids`
- `POST /api/listings/{listing}/bids`
  - body: `amount`
  - header: `Idempotency-Key`
- `GET /api/wallet`

### Correctness approach

Bid placement is the important operation. Lock the listing and relevant wallet/reservation rows inside one database transaction, validate the auction and limit rules, release the old reservation, create the new reservation and bid, then commit. Return an unambiguous API error for rejected bids. Persist an idempotency record keyed by dealer, endpoint/listing, and key.

## UI direction

Use Laravel Livewire for the interactive UI, with Tailwind CSS and Alpine.js only for small browser-only interactions. This keeps the recruiter-facing experience dynamic while leaving the important state and business logic in Laravel/PHP. Do not use a separate Vue/Nuxt or Angular application for this project.

Use PostgreSQL for local development, tests, and deployment. Its real database transaction and row-locking behaviour is part of the point of the demonstration; do not substitute SQLite for the bidding-path tests.

The visual reference can borrow high-level cues from the public Dealer Portal screenshot:

- pale blue-grey page background;
- dark navy typography and headings;
- bright blue for primary actions and status;
- white cards with subtle borders/radius;
- restrained spacing and clear dashboard hierarchy.

Do **not** copy the SellAnyCar logo, name, assets, proprietary-looking data, or page layout. Place a visible note in the footer: “Independent portfolio demonstration using fictional data.”

## What proves engineering ability

- Transactional bid placement and row locking.
- Idempotent writes.
- Meaningful validation and API error responses.
- Clean controller/service separation.
- Focused feature tests:
  - accepts a valid higher bid and updates reservations;
  - rejects a bid beyond available limit without side effects;
  - rejects a late bid;
  - repeats an idempotent bid safely;
  - prevents two competing bids from both being accepted as current winner.
- Docker setup or a one-command local setup, if time permits.
- Readme with architecture, tradeoffs, running instructions, and test command.

## Repository and review workflow

The live URL is for a recruiter; the repository must let an engineering reviewer verify how the feature was built. Treat the Git history, pull requests, CI checks, tests, and documentation as part of the deliverable.

### Keep the change history reviewable

Use a protected-quality `main` branch and build through small, coherent feature branches. Do not manufacture a long history or meaningless commits after the fact. Three honest, reviewable changes are enough:

1. **Project foundation** — Laravel/Livewire setup, PostgreSQL Docker Compose or local instructions, baseline CI, factories/seed data, code formatter.
2. **Atomic bidding core** — migrations, `PlaceBid` action, request validation, idempotency, transactions/locks, and feature tests. This is the important review PR.
3. **Dealer experience** — Livewire dashboard/listing/bid-history UI, safe feedback states, README/screenshots, and deployment notes.

Each pull request should state the user-facing change, the invariant it preserves, how it was tested, and anything intentionally deferred. Attach a short GIF or screenshots to the UI PR. Keep each commit independently understandable; avoid mixing formatting-only churn with the bidding logic.

### GitHub checks that should be visible

Add a GitHub Actions workflow that runs on pull requests and pushes to `main` using a PostgreSQL service container:

- install PHP and Composer dependencies from the lock file;
- install/build frontend assets if the stack needs it;
- wait for PostgreSQL and run migrations;
- run `composer validate --strict`;
- run `vendor/bin/pint --test`;
- run the Laravel test suite against PostgreSQL;
- run static analysis only if it is configured cleanly (for example, Larastan/PHPStan).

The workflow must fail on a test/format error. Do not add a badge or claim a check is passing until it truly is.

### Make the technical story easy to inspect

The README should contain a two-minute reviewer path: open a verified deployed demo if available, or run it locally; try a valid bid and a rejected bid; then find `PlaceBid`, its feature tests, migrations/indexes, and the CI workflow. Include a small architecture diagram or sequence diagram showing the bid transaction and reservation release. Document the deliberate boundary: PostgreSQL is used because SQLite does not demonstrate the intended lock semantics.

Include `CONTRIBUTING.md` with setup, test, formatter, and branch/PR expectations; an `.env.example` with no secrets; and a short PR template that asks for test evidence and scope notes. This is enough process evidence for a small portfolio project.

## Delivery target

Aim for a functional local browser demo first. A public deployed URL is better for a recruiter when hosting is actually available and verified; otherwise provide deployment instructions and repository evidence without a placeholder URL or a claim that the app is deployed. Do not delay the follow-up for extras that do not strengthen the core bid flow.
