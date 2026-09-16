<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Commands;

use Bpmore\A11yState\Evidence\Actions;
use Bpmore\A11yState\Evidence\Filter;
use Bpmore\A11yState\Feedback\FeedbackWindow;
use Bpmore\A11yState\Feedback\FormSubmissions;
use Bpmore\A11yState\Statement\Dates;
use Bpmore\A11yState\Statement\Problem;
use Bpmore\A11yState\Statements;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Site;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `php please a11y:state:check`. Reads every site's statement and says whether
 * it would generate, whether its assessment is stale, whether it claims
 * more than its exceptions allow, and whether feedback is waiting past
 * the window.
 *
 * Exit codes: 0 nothing in --fail-on was found, 1 something was, 2 the
 * input was wrong. `--json` is the whole of stdout with nothing else on
 * it, for a pipeline.
 */
class Check extends Command
{
    use RunsInPlease;

    public const CONDITIONS = ['incomplete', 'stale', 'overclaim', 'feedback'];

    public const EXIT_FOUND = 1;

    public const EXIT_INVALID = 2;

    protected $signature = 'statamic:a11y:state:check
        {--site=* : Site handle to check, repeatable, every site when omitted}
        {--json : Write the result as JSON to stdout and nothing else}
        {--fail-on= : Comma list of incomplete, stale, overclaim, feedback; exit 1 when any is found. All of them when omitted}
        {--no-import : Do not pull fixes from the integrations into the evidence log}';

    protected $description = 'Check that the accessibility statement is complete, current, and claims no more than its evidence allows.';

    public function handle(Statements $statements, FormSubmissions $submissions, FeedbackWindow $window): int
    {
        try {
            $failOn = $this->failOn();
            $sites = $this->sites();
        } catch (\InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::EXIT_INVALID;
        }

        // Fixes from the integrations become evidence here, on every run,
        // which is what makes a scheduled check keep the log current
        // without anybody pressing the button on the Evidence screen. Keyed
        // on the finding, so a second run writes nothing twice.
        $imported = 0;

        if (! $this->option('no-import') && $statements->findings()->isAvailable()) {
            foreach ($sites as $site) {
                $imported += $statements->importFixes($site, Dates::now()->modify('-365 days'));
            }
        }

        $acks = $statements->log()->filter(new Filter(action: Actions::FEEDBACK_ACKNOWLEDGED));
        $feedback = $window->report($submissions->all(), $acks, Dates::now());

        $result = [
            'checked_at' => Dates::moment(Dates::now()),
            'fail_on' => $failOn,
            'integrations' => $statements->findings()->name(),
            'imported_fixes' => $imported,
            'feedback' => [
                'form' => $submissions->handle(),
                'response_days' => $feedback->responseDays,
                'total' => count($feedback->rows),
                'awaiting' => count($feedback->awaiting()),
                'overdue' => count($feedback->overdue()),
            ],
            'sites' => [],
        ];

        foreach ($sites as $site) {
            $statement = $statements->for($site);
            $problems = $statements->problems($statement);
            $errors = array_values(array_filter($problems, fn (Problem $p) => $p->isError()));

            $result['sites'][] = [
                'site' => $site,
                'exists' => $statements->exists($site),
                'profile' => $statement->profile,
                'status' => $statement->status->value,
                'complete' => $errors === [],
                'stale' => (bool) array_filter($problems, fn (Problem $p) => $p->code === Problem::STALE),
                'overclaim' => (bool) array_filter($problems, fn (Problem $p) => $p->code === Problem::OVERCLAIM),
                'open_exceptions' => count($statement->openExceptions()) + count($statements->externalFindings($site)),
                'problems' => array_map(fn (Problem $p) => $p->toArray(), $problems),
            ];
        }

        $found = [];

        foreach ($result['sites'] as $row) {
            if (! $row['complete']) {
                $found['incomplete'] = true;
            }

            if ($row['stale']) {
                $found['stale'] = true;
            }

            if ($row['overclaim']) {
                $found['overclaim'] = true;
            }
        }

        if ($result['feedback']['overdue'] > 0) {
            $found['feedback'] = true;
        }

        $result['found'] = array_keys($found);
        $result['failed'] = array_intersect($failOn, array_keys($found)) !== [];

        if ($this->option('json')) {
            $this->output->write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n", false, OutputInterface::OUTPUT_RAW);
        } else {
            $this->human($result);
        }

        return $result['failed'] ? self::EXIT_FOUND : self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function sites(): array
    {
        $asked = array_values(array_map('strval', (array) $this->option('site')));

        if ($asked === []) {
            return Site::all()->map(fn ($s) => $s->handle())->values()->all();
        }

        foreach ($asked as $handle) {
            if (Site::get($handle) === null) {
                throw new \InvalidArgumentException("There is no site named [{$handle}].");
            }
        }

        return $asked;
    }

    /**
     * @return list<string>
     */
    private function failOn(): array
    {
        $raw = trim((string) $this->option('fail-on'));

        if ($raw === '') {
            return self::CONDITIONS;
        }

        $conditions = array_values(array_unique(array_map('trim', explode(',', $raw))));

        foreach ($conditions as $condition) {
            if (! in_array($condition, self::CONDITIONS, true)) {
                throw new \InvalidArgumentException('--fail-on accepts '.implode(', ', self::CONDITIONS).", got [{$condition}].");
            }
        }

        return $conditions;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function human(array $result): void
    {
        foreach ($result['sites'] as $row) {
            $this->newLine();
            $this->components->twoColumnDetail(
                "<fg=yellow>{$row['site']}</>",
                ($row['exists'] ? "{$row['profile']}, {$row['status']}" : 'no statement saved').", {$row['open_exceptions']} open ".($row['open_exceptions'] === 1 ? 'exception' : 'exceptions'),
            );

            if ($row['problems'] === []) {
                $this->line('  Complete and current.');

                continue;
            }

            foreach ($row['problems'] as $problem) {
                $tag = $problem['level'] === Problem::ERROR ? '<fg=red>error</>' : '<fg=yellow>warning</>';
                $this->line("  {$tag}  {$problem['message']}");
            }
        }

        $this->newLine();
        $f = $result['feedback'];

        if ($f['form'] === null) {
            $this->line('Feedback: no form configured.');
        } elseif ($f['response_days'] === null) {
            $this->line("Feedback: {$f['total']} on the form, {$f['awaiting']} awaiting acknowledgment, no response window configured.");
        } else {
            $this->line("Feedback: {$f['total']} on the form, {$f['awaiting']} awaiting acknowledgment, {$f['overdue']} past the {$f['response_days']}-day window.");
        }

        $this->line('Integrations: '.$result['integrations'].($result['imported_fixes'] > 0 ? ', '.$result['imported_fixes'].' '.($result['imported_fixes'] === 1 ? 'fix' : 'fixes').' imported into the evidence log' : '').'.');
        $this->line($result['failed'] ? 'Found: '.implode(', ', $result['found']).'.' : 'Nothing to fail on.');
    }
}
