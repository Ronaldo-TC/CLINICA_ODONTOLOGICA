<?php

namespace Tests\Feature;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class RespaldosTest extends TestCase
{
    public function test_backup_encrypts_database_and_clinical_files_and_excludes_old_backups(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        config(['clinica.respaldo.password' => 'test-backup-password-2026']);
        Storage::disk('local')->put('radiologias/1/estudio.dcm', 'private-study');
        Storage::disk('local')->put('backups/previous.zip', 'old-backup');
        Storage::disk('public')->put('odontograma/adulto/diente.png', 'illustration');
        Process::fake(function (PendingProcess $process) {
            foreach ($process->command as $argument) {
                if (str_starts_with($argument, '--result-file=')) {
                    file_put_contents(substr($argument, strlen('--result-file=')), 'CREATE TABLE clinical_test (id INT);');
                }
            }

            return Process::result();
        });

        $this->artisan('clinica:respaldo')->assertSuccessful();

        $archives = array_values(array_filter(Storage::disk('local')->files('backups'), fn (string $file): bool => str_starts_with($file, 'backups/clinica-')));
        $this->assertCount(1, $archives);
        $this->assertStringEndsWith('.zip', $archives[0]);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($archives[0])));
        $this->assertSame(ZipArchive::EM_AES_256, $zip->statName('database.sql')['encryption_method']);
        $this->assertFalse($zip->getFromName('database.sql'));
        $zip->setPassword('test-backup-password-2026');
        $this->assertSame('CREATE TABLE clinical_test (id INT);', $zip->getFromName('database.sql'));
        $this->assertSame('private-study', $zip->getFromName('archivos/local/radiologias/1/estudio.dcm'));
        $this->assertSame('illustration', $zip->getFromName('archivos/public/odontograma/adulto/diente.png'));
        $this->assertFalse($zip->locateName('archivos/local/backups/previous.zip'));
        $zip->close();
        Process::assertRan(fn (PendingProcess $process): bool => isset($process->environment['MYSQL_PWD']) && ! str_contains(implode(' ', $process->command), '--password'));
    }

    public function test_failed_database_dump_leaves_no_plaintext_or_partial_archive(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        config(['clinica.respaldo.password' => 'test-backup-password-2026']);
        Process::fake(function (PendingProcess $process) {
            foreach ($process->command as $argument) {
                if (str_starts_with($argument, '--result-file=')) {
                    file_put_contents(substr($argument, strlen('--result-file=')), 'partial sensitive content');
                }
            }

            return Process::result(errorOutput: 'dump failed', exitCode: 1);
        });

        $this->artisan('clinica:respaldo')->assertFailed();

        $this->assertSame([], Storage::disk('local')->allFiles('backups'));
    }
}
