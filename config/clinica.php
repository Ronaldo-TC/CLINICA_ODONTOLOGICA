<?php

return [
    'respaldo' => [
        'binario' => env('CLINICA_MYSQLDUMP', 'mysqldump'),
        'password' => env('CLINICA_BACKUP_PASSWORD'),
        'hora' => env('CLINICA_BACKUP_HORA', '02:00'),
    ],
];
