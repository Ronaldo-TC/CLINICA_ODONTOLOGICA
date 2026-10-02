<?php

/*
|--------------------------------------------------------------------------
| Bootstrap para pruebas (PHPUnit)
|--------------------------------------------------------------------------
| Además del autoload de Composer, permite que la compilación de vistas
| Blade se escriba en otra carpeta mediante la variable de entorno
| VIEW_COMPILED_PATH. Es útil en entornos donde `storage/framework/views`
| no es escribible por el usuario que ejecuta las pruebas.
|
| No cambia el comportamiento de la aplicación: sólo afecta a las pruebas.
*/

require __DIR__ . '/../vendor/autoload.php';

/*
| Si se define VIEW_COMPILED_PATH, las vistas Blade compiladas se escriben
| ahí. Es útil cuando `storage/framework/views` no es escribible por el
| usuario que ejecuta las pruebas. No afecta a la aplicación en producción.
*/
$compiled = getenv('VIEW_COMPILED_PATH') ?: ($_ENV['VIEW_COMPILED_PATH'] ?? null);

if (is_string($compiled) && $compiled !== '') {
    if (! is_dir($compiled)) {
        @mkdir($compiled, 0777, true);
    }

    config(['view.compiled' => $compiled]);
}
