# Auction Bid Service — Specification

## Problem Statement

A recruiter needs to understand and try a dealer auction demonstration in a browser, while an engineering reviewer needs to inspect evidence that bidding and deposit-backed wallet state remain correct when multiple demo dealers compete. The current repository has the Laravel, Livewire, Tailwind, and PostgreSQL foundation but no auction implementation. The demonstration must use fictional data and neutral branding, with no suggestion that it is a SellAnyCar product or a reproduction of its internal rules.

## Solution

Build an independent Laravel application where visitors browse fictional vehicle and number plate listings, choose a demo dealer by bidder code, and place bids from separate browser sessions. Each session sees bid, leader, and wallet changes within a few seconds through Livewire polling and clear animations. A deposit-backed available limit constrains bids; a simulated top-up lets a demo dealer increase that limit. PostgreSQL transactions, row locks, reservations, and idempotency records protect the write path. The repository provides tests, CI, and a short review path.

## User Stories

1. As a recruiter, I want to open the demo without setup or registration, so that I can understand its purpose immediately.
2. As a recruiter, I want to see a clear independent portfolio disclaimer, so that I do not mistake the demo for a commercial auction portal.
3. As a visitor, I want to browse listings before choosing a demo dealer, so that I can explore the inventory without committing to a session.
4. As a visitor, I want to filter vehicle and number plate listings, so that I can find the type of lot I want to inspect.
5. As a visitor, I want to distinguish Live Offers, Deal Offers, and closed listings, so that I know which listings accept bids.
6. As a visitor, I want to see each listing's current high bid and closing time, so that I understand its auction state.
7. As a visitor, I want to read concise fictional inspection highlights for a vehicle, so that I can make an informed demo bid.
8. As a visitor, I want to see suitable details for a number plate, so that I can identify the lot without a vehicle inspection report.
9. As a visitor, I want to see bid history using bidder codes, so that I can follow competition without seeing dealer names.
10. As a visitor, I want to choose a demo dealer by bidder code without a password, so that I can start bidding quickly.
11. As a reviewer, I want to use different demo dealers in separate browser sessions, so that I can observe competing bids and reservation release.
12. As a demo dealer, I want to see my deposit, active reservations, and available limit in AED, so that I know my current bidding capacity.
13. As a demo dealer, I want to see which bids are mine, so that I can follow my position using a “You” marker alongside my bidder code.
14. As a demo dealer, I want to submit a whole-AED bid above the current high bid, so that I can become the leader.
15. As a demo dealer, I want my accepted leading bid to reserve its full amount, so that my wallet reflects my exposure.
16. As a demo dealer, I want a replacement bid on a listing I already lead to replace its prior reservation, so that raising my bid uses only the additional capacity required.
17. As a demo dealer, I want my reservation released when another dealer outbids me, so that my available limit recovers.
18. As a demo dealer, I want to see a helpful response when my bid is too low, so that I can submit a valid amount.
19. As a demo dealer, I want to see a helpful response when my bid exceeds my deposit capacity, so that I can choose another amount or top up.
20. As a demo dealer, I want a bid rejected after the server-side closing time, so that a late request cannot change the winner.
21. As a demo dealer, I want a bid retried with the same idempotency key to return its original result, so that network retries do not create extra bids or reservations.
22. As a demo dealer, I want a new bid attempt after a top-up to use a new key, so that a previous rejection remains a stable record of that attempt.
23. As a demo dealer, I want to choose a simulated AED 10,000 or AED 50,000 top-up, so that I can increase my available limit without using real money.
24. As a demo dealer, I want to see each simulated top-up in wallet activity, so that the deposit change is understandable.
25. As a demo dealer, I want a retried top-up to credit my deposit once, so that a double-click or network retry cannot duplicate it.
26. As a demo dealer, I want a closed winning bid to remain reserved, so that the wallet continues to show exposure from an unsettled win.
27. As a viewer, I want high bids, leader markers, and wallet amounts to update within a few seconds, so that competing sessions feel connected.
28. As a viewer, I want changed values to animate and recently active listing cards to move visibly, so that I can spot what changed.
29. As a viewer, I want the dashboard to retain open Live and Deal Offers when I return later, so that short auction windows do not leave the demo unusable.
30. As an engineering reviewer, I want old listings and bid history retained after replenishment, so that I can audit prior bidding activity.
31. As an engineering reviewer, I want to find focused PostgreSQL tests and an explanation of the bid transaction, so that I can assess the correctness claim.
32. As an engineering reviewer, I want CI checks and exact local commands, so that I can reproduce the build and tests.
33. As a recruiter, I want a verified public URL if one exists, or honest local instructions otherwise, so that I do not follow a placeholder link.

## Implementation Decisions

- Keep the installed Laravel, Livewire 3, Tailwind CSS, PHP, and PostgreSQL versions and existing application conventions. Build one modular monolith.
- Use AED throughout. Accept and display whole-AED bids with a minimum AED 1 increase; store money as integer minor units or an exact decimal, never floating point.
- Seed at least 20 fictional Demo dealers identified publicly only by stable Bidder codes. A visitor selects a code into a browser session. This is openly labelled demo identity selection, not production authentication. A selected session is required for bids, wallet access, and top-ups; listings and code-only Bid history are public.
- Initially seed 50 Listings: 40 fictional vehicles and 10 fictional number plates, spread across open Live Offers, open Deal Offers, and closed listings. Seed plausible Bid histories and matching Active reservations on some listings; leave others open without bids.
- A Live Offer lasts 12 minutes and a Deal Offer lasts 72 hours. Closing times are fixed: a last-second Bid does not extend an offer. Server time decides whether bidding is open. When a dashboard visit finds no open Listing of an offer type, create a fresh one of that type with a concurrency-safe check. Retain closed Listings and their Bid history. No cron or daily reset is required.
- Vehicle and number plate Listings share the same bidding, reservation, closing-time, and idempotency rules. Their descriptive fields differ: vehicles have concise inspection data, while plates have plate-specific details.
- A Dealer's Available limit is Deposit minus total Active reservations. A proposed leading Bid is allowed only if its resulting reservations fit within the Deposit. A Replacement bid substitutes for that Dealer's prior reservation on the same Listing when checking capacity. The newly accepted Bid reserves its amount; the displaced leader's reservation is released. A winning reservation remains active when the Listing closes, because settlement is outside this demo.
- Place the complete bid state change inside one short PostgreSQL transaction. Lock the Listing before rechecking its high Bid and end time. Protect relevant Wallet and reservation state with a consistent lock order. Create the Bid, release any displaced reservation, create the new reservation, update the high Bid, and record the result atomically. A rejected Bid leaves no Bid or reservation side effects.
- Require an Idempotency-Key for bid submission. Enforce uniqueness in the database for the Dealer, Listing, and key; compare a normalized request fingerprint. A repeated matching key returns the original accepted or business-rule-rejected outcome. Reusing a key with changed input returns a stable conflict error. Validation failures do not create bid attempts.
- Provide stable JSON responses and machine-readable business error codes. Validate bid input with a Form Request, authorize Listing access through a policy seam, apply a named bid rate limiter, and keep controllers thin. Resolve the Dealer from the server-side session, never from a client-supplied dealer ID. Protect session writes with CSRF measures.
- A simulated Demo top-up offers fixed AED 10,000 and AED 50,000 choices. It immediately increases Deposit, records wallet activity, and never collects payment details or transfers money. Protect it with a short Wallet transaction and database-backed idempotency so retries credit once.
- Expose documented JSON routes for the dashboard, Listings, Listing Bid history, bid submission, Wallet, and simulated top-up. Provide an obvious browser entry route and retain the existing health route.
- Build the browser experience in Livewire. Poll compact, visible auction and wallet state about every two seconds in every open session; two browsers are a review scenario, not a session limit. Animate changed high bids, leader markers, and wallet values, and reorder listing cards by recent bid activity with visible movement. Use Alpine.js only for small client-only effects.
- Use pale blue-grey, dark navy, bright blue, and white rounded cards as broad visual cues. Do not copy SellAnyCar names, logos, assets, page layout, or wording. Show “Independent portfolio demonstration using fictional data.” in the footer.
- Add GitHub Actions for pull requests and pushes to main using PostgreSQL. Install locked dependencies, build assets, run migrations, strict Composer validation, Pint checks, and the Laravel test suite. Add static analysis only if it is configured and passing. Add a PR template, contributing instructions, and a reviewer-oriented README. Preserve the existing foundation commit and the current uncommitted documentation edits; do not fabricate reviews or history.

## Testing Decisions

- Test at the highest useful seams: HTTP requests for bid, wallet, top-up, access, and API behavior; Livewire component behavior for polling and visible state changes. Assert externally observable results and persisted invariants, not private method calls or lock implementation details.
- Use PostgreSQL for local and CI bidding tests. The repository currently has only starter PHPUnit examples, so feature tests are the appropriate prior pattern; there is no existing auction test seam to reuse.
- Verify that a valid higher Bid advances the high Bid and changes the correct Dealer's Active reservation and Available limit.
- Verify that an at-or-below-high Bid, a capacity-exceeding Bid, a closed Listing, and an invalid payload return stable failures without creating a Bid or reservation.
- Verify that raising a Dealer's own high Bid replaces that Listing's reservation and checks the resulting total against Deposit.
- Verify that an outbid Dealer's reservation is released and historical Bids remain visible under Bidder codes.
- Verify accepted and business-rule-rejected bid replay, including a changed-payload key conflict and a new attempt after a Demo top-up.
- Verify that a simulated top-up increases Deposit and wallet activity once even when retried.
- Verify that public browsing works without a selected Dealer while bid submission, Wallet access, and top-up require one; verify policy and rate-limit responses where applicable.
- Verify fixed closing-time behavior and on-visit replenishment without deleting old Listings or Bid history.
- Verify that separate demo sessions observe the changed leader, high Bid, and Wallet state after Livewire polling. Manually inspect the two-browser animations; a sequential test alone does not prove true concurrent locking. If a reliable two-process PostgreSQL test is feasible in this environment, add one for competing bids and report its limits accurately.
- Run focused tests, the complete test suite, Pint, the frontend build, and the CI-equivalent commands. Fix failures introduced by this work.

## Out of Scope

- Real sign-up, secure production authentication, real payments or payment details, payment-proof uploads, banking, VAT invoices, vehicle image storage, delivery, and valuation.
- Web sockets, instant server push, notifications, e-mail, proxy bidding, upcoming-offer alerts, automatic bid extensions, and an auction settlement workflow.
- Microservices, distributed locks, cache as the bidding source of truth, and unconfigured static analysis.
- A scheduled reset or cron job. A public deployment URL is not claimed until one is actually deployed and verified.

## Further Notes

- **Public reference:** [SellAnyCar Dealer Portal home and FAQ](https://dealers.sellanycar.com/#/en/home) describes inspected vehicles and number plates, a refundable AED deposit related to bid and exposure limits, a digital wallet, 12-minute Live Offers, and 72-hour Deal Offers. These are background references only. The portal does not publish the exact Deposit-to-limit formula, reservation lifecycle, replacement-bid handling, or idempotency behavior; this specification makes independent fictional choices for those rules.
- This specification follows the project's Product brief, Laravel workflow, and Glossary. The README should give a reviewer a two-minute path through the browser flow, transaction diagram, focused tests, and CI. A verified deployed URL may be added later; otherwise the local run instructions are the review entry point.
- The existing repository has a Laravel and PostgreSQL foundation commit. Implementation should proceed in coherent bidding-core and dealer-experience changes, while preserving existing user edits to the planning documents.
