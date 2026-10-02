<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * FilesystemTolerante
 *
 * Laravel escribe los archivos de forma atómica con `tempnam()` +
 * `rename()`. En Windows, `tempnam()` puede emitir el aviso
 * "file created in the system's temporary directory" (por ejemplo cuando
 * el directorio temporal no es escribible para el usuario del proceso).
 * Laravel convierte ese aviso en `ErrorException`, lo que hace fallar la
 * compilación de vistas y, en consecuencia, la página o la prueba.
 *
 * Esta clase conserva el comportamiento normal y, si `tempnam()` no es
 * utilizable, recurre a `put_contents` directo (misma garantía práctica en
 * Windows, sin el aviso).
 */
class FilesystemTolerante extends Filesystem
{
    /**
     * {@inheritDoc}
     */
    public function replace($path, $content, $mode = null)
    {
        // Sin directorio padre no hay nada que reemplazar.
        if (! is_null($mode)) {
            return parent::replace($path, $content, $mode);
        }

        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        if (! is_file($path) || ! is_writable($path)) {
            return $this->put($path, $content);
        }

        $temp = @tempnam(dirname($path), basename($path));

        if ($temp === false) {
            return $this->put($path, $content);
        }

        // tempnam() puede devolver una ruta de otro directorio (con aviso
        // suprimido): en ese caso se descarta y se escribe directo.
        if (realpath(dirname($temp)) !== realpath(dirname($path))) {
            @unlink($temp);

            return $this->put($path, $content);
        }

        if (@file_put_contents($temp, $content) === false) {
            @unlink($temp);

            return $this->put($path, $content);
        }

        return @rename($temp, $path);
    }
}
