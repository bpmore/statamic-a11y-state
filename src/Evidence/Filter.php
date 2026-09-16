<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Evidence;

use Bpmore\A11yState\Statement\Dates;

/**
 * What the viewer and the export can narrow the log by. A filter is a view
 * of the log and nothing more: a superseded record is shown unless the
 * viewer asks to hide corrected records, and even then it is still there.
 */
final class Filter
{
    public function __construct(
        public readonly ?string $action = null,
        public readonly ?string $criterion = null,
        public readonly ?string $actor = null,
        public readonly ?string $subject = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly bool $hideSuperseded = false,
    ) {}

    /**
     * @param  array<string, mixed>  $query  as from a request
     */
    public static function fromArray(array $query): self
    {
        $s = fn (string $k) => is_string($query[$k] ?? null) && trim($query[$k]) !== '' ? trim($query[$k]) : null;

        return new self(
            action: $s('action'),
            criterion: $s('criterion'),
            actor: $s('actor'),
            subject: $s('subject'),
            from: Dates::isDate($s('from') ?? '') ? $s('from') : null,
            to: Dates::isDate($s('to') ?? '') ? $s('to') : null,
            hideSuperseded: filter_var($query['hide_superseded'] ?? false, FILTER_VALIDATE_BOOL),
        );
    }

    public function isEmpty(): bool
    {
        return $this->action === null && $this->criterion === null && $this->actor === null
            && $this->subject === null && $this->from === null && $this->to === null && ! $this->hideSuperseded;
    }

    /**
     * @param  array<string, true>  $superseded  ids that a later record corrects
     */
    public function accepts(Record $record, array $superseded): bool
    {
        if ($this->action !== null && $record->action !== $this->action) {
            return false;
        }

        if ($this->criterion !== null && $record->criterion !== $this->criterion) {
            return false;
        }

        if ($this->actor !== null && stripos($record->actor, $this->actor) === false) {
            return false;
        }

        if ($this->subject !== null && ($record->subject === null || stripos($record->subject, $this->subject) === false)) {
            return false;
        }

        $day = substr($record->at, 0, 10);

        if ($this->from !== null && $day < $this->from) {
            return false;
        }

        if ($this->to !== null && $day > $this->to) {
            return false;
        }

        if ($this->hideSuperseded && isset($superseded[$record->id])) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'action' => $this->action,
            'criterion' => $this->criterion,
            'actor' => $this->actor,
            'subject' => $this->subject,
            'from' => $this->from,
            'to' => $this->to,
            'hide_superseded' => $this->hideSuperseded ? '1' : null,
        ], fn ($v) => $v !== null);
    }
}
