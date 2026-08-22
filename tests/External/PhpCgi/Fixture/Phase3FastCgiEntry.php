<?php

declare(strict_types=1);

namespace Kongono\Tests\External\PhpCgi\Fixture;

use Kongono\PhpCgi\Bootstrap\CgiOperatorProfile;
use Kongono\PhpCgi\Entry\CgiFrontController;
use RuntimeException;
use Throwable;

/** Runs one real front-controller request and appends closed cleanup evidence. */
final class Phase3FastCgiEntry
{
    private function __construct() {}

    public static function run(CgiOperatorProfile $profile): void
    {
        $journal = self::journalPath();
        $application = null;
        $failure = null;
        $outcome = 'failure';
        try {
            $application = Phase3AcceptanceApplication::boot($profile);
            CgiFrontController::run(
                $application->runtime(),
                static function () use ($application): void {
                    $application->shutdown();
                },
            );
            $outcome = 'complete';
        } catch (Throwable $caught) {
            $failure = $caught;
        }

        $activeOperations = null === $application ? 0 : $application->activeOperationCount();
        $shutdown = null !== $application && $application->shutdownComplete()
            ? 'complete'
            : 'incomplete';
        self::appendJournal($journal, [
            'profile' => CgiOperatorProfile::Production === $profile ? 'production' : 'loopback_development',
            'outcome' => $outcome,
            'shutdown' => $shutdown,
            'active_operations' => $activeOperations,
        ]);
        if (null !== $failure) {
            throw $failure;
        }
    }

    private static function journalPath(): string
    {
        $value = getenv('KONGONO_PHASE3_ACCEPTANCE_JOURNAL');
        if (false === $value || '' === $value || basename($value) !== 'journal.jsonl') {
            throw new RuntimeException('phase3_journal_invalid');
        }
        $journal = realpath($value);
        $directory = realpath(dirname($value));
        $temporary = realpath(sys_get_temp_dir());
        if (
            false === $journal
            || false === $directory
            || false === $temporary
            || dirname($directory) !== $temporary
            || 1 !== preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', basename($directory))
            || is_link($journal)
            || is_link($directory)
        ) {
            throw new RuntimeException('phase3_journal_invalid');
        }

        return $journal;
    }

    /** @param array{profile: string, outcome: string, shutdown: string, active_operations: int} $record */
    private static function appendJournal(string $journal, array $record): void
    {
        clearstatcache(true, $journal);
        $size = filesize($journal);
        if (!is_int($size) || $size > 2_096_640) {
            throw new RuntimeException('phase3_journal_limit');
        }
        $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        if (strlen($line) > 512) {
            throw new RuntimeException('phase3_journal_limit');
        }
        $handle = @fopen($journal, 'ab');
        if (false === $handle) {
            throw new RuntimeException('phase3_journal_write_failed');
        }
        try {
            $offset = 0;
            while ($offset < strlen($line)) {
                $written = @fwrite($handle, substr($line, $offset));
                if (false === $written || 0 === $written) {
                    throw new RuntimeException('phase3_journal_write_failed');
                }
                $offset += $written;
            }
            if (!fflush($handle)) {
                throw new RuntimeException('phase3_journal_write_failed');
            }
        } finally {
            fclose($handle);
        }
    }
}
