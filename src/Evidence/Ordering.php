<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Evidence;

/**
 * Shared by every driver: the order records come back in, and which of
 * them a later record has corrected.
 */
final class Ordering
{
    /**
     * Oldest first, by timestamp then id, so two records at the same second
     * still come back in one order every time.
     *
     * @param  list<Record>  $records
     * @return list<Record>
     */
    public static function chronological(array $records): array
    {
        usort($records, fn (Record $a, Record $b) => [$a->at, $a->id] <=> [$b->at, $b->id]);

        return array_values($records);
    }

    /**
     * @param  list<Record>  $records
     * @return array<string, true> the ids some later record supersedes
     */
    public static function superseded(array $records): array
    {
        $superseded = [];

        foreach ($records as $record) {
            if ($record->supersedes !== null) {
                $superseded[$record->supersedes] = true;
            }
        }

        return $superseded;
    }

    /**
     * @param  list<Record>  $records  already chronological
     * @return list<Record>
     */
    public static function apply(array $records, Filter $filter): array
    {
        $superseded = self::superseded($records);

        return array_values(array_filter($records, fn (Record $r) => $filter->accepts($r, $superseded)));
    }
}
