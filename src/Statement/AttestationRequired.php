<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

use RuntimeException;

/**
 * Thrown by every path that would persist a statement claiming full
 * conformance without a named, dated attestation behind it.
 */
final class AttestationRequired extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A statement cannot claim full conformance without an attestation naming who confirmed it, on what date, and on what basis.');
    }
}
