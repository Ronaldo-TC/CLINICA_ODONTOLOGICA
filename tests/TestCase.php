<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea la aplicación para las pruebas.
     */
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        /*
         * En entornos donde `storage/framework/views` no es escribible por el
         * usuario que ejecuta las pruebas, se puede compilar en otra carpeta
         * definiendo VIEW_COMPILED_PATH en phpunit.xml o en el entorno.
         */
        $compiled = env('VIEW_COMPILED_PATH');

        if (is_string($compiled) && $compiled !== '') {
            if (! is_dir($compiled)) {
                @mkdir($compiled, 0777, true);
            }

            $app['config']->set('view.compiled', $compiled);
        }

        return $app;
    }
}
