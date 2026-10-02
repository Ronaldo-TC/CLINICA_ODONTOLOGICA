# Cumplimiento de requerimientos — Clínica Odontológica UPEA

Documento de trazabilidad entre los requerimientos del PDF y el estado real del
proyecto. Última actualización: tras implementar T1, T2, T3, T4, T6, T8, T9 y T10.

## Resumen

| # | Requerimiento | Estado | Pruebas |
|---|---|---|---|
| T1 | Insumos y flujo de solicitudes con aprobaciones | ✅ Completo | 7 |
| T2 | Kardex (solo lectura) | ✅ Completo | 5 |
| T3 | Reportes con gráficos | ✅ Completo | 10 |
| T4 | Investigación (datos anonimizados) | ✅ Completo | 6 |
| T5 | Archivos, firma digital y PDF de historia | 🟡 Parcial (falta PDF, ver abajo) | 8 |
| T6 | Odontograma interactivo | ✅ Completo | 5 |
| T7 | Integración con SHICO | ⛔ Bloqueado (faltan credenciales) | — |
| T8 | Dashboard con métricas reales | ✅ Completo | 3 |
| T9 | Notificaciones internas | ✅ Completo | 6 |
| T10 | API REST v1 (Sanctum) | ✅ Completo | 9 |

**Total: 59 pruebas, 617 aserciones, todas en verde** contra MySQL real
(`clinica_odontologica`).

---

## Cómo ejecutar las pruebas

```powershell
cd C:\laragon\www\UTIC\ODONTOLOGIA\CLINICA_ODONTOLOGICA
php artisan test --configuration=phpunit.mysql.xml
```

`phpunit.mysql.xml` usa **MySQL** (no SQLite) porque el esquema depende de claves
foráneas compuestas y columnas generadas que SQLite no soporta. Todas las
pruebas usan `DatabaseTransactions`: cada test se revierte.

> El `phpunit.xml` original sigue apuntando a SQLite en memoria y **no puede
> ejecutar la suite**; se conserva por compatibilidad.

---

## T1 · Insumos y solicitudes

**Qué faltaba realmente:** el modal de `solicitudes-insumos` era una copia del
modal del catálogo de insumos. El botón "Nueva Solicitud" no hacía nada porque
`window.SolicitudModal` no existía. Además, `insumos_solicitados.remision_id`
era `NOT NULL`, así que **crear una solicitud fallaba** siempre.

**Implementado:**
- `database/migrations/2026_10_02_120000_make_remision_id_nullable_in_insumos_solicitados_table.php`
  — `remision_id` pasa a ser opcional (una solicitud directa no tiene remisión).
- `resources/views/solicitudes-insumos/partials/modal.blade.php` — reescrito:
  filas dinámicas de insumos, autocomplete con debounce de 300 ms y
  `AbortController`, resumen de costo estimado y errores por campo.
- `SolicitudInsumoController` — `update()` devuelve 422 (antes 500) si la
  solicitud no es editable; `entregar()` consolida detalles repetidos y valida
  que no se entregue más de lo solicitado ni más que el stock.
- Endpoint `GET /solicitudes-insumos/buscar-tratamientos`.
- Entrada "Solicitudes de insumos" en el menú lateral, con `exclude` para que
  "Insumos" no quede marcado como activo en esa pantalla.

**Flujo verificado:** estudiante crea (pendiente) → docente autoriza
(autorizado) → encargado entrega (entregado) y el stock se descuenta.

---

## T2 · Kardex

**Aviso importante:** el requerimiento proponía
`LEFT JOIN cajas c ON c.paciente_id = p.id`, pero **`cajas` no tiene
`paciente_id`** (tiene `ci` y `paciente_nombre_completo`). Esa consulta falla
contra el esquema real. También `SUM(DISTINCT ...)` duplica o colapsa importes.

**Implementado:** `app/Http/Controllers/KardexController.php` +
`resources/views/kardex/{index,partials/filas,partials/modal}.blade.php`, con
**subconsultas correlacionadas** para los totales y la asociación de pagos por
la cadena `cajas → caja_detalles → tratamientos_realizados →
historias_clinicas → paciente` (con respaldo por `ci`). Filtros por nombre/CI,
periodo y clínica. Sólo lectura: `store/update/destroy` responden 405/403.

> El rol `kardex` no tiene el permiso `reportes.ver`, que es el que usa el
> módulo. Asígnelo desde **Roles y permisos**, o ejecute
> `php artisan db:seed --class=RoleHasPermissionsSeeder`.

---

## T3 · Reportes con gráficos

`ReporteController` (13 endpoints JSON), `resources/views/reportes/index.blade.php`
y los partials `graficos` e `imprimible`.

| Reporte | Endpoint |
|---|---|
| Pacientes por periodo | `GET /reportes/pacientes-por-periodo` |
| Pacientes por especialidad | `GET /reportes/pacientes-por-especialidad` |
| Pacientes por grupo etario | `GET /reportes/pacientes-por-edad` |
| Pacientes por sexo | `GET /reportes/pacientes-por-sexo` |
| Motivos de consulta | `GET /reportes/motivos-consulta` |
| Tratamientos por especialidad | `GET /reportes/tratamientos-por-especialidad` |
| Atenciones por docente | `GET /reportes/atenciones-por-docente` |
| Atenciones por estudiante | `GET /reportes/atenciones-por-estudiante` |
| Insumos utilizados | `GET /reportes/insumos-utilizados` |
| Radiografías por mes | `GET /reportes/radiografias-por-mes` |
| Esterilizaciones por mes | `GET /reportes/esterilizaciones-por-mes` |
| Pagos por tipo | `GET /reportes/pagos-por-tipo` |
| Ingresos por mes | `GET /reportes/ingresos-por-mes` |

**Exportaciones:** `GET /reportes/exportar/csv` (CSV con BOM, separador `;` para
Excel en español) y `GET /reportes/exportar/imprimible` (documento autónomo con
`@media print` y `window.print()`).

**Limitación real:** `cajas` y `radiologias` **no tienen `periodo_id`**, por lo
que los reportes de pagos/ingresos/radiografías no aplican ese filtro.

---

## T4 · Investigación

`InvestigacionController` + `resources/views/investigacion/*`.

- Sólo incluye admisiones con `consentimiento_investigacion = 1`.
- Anonimización: `substr(hash('sha256', $pacienteId . config('app.key')), 0, 12)`.
  **Nunca** se devuelven nombre, CI, apellidos ni el id real (hay una prueba que
  lo verifica explícitamente).
- Filtros por año, clínica, rango de edad, género y motivo.
- `GET /investigacion/estadisticas` → media, mediana, moda, min, max, desviación.
- `GET /investigacion/exportar` → CSV UTF-8 con BOM.
- Cada consulta del dataset se registra en `bitacoras` (tipo `investigacion`).

---

## T5 · Archivos, firma y PDF

### 5.1 Archivos adjuntos ✅

- `GET|POST /historias/{id}/archivos`, `GET|DELETE /historias/{id}/archivos/{archivo}`.
- Tipos permitidos: PDF, JPG, PNG. Máximo 10 MB.
- Guardado en `storage/app/public/patients/{paciente_id}/` **y** respaldo del
  binario en `historias_clinicas.archivo`.
- Hash **SHA-256** para evitar duplicados (la columna real es `hash varchar(128)`;
  el requerimiento pedía MD5). El segundo intento con el mismo contenido
  devuelve 422.
- Galería con vista previa: imágenes en línea y PDF embebido.

### 5.2 Firma digital ✅

- `resources/views/components/firma-digital.blade.php`: **canvas nativo, sin
  librerías externas** (funciona sin conexión, a diferencia de un CDN).
- Alta resolución (`devicePixelRatio`), ratón y táctil, botón de limpiar.
- Se guarda como data URL PNG en `admisiones.firma`.
- Límite de 2.000.000 caracteres para no desbordar la columna.

### 5.3 PDF de la historia clínica 🟡 PENDIENTE

No se pudo implementar: **este entorno no tiene acceso de red** (falla TLS del
sandbox), así que no es posible ejecutar `composer require`. El paquete no está
instalado:

```
vendor/barryvdh  → no existe
vendor/dompdf    → no existe
```

Mientras tanto puede usar `GET /reportes/exportar/imprimible`, que genera un
documento imprimible (y desde el navegador se guarda como PDF).

**Comando para habilitarlo (ejecútelo usted, requiere internet):**

```powershell
cd C:\laragon\www\UTIC\ODONTOLOGIA\CLINICA_ODONTOLOGICA
composer require barryvdh/laravel-dompdf
```

Después de eso se puede añadir `GET /historias/{id}/pdf` con
`Pdf::loadView(...)->download(...)`.

---

## T6 · Odontograma interactivo

- `resources/views/components/odontograma.blade.php` + partial de JS.
- 32 piezas en notación FDI, 5 caras por pieza (O, M, D, V, L) → 160 polígonos.
- 8 estados con los colores pedidos: sano (blanco), caries (rojo), obturado
  (azul), ausente (gris tachado), corona (dorado), endodoncia (morado),
  sellante (verde), fractura (naranja).
- Clic en una cara cicla el estado; clic derecho devuelve la pieza a sano.
- Se guarda como JSON en `admisiones.odontograma_pintado`, saneado en el
  backend (sólo piezas FDI 11–48 y caras conocidas).
- Cada cambio queda registrado en `bitacoras` (tipo `odontograma`).

---

## T7 · SHICO ⛔ BLOQUEADO

La tabla `shico_sincronizacion` existe (vacía). No se implementó porque falta
información que no se puede inventar:

1. URL base de la API de SHICO.
2. Esquema de autenticación (token, usuario/clave, IP permitida).
3. Endpoints y formato de intercambio para pacientes, tratamientos y cajas.
4. Frecuencia de sincronización esperada.

Con esos datos, el trabajo es: `app/Services/ShicoService.php` + comando
`shico:sync` + reintentos con estado en `shico_sincronizacion`.

---

## T8 · Dashboard con métricas reales

- `app/Services/DashboardMetrics.php` resuelve las tarjetas **según el rol**
  (15 roles) y tolera tablas vacías.
- `resources/views/dashboard.blade.php` reescrito: usa las clases del sistema
  (antes tenía clases de Tailwind que no existen en el layout).
- Gráficos: admisiones por mes (12 meses) y top de especialidades.
- Tablas: actividad reciente desde `bitacoras` y próximos vencimientos de
  periodos (30 días).
- `GET /dashboard/stats` permite refrescar sin recargar.

---

## T9 · Notificaciones internas

- Tabla `notificaciones` (migración `2026_10_02_130000`).
- `App\Services\NotificacionService` es el punto único de creación y **nunca
  interrumpe** el flujo que lo invoca.
- Disparadores conectados: solicitud creada → docentes; autorizada →
  estudiante y encargados de insumos; rechazada → estudiante; entregada →
  estudiante; stock en o bajo el mínimo → insumos/admin (sin repetir el aviso).
- Campana del topbar con contador real, carga diferida al abrirla y marcado
  como leída al hacer clic.
- Bandeja completa en `GET /notificaciones` con filtros (todas / sin leer / leídas).
- Cada usuario sólo ve y modifica **sus** notificaciones.

---

## T10 · API REST v1

Se habilitó `routes/api.php` en `bootstrap/app.php` (estaba comentado, por lo que
la API no existía).

| Método | Endpoint | Permiso |
|---|---|---|
| POST | `/api/v1/login` | público |
| GET | `/api/v1/me` | autenticado |
| POST | `/api/v1/logout` · `/logout-all` | autenticado |
| GET | `/api/v1/dashboard` | autenticado |
| GET | `/api/v1/pacientes` · `/pacientes/{id}` | `pacientes.ver` |
| GET | `/api/v1/historias` · `/historias/{id}` | `historias.ver` |
| POST | `/api/v1/historias` | `historias.editar` |
| GET | `/api/v1/insumos` | `insumos.ver` |
| GET | `/api/v1/solicitudes-insumos` | `insumos.ver` |
| POST | `/api/v1/solicitudes-insumos` | `insumos.crear` |
| GET | `/api/v1/notificaciones` | autenticado |
| POST | `/api/v1/notificaciones/{id}/leer` | autenticado |

Respuestas mediante **Recursos Eloquent**
(`app/Http/Resources/*Resource.php`). El login acepta `device_name` y revoca
los tokens previos si el usuario no tiene `multilogin`.

---

## Otros arreglos importantes

| Problema | Solución |
|---|---|
| `tests/TestCase.php` no arrancaba la aplicación: **ninguna prueba podía correr** | Se añadió `createApplication()` |
| `tempnam()` en Windows hacía fallar la compilación de vistas (Laravel lo convertía en excepción) | `app/Support/FilesystemTolerante.php` registrado en `AppServiceProvider` |
| El layout no tenía utilidad `.sr-only` ni estilos para notificaciones, archivos, odontograma ni firma | Añadidos al bloque `<style>` del layout |
| El menú marcaba "Insumos" como activo en la pantalla de solicitudes | Clave `exclude` en `MenuBuilder` + respeto en sidebar y breadcrumb |

## Convenciones respetadas

- Trait `HasCrudAjax` reutilizado (no se redefinen `index/tabla/datos/store/update/destroy`).
- Respuestas AJAX con el formato exacto `{filas, paginacion, total}` y
  `{success, es_edicion, registro, id}`.
- Vistas Blade con `@section` y JS autocontenido en `@push('scripts')`.
- Sin Bootstrap ni Tailwind en las vistas nuevas; sólo clases del sistema.
- Permisos Spatie en rutas (`permission:modulo.accion`) y en vistas (`@can`), y
  validación también en el backend.
- Textos e interfaz en español.
