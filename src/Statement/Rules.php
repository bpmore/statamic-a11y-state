<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

use InvalidArgumentException;

/**
 * The two numbers the config file sets that decide what a statement may
 * claim: how bad an open exception has to be to refuse "fully conformant",
 * and how old an assessment may be before the statement is stale.
 */
final class Rules
{
    public function __construct(
        public readonly Severity $overclaimSeverity,
        public readonly int $staleAfterDays,
    ) {
        if ($staleAfterDays < 1) {
            throw new InvalidArgumentException('stale_after_days must be at least 1.');
        }
    }

    /**
     * @param  array<string, mixed>  $config  the addon's config block
     */
    public static function fromConfig(array $config): self
    {
        $severity = Severity::tryFromLoose($config['overclaim_severity'] ?? 'moderate');

        if ($severity === null) {
            throw new InvalidArgumentException('overclaim_severity must be one of critical, serious, moderate, minor; got ['.(string) ($config['overclaim_severity'] ?? '').'].');
        }

        return new self($severity, (int) ($config['stale_after_days'] ?? 365));
    }
}
