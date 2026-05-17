<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Generate a database backup.
 *
 * Scheduled: Daily at 2:00 AM.
 */
class GenerateBackup extends Command
{
    protected $signature = 'factory:backup';

    protected $description = 'إنشاء نسخة احتياطية لقاعدة البيانات';

    public function handle(): int
    {
        $filename = 'backups/factory_db_'.now()->format('Y-m-d_H-i-s').'.sql';
        $path = storage_path("app/{$filename}");

        if (config('database.default') !== 'mysql') {
            $this->error('Backup failed: factory backups require the mysql database connection.');

            return self::FAILURE;
        }

        $binary = (new ExecutableFinder)->find('mysqldump');
        if (! $binary) {
            $this->error('Backup failed: mysqldump executable was not found in PATH.');

            return self::FAILURE;
        }

        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        File::ensureDirectoryExists(dirname($path));

        $stream = fopen($path, 'wb');
        if (! $stream) {
            $this->error('Backup failed: backup file could not be opened for writing.');

            return self::FAILURE;
        }

        $errorOutput = '';
        $process = new Process(array_values(array_filter([
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--host='.$dbHost,
            $dbPort ? '--port='.$dbPort : null,
            '--user='.$dbUser,
            $dbName,
        ])), null, $dbPass ? ['MYSQL_PWD' => $dbPass] : null, null, 3600);

        $returnCode = $process->run(function (string $type, string $buffer) use ($stream, &$errorOutput): void {
            if ($type === Process::OUT) {
                fwrite($stream, $buffer);

                return;
            }

            $errorOutput .= $buffer;
        });

        fclose($stream);

        if ($returnCode === 0 && File::size($path) > 0) {
            $this->info("Backup created: {$filename}");

            return self::SUCCESS;
        }

        File::delete($path);
        $message = trim($errorOutput) ?: 'mysqldump produced an empty backup or failed without output.';
        $this->error('Backup failed: '.Str::limit($message, 500));

        return self::FAILURE;
    }
}
