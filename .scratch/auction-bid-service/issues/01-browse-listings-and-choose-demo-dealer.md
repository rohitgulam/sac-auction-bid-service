# 01: Browse Listings and choose a Demo dealer

**What to build:** A visitor can open the independent portfolio demo, browse fictional vehicle and number plate Listings, inspect their details and code-only Bid history, then choose one of at least 20 Demo dealers by Bidder code for that browser session. Use the [public Dealer Portal](https://dealers.sellanycar.com/#/en/home) only as background for inspected vehicles, number plates, offer types, and broad visual cues. Do not copy its branding, layout, assets, wording, or imply affiliation.

**Blocked by:** None (can start immediately).

**Status:** complete

- [x] Initially seed 40 fictional vehicle Listings, 10 fictional number plate Listings, and at least 20 Demo dealers identified in the UI by Bidder code only.
- [x] Seed a mix of open Live Offers, open Deal Offers, closed Listings, some code-only Bid histories with matching reservations, and some open Listings without Bids.
- [x] Visitors can browse and filter Listings, open details, and inspect public Bid history without choosing a Dealer. Vehicles show concise inspection highlights; plates show plate-specific details.
- [x] A clearly labelled passwordless chooser stores the selected Demo dealer in that browser session. Separate browser sessions can choose different codes.
- [x] The UI displays the exact footer disclaimer: “Independent portfolio demonstration using fictional data.”
- [x] PostgreSQL-backed feature tests cover public browsing, seed counts and state consistency, code-only history, and dealer selection. No production authentication is implied.
