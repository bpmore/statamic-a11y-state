<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

use InvalidArgumentException;

/**
 * A jurisdiction: the disclosures its regime expects and which of them it
 * requires. Nothing here says what the law means, only what a statement
 * under it is expected to contain, and each disclosure names where that
 * expectation came from.
 */
final class Profile
{
    /**
     * @param  list<Disclosure>  $disclosures
     */
    public function __construct(
        public readonly string $handle,
        public readonly string $name,
        public readonly string $description,
        public readonly array $disclosures,
        /** What the regime measures against, offered as the default for the standard disclosure. */
        public readonly ?string $standardDefault = null,
        /** The regime's own name for the document, where it has one. */
        public readonly string $title = 'Accessibility statement',
    ) {
        if (! preg_match('/^[a-z][a-z0-9_]*$/', $handle)) {
            throw new InvalidArgumentException("Profile handle [{$handle}] must be a lowercase identifier.");
        }

        $seen = [];

        foreach ($disclosures as $disclosure) {
            if (isset($seen[$disclosure->key])) {
                throw new InvalidArgumentException("Profile [{$handle}] lists disclosure [{$disclosure->key}] twice.");
            }

            $seen[$disclosure->key] = true;
        }
    }

    public function disclosure(string $key): ?Disclosure
    {
        foreach ($this->disclosures as $disclosure) {
            if ($disclosure->key === $key) {
                return $disclosure;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map(fn (Disclosure $d) => $d->key, $this->disclosures);
    }

    /**
     * @return list<Disclosure>
     */
    public function requiredFor(ConformanceStatus $status): array
    {
        return array_values(array_filter($this->disclosures, fn (Disclosure $d) => $d->isRequiredFor($status)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(string $handle, array $data): self
    {
        $disclosures = [];

        foreach ((array) ($data['disclosures'] ?? []) as $key => $spec) {
            $disclosures[] = Disclosure::fromArray((string) $key, is_array($spec) ? $spec : []);
        }

        return new self(
            handle: $handle,
            name: (string) ($data['name'] ?? $handle),
            description: (string) ($data['description'] ?? ''),
            disclosures: $disclosures,
            standardDefault: is_string($data['standard_default'] ?? null) ? $data['standard_default'] : null,
            title: (string) ($data['title'] ?? 'Accessibility statement'),
        );
    }
}
