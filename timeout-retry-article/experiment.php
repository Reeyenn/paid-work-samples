<?php
declare(strict_types=1);
require __DIR__ . '/../order-integration-sample/OrderFlow.php';
use OrderFlowSample\Gateway;
use OrderFlowSample\OrderFlow;
use OrderFlowSample\UnknownOutcome;
final class ReceiptLost implements Gateway {
    public int $fulfilled = 0;
    private array $receipts = [];
    public function submit(string $reference,string $body): string {
        ++$this->fulfilled;
        $receipt = 'fake-receipt-'.$this->fulfilled;
        $this->receipts[$reference] = $receipt;
        if ($this->fulfilled === 1) { throw new UnknownOutcome('Simulated lost response'); }
        return $receipt;
    }
    public function lookup(string $reference): ?string {return $this->receipts[$reference] ?? null;}
}
$naive = new ReceiptLost();
for ($attempt = 0; $attempt < 2; ++$attempt) {
    try {$naive->submit('demo', '{"quantity":1}');break;} catch (UnknownOutcome) {}
}
$file=tempnam(sys_get_temp_dir(),'retry-article-');
try {
    $safe=new ReceiptLost();$flow=new OrderFlow($file,$safe);
    $flow->enqueue('demo',['quantity'=>1],true);
    $initial=$flow->run('demo',100);
    $afterScheduledRetry=$flow->run('demo',10000);
    $reconciled=$flow->reconcile('demo');
    if ($naive->fulfilled !== 2 || $safe->fulfilled !== 1 || $initial!=='uncertain' || $reconciled!=='succeeded') {throw new RuntimeException('Experiment invariant failed');}
    echo "Naive retry: {$naive->fulfilled} fake fulfillments\n";
    echo "Ledger flow: {$safe->fulfilled} fake fulfillment\n";
    echo "States: $initial -> $afterScheduledRetry -> $reconciled\n";
    echo "No network requests or real transactions.\n";
} finally {unset($flow);unlink($file);}
