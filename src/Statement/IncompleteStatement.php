<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

use RuntimeException;

/**
 * The generator's refusal. Carries every error so the caller can show all
 * of them, and its message names each one, because a refusal that says
 * only "incomplete" sends somebody hunting.
 */
final class IncompleteStatement extends RuntimeException
{
    /**
     * @param  list<Problem>  $problems
     */
    public function __construct(public readonly array $problems)
    {
        parent::__construct('The statement cannot be generated: '.implode(' ', array_map(fn (Problem $p) => $p->message, $problems)));
    }

    /**
     * @return list<string>
     */
    public function fields(): array
    {
        return array_values(array_unique(array_filter(array_map(fn (Problem $p) => $p->field, $this->problems))));
    }
}
