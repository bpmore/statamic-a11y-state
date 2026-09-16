<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Evidence;

use RuntimeException;

/**
 * The log, or a filtered view of it, as a file somebody can hand over.
 *
 * CSV for the person with a spreadsheet, JSON for the person with a
 * script. Both carry every column of every record, the superseded ones
 * included, with a column saying which later record corrected each. An
 * export that quietly left out corrected records would be an export of a
 * different log.
 */
final class Export
{
    public const FORMATS = ['csv', 'json'];

    public const COLUMNS = ['id', 'at', 'actor', 'action', 'criterion', 'subject', 'notes', 'supersedes', 'superseded_by', 'meta'];

    /**
     * @param  list<Record>  $records
     */
    public static function render(string $format, array $records): string
    {
        return match ($format) {
            'csv' => self::csv($records),
            'json' => self::json($records),
            default => throw new RuntimeException("Unknown export format [{$format}]. Use one of ".implode(', ', self::FORMATS).'.'),
        };
    }

    public static function contentType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv; charset=UTF-8',
            'json' => 'application/json',
            default => throw new RuntimeException("Unknown export format [{$format}]."),
        };
    }

    /**
     * @param  list<Record>  $records
     */
    public static function csv(array $records): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Could not open a buffer for the CSV export.');
        }

        // A byte order mark so a spreadsheet opens accented text correctly.
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::COLUMNS, ',', '"', '');

        foreach (self::rows($records) as $row) {
            fputcsv($handle, array_map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string) ($v ?? ''), $row), ',', '"', '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @param  list<Record>  $records
     */
    public static function json(array $records): string
    {
        return json_encode([
            'records' => self::rows($records),
            'count' => count($records),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * @param  list<Record>  $records
     * @return list<array<string, mixed>>
     */
    private static function rows(array $records): array
    {
        $by = [];

        foreach ($records as $record) {
            if ($record->supersedes !== null) {
                $by[$record->supersedes] = $record->id;
            }
        }

        return array_map(function (Record $r) use ($by) {
            $row = $r->toArray();
            $row['superseded_by'] = $by[$r->id] ?? null;

            $ordered = [];

            foreach (self::COLUMNS as $column) {
                $ordered[$column] = $row[$column] ?? null;
            }

            return $ordered;
        }, $records);
    }
}
