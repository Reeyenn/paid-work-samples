<?php

declare(strict_types=1);

require __DIR__ . '/OrderFlow.php';

use OrderFlowSample\Gateway;
use OrderFlowSample\OrderFlow;
use OrderFlowSample\DefinitelyNotAccepted;
use OrderFlowSample\PermanentlyRejected;
use OrderFlowSample\UnknownOutcome;
use function OrderFlowSample\signExactBody;

final class FakeGateway implements Gateway
{
    public int $calls = 0;
    public array $accepted = [];
    public string $mode = 'success';
    public ?Closure $duringSubmit = null;

    public function submit(string $reference, string $body): string
    {
        $this->calls++;
        if ($this->duringSubmit) {
            ($this->duringSubmit)();
        }
        if ($this->mode === 'retry') {
            throw new DefinitelyNotAccepted('Provider test response explicitly says not accepted.');
        }
        if ($this->mode === 'reject') {
            throw new PermanentlyRejected('Test-only invalid product.');
        }
        if ($this->mode === 'unexpected') {
            throw new RuntimeException('sensitive-value-must-not-be-logged');
        }
        if ($this->mode === 'empty') {
            return '';
        }
        $receipt = 'fake-receipt-' . $reference;
        $this->accepted[$reference] = $receipt;
        if ($this->mode === 'timeout_after_accept') {
            throw new UnknownOutcome('Fake timeout after provider acceptance.');
        }
        return $receipt;
    }

    public function lookup(string $reference): ?string
    {
        return $this->accepted[$reference] ?? null;
    }
}

$tests = 0;
function check(bool $condition, string $label): void
{
    global $tests;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $tests++;
    echo 'PASS: ', $label, PHP_EOL;
}
function expectInvalid(Closure $action, string $label): void
{
    try {
        $action();
    } catch (InvalidArgumentException) {
        check(true, $label);
        return;
    }
    throw new RuntimeException('FAIL: ' . $label);
}

$database = tempnam(sys_get_temp_dir(), 'orderflow-demo-');
try {
    $gateway = new FakeGateway();
    $flow = new OrderFlow($database, $gateway);
    $payload = ['product' => 'FAKE_DEMO_ONLY', 'quantity' => 1];
    expectInvalid(fn() => $flow->enqueue('unpaid', $payload, false), 'unpaid order is refused');
    expectInvalid(fn() => $flow->enqueue('../invalid', $payload, true), 'invalid reference is refused');
    check($gateway->calls === 0, 'validation does not invoke the gateway');
    $flow->enqueue('order_1', $payload, true);
    $flow->enqueue('order_1', $payload, true);
    check($flow->run('order_1', 100) === 'succeeded', 'accepted result records success');
    $flow->run('order_1', 100);
    check($gateway->calls === 1, 'duplicate payment events and workers submit only once');
    expectInvalid(fn() => $flow->enqueue('order_1', ['quantity' => 2], true), 'changed payload cannot reuse an order reference');
    $reopened = new OrderFlow($database, $gateway);
    check($reopened->run('order_1', 200) === 'succeeded' && $gateway->calls === 1, 'restart preserves the completed state');

    $secondWorker = new OrderFlow($database, $gateway);
    $gateway->duringSubmit = function () use ($secondWorker): void {
        check($secondWorker->run('concurrent', 100) === 'in_flight', 'overlapping independent DB connection cannot acquire claimed job');
    };
    $flow->enqueue('concurrent', $payload, true);
    $before = $gateway->calls;
    $flow->run('concurrent', 100);
    check($gateway->calls === $before + 1, 'overlapping workers produce one provider submission');
    $gateway->duringSubmit = null;

    $gateway->mode = 'timeout_after_accept';
    $flow->enqueue('timeout', $payload, true);
    check($flow->run('timeout', 100) === 'uncertain', 'timeout after acceptance is not treated as a definite failure');
    $before = $gateway->calls;
    $flow->run('timeout', 10000);
    check($gateway->calls === $before, 'uncertain order is never blindly retried');
    check($flow->reconcile('timeout') === 'succeeded', 'provider lookup recovers the accepted receipt');
    check($gateway->calls === $before, 'reconciliation does not submit a second recharge');

    $gateway->mode = 'unexpected';
    $flow->enqueue('unresolved', $payload, true);
    $flow->run('unresolved', 100);
    check($flow->reconcile('unresolved') === 'uncertain', 'missing lookup result leaves the order unresolved');
    check(!str_contains(json_encode($flow->get('unresolved')), 'sensitive-value'), 'transport exception details are excluded from the ledger');

    $gateway->mode = 'retry';
    $flow->enqueue('backoff', $payload, true);
    check($flow->run('backoff', 100) === 'retry', 'proven non-acceptance schedules a retry');
    $before = $gateway->calls;
    $flow->run('backoff', 129);
    check($gateway->calls === $before, 'worker respects first retry deadline');
    $flow->run('backoff', 130);
    check((int) $flow->get('backoff')['next_attempt'] === 190, 'backoff increases from 30 to 60 seconds');
    check($flow->run('backoff', 190) === 'failed', 'retry budget stops after three attempts');
    $before = $gateway->calls;
    $flow->run('backoff', 10000);
    check($gateway->calls === $before, 'exhausted jobs stay stopped');

    $gateway->mode = 'reject';
    $flow->enqueue('rejected', $payload, true);
    check($flow->run('rejected', 100) === 'failed', 'permanent rejection does not retry');
    $gateway->mode = 'empty';
    $flow->enqueue('empty_receipt', $payload, true);
    check($flow->run('empty_receipt', 100) === 'uncertain', 'empty success receipt requires reconciliation');

    // Simulate process death after durable claim, before the result can be persisted.
    $flow->enqueue('crashed', $payload, true);
    $databaseHandle = new PDO('sqlite:' . $database);
    $databaseHandle->exec("UPDATE jobs SET status = 'in_flight' WHERE reference = 'crashed'");
    $before = $gateway->calls;
    check($flow->run('crashed', 10000) === 'in_flight' && $gateway->calls === $before, 'crashed in-flight work cannot be resubmitted automatically');
    $gateway->accepted['crashed'] = 'fake-recovered-receipt';
    check($flow->reconcile('crashed') === 'succeeded', 'crashed worker can be reconciled by provider receipt');

    $privateKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    if ($privateKey === false) {
        throw new RuntimeException('Could not generate test-only ephemeral key.');
    }
    $publicKey = openssl_pkey_get_details($privateKey)['key'];
    $body = '{"product":"FAKE_DEMO_ONLY","quantity":1}';
    $signature = base64_decode(signExactBody($body, $privateKey), true);
    check(openssl_verify($body, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1, 'RSA signature verifies over the exact request bytes');
    check(openssl_verify($body . ' ', $signature, $publicKey, OPENSSL_ALGO_SHA256) === 0, 'changing serialized bytes invalidates the RSA signature');
    echo PHP_EOL, $tests, ' checks passed; zero network requests; all orders and receipts are fake.', PHP_EOL;
} finally {
    unset($databaseHandle, $secondWorker, $reopened, $flow);
    unlink($database);
}
