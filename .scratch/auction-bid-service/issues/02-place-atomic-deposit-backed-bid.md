# 02: Place an atomic deposit-backed Bid

**What to build:** A selected Demo dealer can place a whole-AED Bid from a Listing, see the high Bid and Wallet change, and inspect code-only history. Competing Dealers can displace each other safely. The [public Dealer Portal](https://dealers.sellanycar.com/#/en/home) says a refundable deposit relates to bidding and exposure limits; the Deposit, Available limit, Active reservation, and Replacement bid rules here are independent fictional demo decisions, not claims about that portal's implementation.

**Blocked by:** 01: Browse Listings and choose a Demo dealer.

**Status:** complete

- [x] Wallet summary shows Deposit, total Active reservations, and Available limit in AED; money is stored without floating-point arithmetic.
- [x] Bid submission requires the selected Dealer's session, a valid whole-AED amount at least AED 1 above the high Bid, and an Idempotency-Key. Public read access remains available without a selected Dealer.
- [x] One short PostgreSQL transaction locks the Listing and consistently protects relevant Wallet and reservation state before rechecking the high Bid, closing time, and resulting Deposit capacity.
- [x] An accepted Bid becomes the high Bid, creates its Active reservation, releases the displaced leader's reservation, and preserves Bid history. A Replacement bid substitutes for the Dealer's own earlier reservation on that Listing.
- [x] Closed, too-low, over-capacity, invalid, and unauthorized requests return stable JSON failures without partial Bid or reservation state. A closed winner's reservation remains active.
- [x] Database constraints protect idempotency. Repeating an accepted or business-rule-rejected attempt returns its original result; reusing the key with changed input returns a conflict.
- [x] A thin transport layer, Form Request, policy seam, named bid rate limiter, and safe logging are present. The Dealer identity is never accepted from the request body.
- [x] PostgreSQL feature tests exercise the domain invariant through HTTP, including first Bid, competing Bid, own Replacement bid, rejection with no side effects, and idempotent replay. Sequential tests are not described as proof of concurrent locking.
