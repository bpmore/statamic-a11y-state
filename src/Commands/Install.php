<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Commands;

use Bpmore\A11yState\Database\EvidenceDatabase;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

/**
 * `php please a11y:state:install`. Only needed for the Eloquent evidence
 * driver; the YAML driver, which is the default, needs nothing.
 */
class Install extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:a11y:state:install';

    protected $description = 'Create the table the evidence log uses when storage.evidence_driver is eloquent.';

    public function handle(): int
    {
        foreach (EvidenceDatabase::install() as $line) {
            $this->line('  '.$line);
        }

        return self::SUCCESS;
    }
}
