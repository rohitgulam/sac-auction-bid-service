# 05: Keep timed offers available

**What to build:** The demo retains fixed closing times and still offers a fresh open Listing when a visitor returns after an offer type has expired. The [public Dealer Portal](https://dealers.sellanycar.com/#/en/home) describes 12-minute Live Offers and 72-hour Deal Offers; this independent demo uses those durations without copying the portal's other auction mechanics.

**Blocked by:** 01: Browse Listings and choose a Demo dealer; 02: Place an atomic deposit-backed Bid.

**Status:** complete

- [x] Live Offers last 12 minutes and Deal Offers last 72 hours. A last-second Bid never extends the fixed end time; the server-side time check determines acceptance.
- [x] On dashboard visit, if an offer type has no open Listing, a concurrency-safe check creates a fresh Listing of that type without requiring cron.
- [x] Concurrent dashboard visits do not create an unintended burst of duplicate replacement Listings.
- [x] Closed Listings, winning Active reservations, and Bid history remain available for review after replenishment. No daily reset or settlement flow is added.
- [x] PostgreSQL feature tests cover boundary-time Bid rejection, replenishment, retained history, and the no-open-offer case.
