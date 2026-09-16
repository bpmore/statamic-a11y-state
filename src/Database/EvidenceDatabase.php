<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Database;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Which connection the Eloquent driver writes to, and how its table gets
 * there. Read from the `storage` block of the config file:
 *
 *   'evidence_driver' => 'yaml' | 'eloquent'
 *   'evidence_connection' => null (the site's default) | a connection name
 */
final class EvidenceDatabase
{
    public const YAML = 'yaml';

    public const ELOQUENT = 'eloquent';

    public static function driver(): string
    {
        $driver = config('statamic-a11y-state.storage.evidence_driver', self::YAML);

        return $driver === self::ELOQUENT ? self::ELOQUENT : self::YAML;
    }

    public static function connectionName(): string
    {
        $configured = config('statamic-a11y-state.storage.evidence_connection');

        return is_string($configured) && $configured !== '' ? $configured : (string) config('database.default');
    }

    public static function log(): EloquentEvidenceLog
    {
        return new EloquentEvidenceLog(DB::connection(self::connectionName()));
    }

    /**
     * Create the table on the configured connection, through Laravel's own
     * migrator so the run is recorded where `php artisan migrate` looks.
     *
     * @return list<string> what was done, one line each
     */
    public static function install(): array
    {
        $connection = self::connectionName();

        if (self::createSqliteFileIfMissing($connection)) {
            $done[] = "Created the database file for [{$connection}].";
        }

        Artisan::call('migrate', [
            '--database' => $connection,
            '--path' => realpath(__DIR__.'/../../database/migrations'),
            '--realpath' => true,
            '--force' => true,
        ]);

        $done[] = 'The evidence table ['.EloquentEvidenceLog::TABLE."] is in place on [{$connection}].";

        if (self::driver() !== self::ELOQUENT) {
            $done[] = "The evidence log is still on the yaml driver. Set storage.evidence_driver to 'eloquent' to use the table.";
        }

        return $done;
    }

    private static function createSqliteFileIfMissing(string $connection): bool
    {
        $config = (array) config("database.connections.{$connection}", []);
        $path = $config['database'] ?? null;

        if (($config['driver'] ?? null) !== 'sqlite' || ! is_string($path) || $path === ':memory:' || file_exists($path)) {
            return false;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        touch($path);

        return true;
    }
}
