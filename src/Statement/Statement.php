<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

use InvalidArgumentException;

/**
 * One site's statement, as entered: which profile it is written under, the
 * claim it makes, the text of every disclosure, the exceptions it admits,
 * and the attestation behind a claim of full conformance, if there is one.
 *
 * Immutable. Every change returns a new statement, and the rules about what
 * a statement may claim live in `Validator` and are enforced again by the
 * repository on save, so there is no path that stores an unsupported claim.
 *
 * Disclosures are kept by key whatever the profile: switching profile does
 * not drop a value the new profile has no field for. `Profiles::orphanedBy`
 * names those so the editor can warn.
 */
final class Statement
{
    /**
     * @param  array<string, mixed>  $disclosures  by disclosure key
     * @param  list<KnownException>  $exceptions
     */
    public function __construct(
        public readonly string $site,
        public readonly string $profile,
        public readonly ConformanceStatus $status,
        public readonly array $disclosures = [],
        public readonly array $exceptions = [],
        public readonly ?Attestation $attestation = null,
        /** When this version was saved, as a moment, or null for a statement nobody has saved yet. */
        public readonly ?string $updatedAt = null,
        public readonly ?string $updatedBy = null,
    ) {
        if (trim($site) === '') {
            throw new InvalidArgumentException('A statement belongs to a site.');
        }

        if (trim($profile) === '') {
            throw new InvalidArgumentException('A statement is written under a profile.');
        }

        $ids = [];

        foreach ($exceptions as $exception) {
            if (! $exception instanceof KnownException) {
                throw new InvalidArgumentException('Exceptions must be KnownException instances.');
            }

            if (isset($ids[$exception->id])) {
                throw new InvalidArgumentException("Exception id [{$exception->id}] appears twice.");
            }

            $ids[$exception->id] = true;
        }
    }

    public static function blank(string $site, string $profile): self
    {
        return new self($site, $profile, ConformanceStatus::NotAssessed);
    }

    public function disclosure(string $key): mixed
    {
        return $this->disclosures[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return self::isFilled($this->disclosures[$key] ?? null);
    }

    public static function isFilled(mixed $value): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== null && $value !== false;
    }

    /**
     * @return list<KnownException>
     */
    public function openExceptions(): array
    {
        return array_values(array_filter($this->exceptions, fn (KnownException $e) => $e->isOpen()));
    }

    public function exception(string $id): ?KnownException
    {
        foreach ($this->exceptions as $exception) {
            if ($exception->id === $id) {
                return $exception;
            }
        }

        return null;
    }

    public function withProfile(string $profile): self
    {
        return $this->copy(profile: $profile);
    }

    public function withStatus(ConformanceStatus $status): self
    {
        return $this->copy(status: $status);
    }

    /**
     * @param  array<string, mixed>  $disclosures
     */
    public function withDisclosures(array $disclosures): self
    {
        return $this->copy(disclosures: array_merge($this->disclosures, $disclosures));
    }

    /**
     * @param  list<KnownException>  $exceptions
     */
    public function withExceptions(array $exceptions): self
    {
        return $this->copy(exceptions: array_values($exceptions));
    }

    public function withException(KnownException $exception): self
    {
        $exceptions = array_values(array_filter($this->exceptions, fn (KnownException $e) => $e->id !== $exception->id));
        $exceptions[] = $exception;

        return $this->copy(exceptions: $exceptions);
    }

    public function withAttestation(?Attestation $attestation): self
    {
        return $this->copy(attestation: $attestation);
    }

    public function withUpdated(string $at, ?string $by): self
    {
        return $this->copy(updatedAt: $at, updatedBy: $by);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $status = ConformanceStatus::tryFromLoose($data['status'] ?? null);

        if ($status === null) {
            throw new InvalidArgumentException('Statement has an unknown conformance status ['.(string) ($data['status'] ?? '').'].');
        }

        $exceptions = [];

        foreach ((array) ($data['exceptions'] ?? []) as $row) {
            if (is_array($row)) {
                $exceptions[] = KnownException::fromArray($row);
            }
        }

        $attestation = is_array($data['attestation'] ?? null) && $data['attestation'] !== []
            ? Attestation::fromArray($data['attestation'])
            : null;

        return new self(
            site: (string) ($data['site'] ?? ''),
            profile: (string) ($data['profile'] ?? ''),
            status: $status,
            disclosures: (array) ($data['disclosures'] ?? []),
            exceptions: $exceptions,
            attestation: $attestation,
            updatedAt: is_string($data['updated_at'] ?? null) ? $data['updated_at'] : null,
            updatedBy: is_string($data['updated_by'] ?? null) ? $data['updated_by'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'site' => $this->site,
            'profile' => $this->profile,
            'status' => $this->status->value,
            'disclosures' => $this->disclosures,
            'exceptions' => array_map(fn (KnownException $e) => $e->toArray(), $this->exceptions),
            'attestation' => $this->attestation?->toArray(),
            'updated_at' => $this->updatedAt,
            'updated_by' => $this->updatedBy,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $disclosures
     * @param  list<KnownException>|null  $exceptions
     */
    private function copy(
        ?string $profile = null,
        ?ConformanceStatus $status = null,
        ?array $disclosures = null,
        ?array $exceptions = null,
        Attestation|null|false $attestation = false,
        ?string $updatedAt = null,
        string|null|false $updatedBy = false,
    ): self {
        return new self(
            site: $this->site,
            profile: $profile ?? $this->profile,
            status: $status ?? $this->status,
            disclosures: $disclosures ?? $this->disclosures,
            exceptions: $exceptions ?? $this->exceptions,
            attestation: $attestation === false ? $this->attestation : $attestation,
            updatedAt: $updatedAt ?? $this->updatedAt,
            updatedBy: $updatedBy === false ? $this->updatedBy : $updatedBy,
        );
    }
}
