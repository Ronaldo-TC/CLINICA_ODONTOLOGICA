# Documentación del Backend de Odontología

## 1. Descripción general

Este proyecto es un backend desarrollado en Laravel para gestionar una plataforma odontológica con autenticación por token, control de roles y administración de usuarios, clínicas, historias clínicas, remisiones, stock, cajas y reportes.

La estructura se basa en una API REST con Laravel 13, usando Sanctum para autenticación y middleware personalizado para validación de roles.

## 2. Stack tecnológico

- PHP 8.3
- Laravel 13.x
- Sanctum
- MySQL / base relacional compatible con Laravel
- PHPUnit para pruebas
- Vite y npm para assets front-end (si se utiliza en el proyecto)

## 3. Estructura principal del proyecto

- app/
    - Http/Controllers/Api/: controladores de la API
    - Http/Middleware/CheckRole.php: middleware de permisos por rol
    - Http/Requests/: validaciones de entrada
    - Models/: modelos Eloquent de dominio
- routes/api.php: definición de endpoints
- database/migrations/: migraciones de base de datos
- database/seeders/: sembradores
- tests/: pruebas automatizadas
- DOCS/: documentación del proyecto

## 4. Modelo de negocio

El sistema contempla distintos perfiles de usuario con acceso a módulos específicos:

- admin
- teacher
- student
- cashier
- sterilization
- supplies
- radiology
- subadmin

Los roles se manejan con middleware `role` y están definidos en `bootstrap/app.php`.

## 5. Autenticación

La autenticación se realiza mediante Sanctum.

### Endpoints principales

- POST /api/login
- POST /api/logout
- GET /api/me

El login valida usuario, estado del usuario, contraseña SHA-256 y genera un token Bearer.

## 6. Módulos principales

### Administración

Los endpoints de administración están bajo `/api/admin`:

- Dashboard general y estadísticas
- Gestión de usuarios
- Gestión de especialidades y responsables
- Listado de clínicas
- Logs del sistema
- Reportes diarios y por rango

### Docente

- GET /api/docente/dashboard
- GET /api/docente/historias
- POST /api/docente/historias/{id}/revisar

### Estudiante

- GET /api/estudiante/dashboard

### Cajas

- GET /api/cajas/dashboard
- GET /api/cajas
- POST /api/cajas

### Esterilización

- GET /api/esterilizacion/dashboard

### Insumos

- GET /api/insumos/dashboard

### Radiología

- GET /api/radiologia/dashboard

### Subadmin

- GET /api/subadmin/dashboard

## 7. Modelos más relevantes

- User: usuarios del sistema
- Clinical: clínicas / especialidades de atención
- Specialty: relación entre usuario y clínica
- Patient: pacientes
- PatientAdmission: admisiones / atención
- Remission: remisiones asociadas a una admisión
- ClinicHistory: historias clínicas
- Treatment: tratamientos
- Supplies: insumos
- SuppliesRequested: solicitudes de insumos
- Box: registros de caja
- Log: auditoría de eventos del sistema

## 8. Convenciones importantes

- El usuario usa `usernumber` como identificador principal.
- La contraseña no se guarda en texto plano, sino con hash SHA-256.
- El sistema usa `updatetime` como campo de auditoría.
- Los usuarios pueden estar deshabilitados con `userenabled` y `userpermitlogin`.
- Los usuarios no se eliminan físicamente; se deshabilitan con lógica de borrado.

## 9. Observaciones del estado actual

El proyecto ya tiene la base funcional de rutas y autenticación. Sin embargo, algunos controladores de módulos específicos están todavía en estructura mínima o vacía:

- EsterilizacionController
- InsumoController
- RadiologiaController
- EstudianteController
- SubadminController

Esto indica que la lógica de negocio de esos módulos está incompleta o aún no implementada.

## 10. Documentación detallada

- Ver [API.md](API.md) para el listado completo de endpoints y ejemplos.

## 11. Sugerencias de uso

1. Configurar variables de entorno con Laravel.
2. Ejecutar migraciones.
3. Crear usuarios con los roles permitidos.
4. Usar el endpoint `/api/login` y enviar el token en el header `Authorization: Bearer <token>`.
5. Validar el rol antes de abrir módulos específicos.

## 12. Resumen

Este backend está orientado a una clínica odontológica con control de acceso por roles y operaciones administrativas. La base de autenticación, rutas, validaciones y paneles principales ya están definidos, mientras que algunos módulos específicos requieren continuidad en su desarrollo.
