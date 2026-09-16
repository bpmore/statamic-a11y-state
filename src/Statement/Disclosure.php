<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

use InvalidArgumentException;

/**
 * One thing a profile expects a statement to say, and whether the regime
 * behind the profile requires it. Data from the config file, so a site can
 * add a disclosure its own regulator asks for without a code change.
 *
 * `required` is true, false, or the string 'unless-fully-conformant': the
 * EU model statement's list of non-accessible content "can be deleted if
 * not applicable", and it is not applicable exactly when the claim is full
 * conformance. A statement that has not been assessed does not owe a list
 * of exceptions either, since it has not looked.
 */
final class Disclosure
{
    public const UNLESS_FULLY_CONFORMANT = 'unless-fully-conformant';

    /** The keys with a home of their own on the statement rather than in the text fields. */
    public const STRUCTURED = ['conformance_status', 'exceptions'];

    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly bool|string $required,
        public readonly string $source,
        public readonly ?string $hint = null,
        public readonly bool $inferred = false,
        /** 'text', 'textarea', 'date', 'url': what the editor shows. Cosmetic. */
        public readonly string $type = 'textarea',
    ) {
        if (! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            throw new InvalidArgumentException("Disclosure key [{$key}] must be a lowercase identifier.");
        }

        if (! is_bool($required) && $required !== self::UNLESS_FULLY_CONFORMANT) {
            throw new InvalidArgumentException("Disclosure [{$key}]: required must be true, false or '".self::UNLESS_FULLY_CONFORMANT."'.");
        }

        if (trim($source) === '') {
            // A disclosure nobody can cite is a requirement somebody invented.
            // docs/profiles.md is built from this field, so it cannot be blank.
            throw new InvalidArgumentException("Disclosure [{$key}] names no source.");
        }
    }

    public function isRequiredFor(ConformanceStatus $status): bool
    {
        if (is_bool($this->required)) {
            return $this->required;
        }

        return $status === ConformanceStatus::PartiallyConformant || $status === ConformanceStatus::NonConformant;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(string $key, array $data): self
    {
        return new self(
            key: $key,
            label: (string) ($data['label'] ?? ucfirst(str_replace('_', ' ', $key))),
            required: $data['required'] ?? false,
            source: (string) ($data['source'] ?? ''),
            hint: is_string($data['hint'] ?? null) ? $data['hint'] : null,
            inferred: (bool) ($data['inferred'] ?? false),
            type: (string) ($data['type'] ?? 'textarea'),
        );
    }
}
