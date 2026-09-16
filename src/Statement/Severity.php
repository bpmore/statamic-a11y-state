<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

/**
 * How bad a known exception is. The four words are axe-core's impact scale,
 * which is what Accessibility Report already uses, so an exception that
 * arrives from there keeps its severity without translation.
 */
enum Severity: string
{
    case Critical = 'critical';

    case Serious = 'serious';

    case Moderate = 'moderate';

    case Minor = 'minor';

    public function rank(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::Serious => 3,
            self::Moderate => 2,
            self::Minor => 1,
        };
    }

    public function atLeast(self $other): bool
    {
        return $this->rank() >= $other->rank();
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public static function tryFromLoose(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        return is_string($value) ? self::tryFrom(strtolower(trim($value))) : null;
    }
}
