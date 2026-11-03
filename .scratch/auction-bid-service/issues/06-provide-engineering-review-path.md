# 06: Provide the engineering review path

**What to build:** A recruiter can try the finished browser demo quickly, and an engineering reviewer can reproduce its PostgreSQL-backed checks and find a concise explanation of the Bid transaction, repository scope, and deliberate limitations. Include the [public Dealer Portal reference](https://dealers.sellanycar.com/#/en/home) only as cited background, clearly separate from the fictional demo rules.

**Blocked by:** 03: Increase a Demo dealer's Deposit; 04: Show bidding changes across sessions; 05: Keep timed offers available.

**Status:** complete

- [x] GitHub Actions runs on pull requests and pushes to main with a PostgreSQL service, locked dependency installs, asset build, migrations, strict Composer validation, Pint check, and the Laravel test suite. It fails on test or format errors.
- [x] Local setup and test instructions are exact for PostgreSQL; no SQLite bidding-path test is used. Environment examples contain no secrets.
- [x] A PR template asks for summary, invariant or risk, test evidence, UI screenshots, and intentional non-goals. Contributing guidance covers setup, branch naming, focused PRs, tests, and formatting.
- [x] The README provides a two-minute browser path, a Bid transaction diagram, where to inspect the action and tests, a two-browser walkthrough, scope limits, and honest concurrency-test limits.
- [x] A verified deployed URL appears only if one exists. No fabricated check status, approval, reviewer, or project history is claimed.
- [x] Focused tests, the full test suite, formatting, frontend build, and CI-equivalent commands pass, or any external limitation is reported precisely.
