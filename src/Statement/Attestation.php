<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

use InvalidArgumentException;

/**
 * A named person saying, on a date, that the site fully conforms, and on
 * what basis. The only thing that lets a statement say "fully conformant".
 *
 * Not a select option and not a checkbox. The name and the date are
 * required, the basis is what a buyer's auditor will ask for first, and the
 * evidence record is where the attestation was written down when it was
 * made, so the statement's claim and the log's history agree.
 */
final class Attestation
{
    public function __construct(
        public readonly string $name,
        public readonly string $date,
        public readonly string $basis,
        public readonly ?string $recordId = null,
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('An attestation needs the name of the person attesting.');
        }

        if (! Dates::isDate($date)) {
            throw new InvalidArgumentException("An attestation needs a date; [{$date}] is not one.");
        }

        if (trim($basis) === '') {
            throw new InvalidArgumentException('An attestation needs a basis: what was evaluated, how, and by whom.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) ($data['name'] ?? '')),
            date: trim((string) ($data['date'] ?? '')),
            basis: trim((string) ($data['basis'] ?? '')),
            recordId: is_string($data['record_id'] ?? null) && $data['record_id'] !== '' ? $data['record_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'date' => $this->date,
            'basis' => $this->basis,
            'record_id' => $this->recordId,
        ];
    }
}
