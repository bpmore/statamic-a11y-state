<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

/**
 * One reason a statement is not ready, or one thing to know about it.
 *
 * Errors stop generation. Warnings do not: a stale assessment is flagged in
 * the control panel and by `grange:check`, and the published statement is
 * left exactly as it was, because a document that changes itself when a
 * date passes is a document nobody signed.
 */
final class Problem
{
    public const ERROR = 'error';

    public const WARNING = 'warning';

    public const MISSING_DISCLOSURE = 'missing-disclosure';

    public const MISSING_EXCEPTIONS = 'missing-exceptions';

    public const OVERCLAIM = 'overclaim';

    public const ATTESTATION_MISSING = 'attestation-missing';

    public const STALE = 'stale';

    public const UNKNOWN_PROFILE = 'unknown-profile';

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $code,
        public readonly string $level,
        public readonly string $message,
        public readonly ?string $field = null,
        public readonly array $context = [],
    ) {}

    public static function error(string $code, string $message, ?string $field = null, array $context = []): self
    {
        return new self($code, self::ERROR, $message, $field, $context);
    }

    public static function warning(string $code, string $message, ?string $field = null, array $context = []): self
    {
        return new self($code, self::WARNING, $message, $field, $context);
    }

    public function isError(): bool
    {
        return $this->level === self::ERROR;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'level' => $this->level,
            'message' => $this->message,
            'field' => $this->field,
            'context' => $this->context,
        ];
    }
}
