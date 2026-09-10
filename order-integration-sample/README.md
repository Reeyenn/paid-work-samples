# Offline PHP order-integration example

Prepared by an AI coding assistant for Reeyen Patel as a fresh technical work sample, September 10, 2026.

This example demonstrates a durable order-delivery state machine with a fake provider. It is runnable PHP, with 26 executed checks. It does not connect to BIGO, WordPress, WooCommerce, Stripe, or any live financial system. No customer data, production credentials, or saved private keys are included.

## Run

Requires PHP 8.1+ with PDO SQLite and OpenSSL. Tested locally on PHP 8.5.10.

```sh
php -l OrderFlow.php
php -l test.php
php test.php
```

Expected last line: `26 checks passed; zero network requests; all orders and receipts are fake.`

## What the tests demonstrate

- Payment status must be established before enqueueing; the sample supplies a fake boolean.
- A persistent unique merchant reference prevents duplicate queue entries.
- A compare-and-set update allows one worker to claim a queued job; a second connection attempting work during submission cannot submit again.
- Reopening the ledger preserves completed orders.
- Reusing a merchant reference with changed serialized payload bytes is rejected.
- Confirmed non-acceptance receives at most three attempts with 30/60-second backoff.
- Permanent rejection stops immediately.
- Timeouts, missing receipts and unexpected failures require reconciliation.
- An in-flight job left by a crashed worker is not automatically recharged.
- Provider lookup can recover a previously accepted receipt without sending another order.
- Transport exception text is excluded from the ledger because it may contain secrets.
- An ephemeral RSA key signs exact request bytes; modifying those bytes breaks verification.

The overlapping-worker test uses separate SQLite connections in one process with a controlled interleaving. This is not a distributed-system load test or proof of exactly-once delivery across arbitrary provider failures.

## Production integration work still required

1. Inspect the supplied BIGO API contract: algorithm, padding, canonical byte format, required fields, timestamp rules, request identity and response semantics. The RSA-SHA256 demonstration is not a claim about BIGO's required signing format.
2. Establish payment from trusted WooCommerce order data. Reject cancelled or refunded orders before dispatch, and scope work to eligible line items and remaining quantities. An input boolean is not a production payment verification mechanism.
3. Use WooCommerce-supported order access and an Action Scheduler job after its initialization. A unique scheduled action alone does not replace durable dispatch state.
4. Replace the sample SQLite store with a site-appropriate transactional store, migrations, retention policy and recovery tooling. Keep payloads minimal and protect access to them.
5. Reconcile timeouts using the provider's official order-query endpoint. If it has no reliable lookup or idempotency contract, ambiguous deliveries need manual review. A "not found" lookup may be eventually consistent and is not sufficient evidence to recharge.
6. Confirm static outbound IP from the actual PHP/web worker environment, and use the provider's documented non-charge test endpoint or sandbox.
7. Test with a staging WooCommerce installation, the actual plugin versions, HPOS settings, supported PHP version, real scheduler behavior and the provider sandbox. These tests have not been run.
8. Store real signing material outside committed code; use redacted logs. Get separate owner approval for live deployment and any transaction-bearing tests.

## Sources for the proposed adapter

- [WooCommerce Payment Gateway API](https://developer.woocommerce.com/docs/features/payments/payment-gateway-api)
- [Action Scheduler API](https://actionscheduler.org/api/)

## License

MIT License. Copyright (c) 2026 Reeyen Patel.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
