<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Dates as strings, because that is how they live in YAML and in the
 * document. Everything is UTC and ISO 8601: a date is `YYYY-MM-DD`, a moment
 * is `YYYY-MM-DDTHH:MM:SS+00:00`. No framework date library, so the core
 * stays framework-free.
 */
final class Dates
{
    public static function isDate(string $value): bool
    {
        return self::parse($value) !== null;
    }

    public static function parse(?string $value): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        // Strict on the calendar date, which is what people type. A moment
        // with a time on it is accepted as PHP reads it.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));

            return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
        }

        try {
            return new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public static function moment(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:sP');
    }

    public static function day(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d');
    }

    /** Whole days from $from to $to; negative when $to is earlier. */
    public static function daysBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $seconds = $to->getTimestamp() - $from->getTimestamp();

        return (int) floor($seconds / 86400);
    }
}
