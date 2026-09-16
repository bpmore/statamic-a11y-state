<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Integrations;

use DateTimeImmutable;

/**
 * Every source that is available, asked in turn. Sources that are not
 * available are skipped rather than asked, so an adapter for an addon that
 * is not installed is never a code path that runs.
 */
final class FindingSources implements FindingSource
{
    /** @var list<FindingSource> */
    private array $sources;

    /**
     * @param  list<FindingSource>  $sources
     */
    public function __construct(array $sources)
    {
        $this->sources = array_values($sources);
    }

    public function name(): string
    {
        $names = array_map(fn (FindingSource $s) => $s->name(), $this->available());

        return $names === [] ? 'none' : implode(', ', $names);
    }

    public function isAvailable(): bool
    {
        return $this->available() !== [];
    }

    public function openFindings(?string $site): array
    {
        $all = [];

        foreach ($this->available() as $source) {
            foreach ($source->openFindings($site) as $finding) {
                $all[] = $finding;
            }
        }

        return $all;
    }

    public function fixesSince(DateTimeImmutable $since, ?string $site): array
    {
        $all = [];

        foreach ($this->available() as $source) {
            foreach ($source->fixesSince($since, $site) as $record) {
                $all[] = $record;
            }
        }

        return $all;
    }

    /**
     * @return list<FindingSource>
     */
    public function available(): array
    {
        return array_values(array_filter($this->sources, fn (FindingSource $s) => $s->isAvailable()));
    }
}
