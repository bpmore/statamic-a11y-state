<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

use RuntimeException;

/**
 * Thrown when a statement would claim full conformance while an open
 * exception at or above the configured severity says otherwise. Names the
 * exceptions, because a refusal that does not say why is a bug report.
 */
final class Overclaim extends RuntimeException
{
    /**
     * @param  list<KnownException>  $exceptions
     */
    public function __construct(public readonly array $exceptions, Severity $threshold)
    {
        $named = implode('; ', array_map(fn (KnownException $e) => $e->summary(), $exceptions));

        parent::__construct(sprintf(
            'Full conformance is refused: %d open %s at or above %s severity: %s',
            count($exceptions),
            count($exceptions) === 1 ? 'exception' : 'exceptions',
            $threshold->value,
            $named,
        ));
    }
}
