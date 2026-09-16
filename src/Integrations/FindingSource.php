<?php

declare(strict_types=1);

namespace Bpmore\Grange\Integrations;

use Bpmore\Grange\Evidence\Record;
use Bpmore\Grange\Statement\KnownException;
use DateTimeImmutable;

/**
 * Where findings come from when something else on the site is testing it.
 *
 * Accessibility Report, Accessibility Gate and Accessibility Docs may be
 * installed; none of them has to be. Each adapter answers two questions:
 * what is open now, which joins the known exceptions, and what was fixed
 * since a moment, which becomes evidence. With nothing installed the null
 * source answers nothing to both and every feature still works with
 * exceptions entered by hand.
 */
interface FindingSource
{
    /** A short name for the log and the control panel: which addon this is. */
    public function name(): string;

    /** Whether the addon behind this source is actually installed and readable. */
    public function isAvailable(): bool;

    /**
     * @return list<KnownException>
     */
    public function openFindings(?string $site): array;

    /**
     * Records for problems fixed since the moment, not yet appended to any
     * log. The caller appends them and skips ids already written.
     *
     * @return list<Record>
     */
    public function fixesSince(DateTimeImmutable $since, ?string $site): array;
}
