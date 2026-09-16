<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Integrations;

use DateTimeImmutable;

/**
 * No other addon. Nothing open, nothing fixed. What A11y State runs on when it
 * is installed alone, which is a supported way to install it.
 */
final class NullFindingSource implements FindingSource
{
    public function name(): string
    {
        return 'none';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function openFindings(?string $site): array
    {
        return [];
    }

    public function fixesSince(DateTimeImmutable $since, ?string $site): array
    {
        return [];
    }
}
