<?php

declare(strict_types=1);

namespace Bpmore\Grange\Evidence;

use RuntimeException;

final class UnknownPredecessor extends RuntimeException
{
    public function __construct(public readonly string $id, public readonly string $supersedes)
    {
        parent::__construct("Evidence record [{$id}] supersedes [{$supersedes}], which is not in the log.");
    }
}
