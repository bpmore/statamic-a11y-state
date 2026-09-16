<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Integrations;

use Bpmore\A11yState\Evidence\Record;
use Bpmore\A11yState\Statement\KnownException;
use DateTimeImmutable;

/**
 * Where findings come from when something else on the site is testing it.
 *
 * A11y Report, A11y Gate and A11y Docs may be
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
