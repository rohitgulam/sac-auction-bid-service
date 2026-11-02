# 03: Increase a Demo dealer's Deposit

**What to build:** A selected Demo dealer can choose a clearly simulated AED 10,000 or AED 50,000 top-up, immediately see a higher Deposit and Available limit, and review the change in Wallet activity. No money moves and no payment details are collected.

**Blocked by:** 02: Place an atomic deposit-backed Bid.

**Status:** complete

- [x] The Wallet offers only the two agreed fixed top-up amounts and labels the action as fictional.
- [x] A successful top-up increases Deposit and Available limit while leaving existing Active reservations and Bid history intact.
- [x] Each top-up appears in Wallet activity with its amount and time.
- [x] A repeated request with the same idempotency key credits once and returns the original result; changed input with that key is rejected consistently.
- [x] Top-up is unavailable before a Demo dealer is selected. No card, bank, payment-proof, or payment-gateway flow is introduced.
- [x] PostgreSQL-backed HTTP and Livewire tests cover success, validation, session access, and duplicate submission.
