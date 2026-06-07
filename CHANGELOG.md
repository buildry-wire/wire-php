# Changelog

Format: Keep a Changelog; semver.

## [Unreleased]

## [1.0.0] - 2026-06-07
First public release.

### Added
- Wire client with bearer auth, automatic idempotency keys, and retry/backoff
  with jitter honoring `Retry-After`.
- `paymentIntents`, `charges`, `events`, `webhookEndpoints` resources.
- Auto-pagination iterator following `has_more` via `starting_after`.
- Typed `WireError` decoded from the error envelope, plus a distinct
  `WireConnectionError` for network/timeout failures.
- Webhook signature verification (HMAC-SHA256, constant-time, fails closed).
