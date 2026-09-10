<?php

declare(strict_types=1);

namespace OrderFlowSample;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/** Offline demonstration. No production gateway or WooCommerce adapter is included. */
interface Gateway
{
    /** Returns a provider receipt; must throw UnknownOutcome if acceptance is uncertain. */
    public function submit(string $reference, string $body): string;

    /** Returns an accepted receipt, null if unresolved. Never guesses absence means failure. */
    public function lookup(string $reference): ?string;
}

/** Use only when the provider contract proves the request was not accepted. */
class DefinitelyNotAccepted extends RuntimeException {}
class PermanentlyRejected extends RuntimeException {}
class UnknownOutcome extends RuntimeException {}

final class OrderFlow
{
    private PDO $db;

    public function __construct(string $database, private Gateway $gateway)
    {
        $this->db = new PDO('sqlite:' . $database, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec('PRAGMA busy_timeout = 5000');
        $this->db->exec('CREATE TABLE IF NOT EXISTS jobs (
            reference TEXT PRIMARY KEY, body TEXT NOT NULL, status TEXT NOT NULL,
            attempts INTEGER NOT NULL DEFAULT 0, next_attempt INTEGER NOT NULL DEFAULT 0,
            receipt TEXT, reason TEXT
        )');
    }

    /** The real adapter must establish payment from trusted WooCommerce order data. */
    public function enqueue(string $reference, array $payload, bool $paid): string
    {
        if (!$paid) {
            throw new InvalidArgumentException('An unpaid order cannot be queued.');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{1,100}$/D', $reference)) {
            throw new InvalidArgumentException('Invalid merchant order reference.');
        }
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $statement = $this->db->prepare('INSERT OR IGNORE INTO jobs (reference, body, status) VALUES (?, ?, ?)');
        $statement->execute([$reference, $body, 'queued']);
        $job = $this->get($reference);
        if (!hash_equals($job['body'], $body)) {
            throw new InvalidArgumentException('An existing reference cannot be reused for a changed payload.');
        }
        return $job['status'];
    }

    /** Durable compare-and-set prevents two workers submitting the same queued job. */
    public function run(string $reference, int $now): string
    {
        $claim = $this->db->prepare("UPDATE jobs SET status = 'in_flight', attempts = attempts + 1
            WHERE reference = ? AND status IN ('queued', 'retry') AND next_attempt <= ?");
        $claim->execute([$reference, $now]);
        if ($claim->rowCount() === 0) {
            return $this->get($reference)['status'];
        }
        $job = $this->get($reference);
        // No DB transaction is held during remote I/O. A process crash leaves in_flight
        // for reconciliation, never an automatic repeat of an uncertain recharge.
        try {
            $receipt = $this->gateway->submit($reference, $job['body']);
            if ($receipt === '') {
                throw new UnknownOutcome('Missing provider receipt.');
            }
        } catch (DefinitelyNotAccepted $exception) {
            if ((int) $job['attempts'] >= 3) {
                $this->finish($reference, 'failed', null, 'retry_limit');
            } else {
                $retry = $this->db->prepare("UPDATE jobs SET status = 'retry', next_attempt = ?, reason = 'not_accepted'
                    WHERE reference = ? AND status = 'in_flight'");
                $retry->execute([$now + 30 * (2 ** ((int) $job['attempts'] - 1)), $reference]);
            }
            return $this->get($reference)['status'];
        } catch (PermanentlyRejected $exception) {
            $this->finish($reference, 'failed', null, 'rejected');
            return $this->get($reference)['status'];
        } catch (Throwable $exception) {
            // Includes timeouts and unexpected transport failures. Exception text may
            // contain secrets and is deliberately excluded from the durable ledger.
            $this->finish($reference, 'uncertain', null, 'reconcile_required');
            return $this->get($reference)['status'];
        }
        // Keep persistence outside the transport catch: a DB failure after remote
        // acceptance leaves in_flight, preserving the requirement to reconcile.
        $this->finish($reference, 'succeeded', $receipt, null);
        return $this->get($reference)['status'];
    }

    public function reconcile(string $reference): string
    {
        $job = $this->get($reference);
        if (!in_array($job['status'], ['in_flight', 'uncertain'], true)) {
            return $job['status'];
        }
        $receipt = $this->gateway->lookup($reference);
        if ($receipt !== null && $receipt !== '') {
            $this->finish($reference, 'succeeded', $receipt, null);
        }
        return $this->get($reference)['status'];
    }

    public function get(string $reference): array
    {
        $query = $this->db->prepare('SELECT * FROM jobs WHERE reference = ?');
        $query->execute([$reference]);
        $job = $query->fetch(PDO::FETCH_ASSOC);
        if ($job === false) {
            throw new InvalidArgumentException('Unknown order reference.');
        }
        return $job;
    }

    private function finish(string $reference, string $status, ?string $receipt, ?string $reason): void
    {
        $query = $this->db->prepare("UPDATE jobs SET status = ?, receipt = ?, reason = ?
            WHERE reference = ? AND status IN ('in_flight', 'uncertain')");
        $query->execute([$status, $receipt, $reason, $reference]);
    }
}

/** Illustrates signing exact bytes; BIGO's algorithm/canonicalization must come from its supplied docs. */
function signExactBody(string $body, \OpenSSLAsymmetricKey $privateKey): string
{
    if (!openssl_sign($body, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Signing failed.');
    }
    return base64_encode($signature);
}
