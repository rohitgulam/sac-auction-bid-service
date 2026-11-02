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

1. Open a seeded demo account.
2. See the dealer's deposit, reserved amount, and available bidding limit.
3. Open an inspected vehicle listing and view concise condition-report data.
4. Place a bid.
5. See the current highest bid update and the wallet reservation change.
6. Try an invalid bid:
   - below the current highest bid;
   - above the available limit;
   - after the auction is closed.
7. Open a simple bid history/audit view.

## Scope boundary

### Must build

- One dealer demo account.
- Three seeded vehicles: an open live auction, an open deal auction, and a closed auction.
- Listing detail with price, end time, inspection highlights, and bid form.
- Wallet summary: deposit, amount reserved, available limit.
- Bid placement with transaction safety.
- Bid history.
- Focused tests and a clear README.

### Explicitly do not build

- Real payments, payment-proof uploads, bank integrations, VAT invoices, delivery, authentication flows, notifications, vehicle image storage, real-time sockets, or a valuation algorithm.
- Any claim that the app reproduces SellAnyCar's actual systems.

## Business rules

1. A dealer's available limit is `deposit - total active reservations`.
2. A bid must be greater than the current highest bid.
3. A bid cannot exceed the dealer's available limit.
4. A bid cannot be accepted after the auction closes.
5. When a bid becomes the highest:
   - reserve that bid amount for the new highest bidder;
   - release the immediately displaced highest bidder's reservation;
   - preserve the bid history.
6. A repeated request with the same idempotency key must return the original outcome without creating a second bid or reservation.
7. A failed request must not leave a partial reservation or bid.

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

## Delivery target

Aim for a functional demo in 1–2 hours. A public deployed URL is better for a recruiter; a GitHub repository with screenshots and a short screen recording is the fallback. Do not delay the follow-up for extras that do not strengthen the core bid flow.
