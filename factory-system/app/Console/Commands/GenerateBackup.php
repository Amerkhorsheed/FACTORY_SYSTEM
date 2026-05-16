<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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

        $dbHost = config('database.connections.mysql.host');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $command = sprintf(
            'mysqldump -h%s -u%s %s %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $dbPass ? '-p'.escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            storage_path("app/{$filename}")
        );

        // Ensure backup directory exists
        Storage::makeDirectory('backups');

        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            $this->info("Backup created: {$filename}");

            return self::SUCCESS;
        }

        $this->error('Backup failed with exit code: '.$returnCode);

        return self::FAILURE;
    }
}
