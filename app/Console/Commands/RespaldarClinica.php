<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

#[Signature('clinica:respaldo')]
#[Description('Respalda la base de datos y los archivos clínicos en un ZIP cifrado privado')]
class RespaldarClinica extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $conexion = DB::connection()->getConfig();
        if (($conexion['driver'] ?? null) !== 'mysql' || ! class_exists(ZipArchive::class)) {
            $this->error('El respaldo requiere MySQL/MariaDB y la extensión PHP zip.');

            return self::FAILURE;
        }
        $password = config('clinica.respaldo.password') ?: config('app.key');
        if (! is_string($password) || strlen($password) < 16) {
            $this->error('Configure CLINICA_BACKUP_PASSWORD o una APP_KEY válida.');

            return self::FAILURE;
        }
        $disco = Storage::disk('local');
        $disco->makeDirectory('backups');
        $nombre = 'clinica-'.now()->format('Ymd-His').'-'.Str::uuid();
        $sql = $disco->path('backups/'.$nombre.'.sql');
        $archivo = $disco->path('backups/'.$nombre.'.zip');
        $zip = new ZipArchive;
        $abierto = false;
        try {
            $resultado = Process::timeout(600)->env(['MYSQL_PWD' => (string) ($conexion['password'] ?? '')])->run([
                config('clinica.respaldo.binario'), '--host='.(string) $conexion['host'], '--port='.(string) $conexion['port'],
                '--user='.(string) $conexion['username'], '--single-transaction', '--routines', '--events', '--triggers',
                '--no-tablespaces', '--result-file='.$sql, (string) $conexion['database'],
            ]);
            if (! $resultado->successful() || ! is_file($sql) || filesize($sql) === 0) {
                throw new \RuntimeException('No se pudo generar el respaldo de la base de datos. Revise CLINICA_MYSQLDUMP y la conexión.');
            }
            if ($zip->open($archivo, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true || ! $zip->setPassword($password)) {
                throw new \RuntimeException('No se pudo crear el archivo cifrado.');
            }
            $abierto = true;
            $agregar = static function (string $ruta, string $destino) use ($zip, $password): void {
                if (! $zip->addFile($ruta, $destino) || ! $zip->setEncryptionName($destino, ZipArchive::EM_AES_256, $password)) {
                    throw new \RuntimeException('No se pudo cifrar un archivo del respaldo.');
                }
            };
            $agregar($sql, 'database.sql');
            foreach (['local', 'public'] as $nombreDisco) {
                $raiz = Storage::disk($nombreDisco)->path('');
                if (! is_dir($raiz)) {
                    continue;
                }
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS)) as $file) {
                    $relativo = str_replace('\\', '/', substr($file->getPathname(), strlen(rtrim($raiz, '/\\')) + 1));
                    if ($file->isFile() && ! $file->isLink() && ! str_starts_with($relativo, 'backups/')) {
                        $agregar($file->getPathname(), 'archivos/'.$nombreDisco.'/'.$relativo);
                    }
                }
            }
            if (! $zip->close()) {
                throw new \RuntimeException('No se pudo terminar el respaldo.');
            }
            $abierto = false;
            $this->info('Respaldo guardado: backups/'.$nombre.'.zip');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            if ($abierto) {
                $zip->close();
            }
            if (is_file($archivo)) {
                unlink($archivo);
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if (is_file($sql)) {
                unlink($sql);
            }
        }
    }
}
