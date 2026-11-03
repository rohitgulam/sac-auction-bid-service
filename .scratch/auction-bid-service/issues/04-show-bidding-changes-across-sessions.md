# 04: Show bidding changes across sessions

**What to build:** Viewers in separate Demo dealer sessions see newly accepted Bids, leader changes, and Wallet changes within a few seconds, with animations that make changes easy to spot. Two browsers are a convenient review scenario, not a limit on sessions.

**Blocked by:** 02: Place an atomic deposit-backed Bid; 03: Increase a Demo dealer's Deposit.

**Status:** complete

- [x] Livewire polls compact, visible auction and Wallet state about every two seconds without requiring manual refresh or web sockets.
- [x] High Bid, leader Bidder code, “You” marker, reserved amount, Available limit, and simulated top-up changes update in the appropriate session.
- [x] Changed values animate clearly, and Listing cards reorder by recent Bid activity with visible movement. Feedback remains understandable without relying on animation alone.
- [x] Polling does not change Bid or reservation state and avoids reloading full details for all 50 initial Listings on every tick.
- [x] Livewire tests verify externally visible state after another session's Bid or top-up; a manual two-browser walkthrough verifies the animation and movement.
