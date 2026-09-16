<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Evidence;

/**
 * The log in an array. For tests of everything that reads a log, and for
 * a driver author to copy: the two refusals in `append` are the contract.
 */
final class InMemoryEvidenceLog implements EvidenceLog
{
    /** @var array<string, Record> */
    private array $records = [];

    public function append(Record $record): void
    {
        if (isset($this->records[$record->id])) {
            throw new RecordExists($record->id);
        }

        if ($record->supersedes !== null && ! isset($this->records[$record->supersedes])) {
            throw new UnknownPredecessor($record->id, $record->supersedes);
        }

        $this->records[$record->id] = $record;
    }

    public function find(string $id): ?Record
    {
        return $this->records[$id] ?? null;
    }

    public function all(): array
    {
        return Ordering::chronological(array_values($this->records));
    }

    public function filter(Filter $filter): array
    {
        return Ordering::apply($this->all(), $filter);
    }

    public function count(): int
    {
        return count($this->records);
    }
}
