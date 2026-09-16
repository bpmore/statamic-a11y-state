<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Evidence;

use Bpmore\A11yState\Statement\Dates;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * One entry in the evidence log: when, who, which success criterion, which
 * page or asset, what was done, and any notes.
 *
 * Readonly all the way down. There is no setter, no `with`, and no way to
 * make a record from another except by writing a new one that names the
 * old one in `supersedes`. A correction is a new record; the record it
 * corrects stays. That is the whole difference between evidence and a
 * content field.
 */
final class Record
{
    /**
     * @param  array<string, mixed>  $meta  anything an integration wants to keep, such as the id of the finding it came from
     */
    public function __construct(
        public readonly string $id,
        public readonly string $at,
        public readonly string $actor,
        public readonly string $action,
        public readonly ?string $criterion = null,
        public readonly ?string $subject = null,
        public readonly string $notes = '',
        public readonly ?string $supersedes = null,
        public readonly array $meta = [],
    ) {
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $id)) {
            throw new InvalidArgumentException("Record id [{$id}] is not a safe id: letters, digits, dots, dashes and underscores only.");
        }

        if (Dates::parse($at) === null) {
            throw new InvalidArgumentException("Record [{$id}] has a timestamp that is not a moment: [{$at}].");
        }

        if (trim($actor) === '') {
            throw new InvalidArgumentException("Record [{$id}] names no actor.");
        }

        if (! Actions::isKnown($action)) {
            throw new InvalidArgumentException("Record [{$id}] has an unknown action [{$action}]. Known: ".implode(', ', Actions::ALL).'.');
        }

        if ($supersedes !== null && $supersedes === $id) {
            throw new InvalidArgumentException("Record [{$id}] cannot supersede itself.");
        }
    }

    /**
     * A new id: sortable by time to the millisecond, then random. The time
     * prefix is what keeps a directory listing, and two records written in
     * the same second, in the order they were written; the random tail is
     * what keeps two written in the same millisecond apart.
     */
    public static function newId(?DateTimeImmutable $at = null): string
    {
        $at ??= Dates::now();

        return $at->format('Ymd\THisv').'-'.bin2hex(random_bytes(4));
    }

    public function timestamp(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $parsed validated in the constructor */
        $parsed = Dates::parse($this->at);

        return $parsed;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            at: (string) ($data['at'] ?? ''),
            actor: (string) ($data['actor'] ?? ''),
            action: (string) ($data['action'] ?? ''),
            criterion: self::nullable($data['criterion'] ?? null),
            subject: self::nullable($data['subject'] ?? null),
            notes: (string) ($data['notes'] ?? ''),
            supersedes: self::nullable($data['supersedes'] ?? null),
            meta: is_array($data['meta'] ?? null) ? $data['meta'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'at' => $this->at,
            'actor' => $this->actor,
            'action' => $this->action,
            'criterion' => $this->criterion,
            'subject' => $this->subject,
            'notes' => $this->notes,
            'supersedes' => $this->supersedes,
            'meta' => $this->meta,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
