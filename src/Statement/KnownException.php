<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

use InvalidArgumentException;

/**
 * One thing the site knows is not accessible: what, where, how bad, why,
 * and when it will be fixed. The W3C statement calls these limitations and
 * the EU model calls them non-accessible content; every profile lists them.
 *
 * A value object. Changing one means making a new one, which is what keeps
 * the list on a statement honest about when it changed.
 */
final class KnownException
{
    public const OPEN = 'open';

    public const RESOLVED = 'resolved';

    public const STATUSES = [self::OPEN, self::RESOLVED];

    /** Entered by hand in the control panel, as opposed to fed by an integration. */
    public const SOURCE_MANUAL = 'manual';

    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly Severity $severity,
        public readonly string $status = self::OPEN,
        public readonly ?string $criterion = null,
        public readonly ?string $page = null,
        public readonly ?string $reason = null,
        public readonly ?string $remediationDate = null,
        public readonly string $source = self::SOURCE_MANUAL,
    ) {
        if (trim($id) === '') {
            throw new InvalidArgumentException('A known exception needs an id.');
        }

        if (trim($description) === '') {
            throw new InvalidArgumentException("Known exception [{$id}] needs a description.");
        }

        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Known exception [{$id}] has an unknown status [{$status}].");
        }

        if ($remediationDate !== null && ! Dates::isDate($remediationDate)) {
            throw new InvalidArgumentException("Known exception [{$id}] has a remediation date that is not a date: [{$remediationDate}].");
        }
    }

    public function isOpen(): bool
    {
        return $this->status === self::OPEN;
    }

    /** A short line naming this exception, for a refusal or a log line. */
    public function summary(): string
    {
        $where = $this->page !== null ? " on {$this->page}" : '';
        $what = $this->criterion !== null ? " ({$this->criterion})" : '';

        return "{$this->severity->label()}: {$this->description}{$what}{$where}";
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $severity = Severity::tryFromLoose($data['severity'] ?? null);

        if ($severity === null) {
            throw new InvalidArgumentException('Known exception ['.(string) ($data['id'] ?? '?').'] has an unknown severity ['.(string) ($data['severity'] ?? '').'].');
        }

        return new self(
            id: (string) ($data['id'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            severity: $severity,
            status: (string) ($data['status'] ?? self::OPEN),
            criterion: self::nullable($data['criterion'] ?? null),
            page: self::nullable($data['page'] ?? null),
            reason: self::nullable($data['reason'] ?? null),
            remediationDate: self::nullable($data['remediation_date'] ?? null),
            source: (string) ($data['source'] ?? self::SOURCE_MANUAL),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'severity' => $this->severity->value,
            'status' => $this->status,
            'criterion' => $this->criterion,
            'page' => $this->page,
            'reason' => $this->reason,
            'remediation_date' => $this->remediationDate,
            'source' => $this->source,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
