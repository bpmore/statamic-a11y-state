<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

use DateTimeImmutable;

/**
 * Whether the assessment a statement rests on is older than the window.
 *
 * The date read is the `assessment_date` disclosure: when the site was
 * last evaluated, which is the date the claim is only as good as. A
 * statement with no assessment date at all is not stale, it is missing a
 * disclosure, and the profile check says so if the profile requires one.
 */
final class Staleness
{
    public const KEY = 'assessment_date';

    public static function check(Statement $statement, int $staleAfterDays, DateTimeImmutable $now): ?Problem
    {
        $age = self::ageInDays($statement, $now);

        if ($age === null || $age <= $staleAfterDays) {
            return null;
        }

        return Problem::warning(
            Problem::STALE,
            sprintf(
                'The assessment is %d days old (dated %s), older than the %d-day window. The published statement is unchanged; re-assess and update the date.',
                $age,
                (string) $statement->disclosure(self::KEY),
                $staleAfterDays,
            ),
            self::KEY,
            ['age_days' => $age, 'window_days' => $staleAfterDays],
        );
    }

    public static function ageInDays(Statement $statement, DateTimeImmutable $now): ?int
    {
        $value = $statement->disclosure(self::KEY);
        $date = is_string($value) ? Dates::parse($value) : null;

        return $date === null ? null : Dates::daysBetween($date, $now);
    }
}
