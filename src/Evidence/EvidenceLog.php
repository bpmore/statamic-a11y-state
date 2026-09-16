<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Evidence;

/**
 * The evidence log. Append and read. There is no update and no delete on
 * this interface and there must never be one: a driver that offered either
 * would turn the log back into a content field.
 *
 * A correction is a record whose `supersedes` names the record it
 * corrects. Both stay readable; `superseded()` says which records have
 * been corrected and by what.
 */
interface EvidenceLog
{
    /**
     * @throws RecordExists when a record with this id is already written
     * @throws UnknownPredecessor when the record supersedes an id that is not in the log
     */
    public function append(Record $record): void;

    public function find(string $id): ?Record;

    /**
     * Every record, oldest first.
     *
     * @return list<Record>
     */
    public function all(): array;

    /**
     * @return list<Record>
     */
    public function filter(Filter $filter): array;

    public function count(): int;
}
