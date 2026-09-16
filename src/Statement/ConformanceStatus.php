<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

/**
 * The claim a statement makes, in the W3C's words.
 *
 * Four values and not three. The brief names non-conformant, partially
 * conformant and fully conformant; the W3C generator this addon follows
 * offers a fourth, "not assessed: the content has not been evaluated or the
 * evaluation results are not available", and it is the only honest value a
 * new statement can carry. A statement that must choose one of the three on
 * day one is a statement that guesses on day one. Confirmed by the owner
 * on 2026-09-15; docs/profiles.md has the source.
 */
enum ConformanceStatus: string
{
    case NotAssessed = 'not-assessed';

    case NonConformant = 'non-conformant';

    case PartiallyConformant = 'partially-conformant';

    case FullyConformant = 'fully-conformant';

    public function label(): string
    {
        return match ($this) {
            self::NotAssessed => 'Not assessed',
            self::NonConformant => 'Non-conformant',
            self::PartiallyConformant => 'Partially conformant',
            self::FullyConformant => 'Fully conformant',
        };
    }

    /** The W3C generator's own definition, printed beside the choice. */
    public function definition(): string
    {
        return match ($this) {
            self::NotAssessed => 'The content has not been evaluated or the evaluation results are not available.',
            self::NonConformant => 'The content does not conform to the accessibility standard.',
            self::PartiallyConformant => 'Some parts of the content do not fully conform to the accessibility standard.',
            self::FullyConformant => 'The content fully conforms to the accessibility standard without any exceptions.',
        };
    }

    /**
     * Whether this is a claim of conformance at all. "Not assessed" claims
     * nothing, and a profile that requires exceptions to be listed does not
     * require them of a statement that has not looked yet.
     */
    public function isClaim(): bool
    {
        return $this !== self::NotAssessed;
    }

    public static function tryFromLoose(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        return is_string($value) ? self::tryFrom(strtolower(trim($value))) : null;
    }
}
