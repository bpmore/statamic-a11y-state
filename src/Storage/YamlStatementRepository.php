<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Storage;

use Bpmore\A11yState\Statement\Statement;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * One YAML file per site under a directory, which is the default and is
 * meant to sit in the site's repository beside its content.
 */
final class YamlStatementRepository implements StatementRepository
{
    public function __construct(private readonly string $directory) {}

    public function directory(): string
    {
        return $this->directory;
    }

    public function find(string $site): ?Statement
    {
        $path = $this->path($site);

        if (! file_exists($path)) {
            return null;
        }

        $data = Yaml::parseFile($path);

        if (! is_array($data)) {
            throw new RuntimeException("Statement file [{$path}] is not a statement.");
        }

        $data['site'] = $site;

        return Statement::fromArray($data);
    }

    public function save(Statement $statement): void
    {
        Guard::assertPersistable($statement);

        if (! is_dir($this->directory) && ! @mkdir($this->directory, 0775, true) && ! is_dir($this->directory)) {
            throw new RuntimeException("The statement directory [{$this->directory}] cannot be created.");
        }

        $path = $this->path($statement->site);
        $temp = $path.'.'.bin2hex(random_bytes(4)).'.tmp';
        $yaml = Yaml::dump($statement->toArray(), 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

        if (file_put_contents($temp, $yaml, LOCK_EX) === false || ! rename($temp, $path)) {
            @unlink($temp);

            throw new RuntimeException("The statement for [{$statement->site}] could not be written to [{$path}].");
        }
    }

    public function sites(): array
    {
        if (! is_dir($this->directory)) {
            return [];
        }

        $sites = array_map(fn (string $p) => basename($p, '.yaml'), glob($this->directory.'/*.yaml') ?: []);
        sort($sites);

        return array_values($sites);
    }

    private function path(string $site): string
    {
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $site)) {
            throw new RuntimeException("Site handle [{$site}] is not a safe file name.");
        }

        return $this->directory.'/'.$site.'.yaml';
    }
}
