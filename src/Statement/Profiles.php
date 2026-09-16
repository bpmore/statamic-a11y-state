<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

use InvalidArgumentException;

/**
 * Every profile the config file declares, by handle.
 */
final class Profiles
{
    /** @var array<string, Profile> */
    private array $profiles = [];

    /**
     * @param  list<Profile>  $profiles
     */
    public function __construct(array $profiles)
    {
        foreach ($profiles as $profile) {
            $this->profiles[$profile->handle] = $profile;
        }

        if ($this->profiles === []) {
            throw new InvalidArgumentException('No profiles are configured. Publish the config file and check the profiles block.');
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $config  the `profiles` config block
     */
    public static function fromArray(array $config): self
    {
        $profiles = [];

        foreach ($config as $handle => $spec) {
            $profiles[] = Profile::fromArray((string) $handle, is_array($spec) ? $spec : []);
        }

        return new self($profiles);
    }

    public function get(string $handle): Profile
    {
        if (! isset($this->profiles[$handle])) {
            throw new UnknownProfile($handle, array_keys($this->profiles));
        }

        return $this->profiles[$handle];
    }

    public function has(string $handle): bool
    {
        return isset($this->profiles[$handle]);
    }

    /**
     * @return list<Profile>
     */
    public function all(): array
    {
        return array_values($this->profiles);
    }

    /**
     * @return list<string>
     */
    public function handles(): array
    {
        return array_keys($this->profiles);
    }

    /**
     * The disclosures a statement has filled in that the profile it is
     * moving to does not know about. Nothing is dropped: the values stay
     * on the statement, and this is the list the editor warns with.
     *
     * @return list<string>
     */
    public function orphanedBy(Statement $statement, Profile $to): array
    {
        $orphans = [];

        foreach ($statement->disclosures as $key => $value) {
            if ($to->disclosure($key) === null && Statement::isFilled($value)) {
                $orphans[] = $key;
            }
        }

        return $orphans;
    }
}
