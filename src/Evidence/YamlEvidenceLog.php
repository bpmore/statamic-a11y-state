<?php

declare(strict_types=1);

namespace Bpmore\Grange\Evidence;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * The log as one YAML file per record in a directory, which is the default.
 *
 * One file per record and not one file with every record in it, because
 * appending to a shared file is a rewrite of the whole file, and a rewrite
 * is exactly the operation this store must not have. A record is a file
 * that is created once: the write refuses if the file exists, goes to a
 * temporary name first and is renamed into place so a reader never sees a
 * half-written record, and the file is then made read-only. The read-only
 * bit is a courtesy to whoever opens the directory; the refusal to write
 * over an existing id is the rule.
 *
 * The directory is meant to live in the site's repository beside its
 * content, so the log travels with the site and its history is git's.
 */
final class YamlEvidenceLog implements EvidenceLog
{
    public function __construct(private readonly string $directory) {}

    public function directory(): string
    {
        return $this->directory;
    }

    public function append(Record $record): void
    {
        $path = $this->path($record->id);

        if (file_exists($path)) {
            throw new RecordExists($record->id);
        }

        if ($record->supersedes !== null && ! file_exists($this->path($record->supersedes))) {
            throw new UnknownPredecessor($record->id, $record->supersedes);
        }

        if (! is_dir($this->directory) && ! @mkdir($this->directory, 0775, true) && ! is_dir($this->directory)) {
            throw new RuntimeException("The evidence directory [{$this->directory}] cannot be created.");
        }

        $temp = $path.'.'.bin2hex(random_bytes(4)).'.tmp';
        $yaml = Yaml::dump($record->toArray(), 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

        if (file_put_contents($temp, $yaml, LOCK_EX) === false) {
            throw new RuntimeException("Evidence record [{$record->id}] could not be written to [{$this->directory}].");
        }

        // `rename` will replace an existing file on POSIX, so the existence
        // check above is repeated here on the very last step. Two processes
        // writing the same id at once is not a thing this addon produces,
        // and the second one still loses rather than winning.
        if (file_exists($path)) {
            @unlink($temp);

            throw new RecordExists($record->id);
        }

        if (! rename($temp, $path)) {
            @unlink($temp);

            throw new RuntimeException("Evidence record [{$record->id}] could not be moved into place.");
        }

        @chmod($path, 0444);
    }

    public function find(string $id): ?Record
    {
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $id)) {
            return null;
        }

        $path = $this->path($id);

        return file_exists($path) ? $this->read($path) : null;
    }

    public function all(): array
    {
        if (! is_dir($this->directory)) {
            return [];
        }

        $records = [];

        foreach (glob($this->directory.'/*.yaml') ?: [] as $path) {
            $records[] = $this->read($path);
        }

        return Ordering::chronological($records);
    }

    public function filter(Filter $filter): array
    {
        return Ordering::apply($this->all(), $filter);
    }

    public function count(): int
    {
        return is_dir($this->directory) ? count(glob($this->directory.'/*.yaml') ?: []) : 0;
    }

    private function path(string $id): string
    {
        return $this->directory.'/'.$id.'.yaml';
    }

    private function read(string $path): Record
    {
        $data = Yaml::parseFile($path);

        if (! is_array($data)) {
            throw new RuntimeException("Evidence file [{$path}] is not a record.");
        }

        $record = Record::fromArray($data);

        if ($record->id !== basename($path, '.yaml')) {
            // A file whose name and contents disagree has been edited by
            // hand or copied. Said out loud rather than trusted.
            throw new RuntimeException("Evidence file [{$path}] holds record [{$record->id}], which is not its name.");
        }

        return $record;
    }
}
