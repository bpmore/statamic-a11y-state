<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

use InvalidArgumentException;

final class UnknownProfile extends InvalidArgumentException
{
    /**
     * @param  list<string>  $known
     */
    public function __construct(public readonly string $handle, array $known)
    {
        parent::__construct("No profile named [{$handle}]. Configured profiles: ".implode(', ', $known).'.');
    }
}
