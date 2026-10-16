# Clínica Odontológica UTIC

Sistema web para la gestión de una clínica odontológica. Está desarrollado con Laravel y utiliza Vite para compilar los recursos del frontend.

## Requisitos

- PHP 8.3 o superior dentro de la rama 8.x.
- Composer 2.
- Node.js 20.19 o superior, o 22.12 o superior, y npm. Estas versiones cumplen los requisitos de Vite 8.
- Una base de datos: SQLite para una instalación local sencilla (opción predeterminada) o MySQL.
- Para generar PDF desde el sistema: Node.js, Puppeteer instalado con npm y Google Chrome o Chromium en el equipo donde corre Laravel.

Composer comprobará las extensiones PHP necesarias al instalar dependencias. Si se utiliza MySQL, PHP debe tener habilitado `pdo_mysql`; para SQLite, `pdo_sqlite`.

## Instalación local en Windows

git clone https://github.com/Ronaldo-TC/CLINICA_ODONTOLOGICA.git
Set-Location CLINICA_ODONTOLOGICA

composer install
npm ci

Copy-Item .env.example .env

> **Importante:** no uses `php artisan migrate:fresh --seed` sobre una base de datos que quieras conservar. `migrate:fresh` elimina todas las tablas antes de crearlas de nuevo. Para una instalación normal o una actualización, utiliza `php artisan migrate --seed`.

php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:linK

## Generación de PDF

composer require spatie/laravel-pdf
composer require spatie/browsershot

php artisan serve

## Comprobaciones útiles
