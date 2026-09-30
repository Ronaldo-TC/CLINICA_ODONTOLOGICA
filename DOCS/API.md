# API del Backend Odontológico

## 1. Base URL

Todas las rutas de la API se exponen bajo:

- /api

## 2. Autenticación

El sistema usa Sanctum con token Bearer.

### Header requerido

```http
Authorization: Bearer <token>
```

## 3. Endpoints públicos

### POST /api/login

Autentica un usuario por username y password.

#### Body ejemplo

```json
{
    "username": "admin",
    "password": "123"
}
```

#### Respuesta exitosa

```json
{
    "success": true,
    "message": "Login exitoso.",
    "data": {
        "user": {
            "usernumber": 1,
            "username": "admin",
            "userfullname": "Administrador",
            "usertype": "admin",
            "userdesc": ""
        },
        "token": "<token>",
        "token_type": "Bearer",
        "dashboard": {
            "route": "/dashboard/admin",
            "name": "Panel de Administración"
        }
    }
}
```

### POST /api/logout

Cierra la sesión del usuario autenticado.

### GET /api/me

Devuelve la información del usuario autenticado.

---

## 4. Endpoints protegidos por rol

## 4.1 Administrador

Prefijo: `/api/admin`

### GET /api/admin/dashboard

Dashboard administrativo con:

- sesión actual
- rango de fechas
- remisiones por clínica
- contadores globales
- alertas del sistema

### GET /api/admin/stats/remissions

Estadísticas de remisiones por clínica.

### GET /api/admin/stats/boxes

Estadísticas de cajas y costos por clínica.

### GET /api/admin/stats/supplies

Estadísticas de insumos solicitados y stock.

### GET /api/admin/clinicals

Lista clínicas disponibles.

### GET /api/admin/specialties

Lista especialidades y asignaciones entre usuarios y clínicas.

### GET /api/admin/logs

Lista logs del sistema con filtros opcionales:

- logtype
- loguser
- logstatus
- from
- to
- per_page

### GET /api/admin/logs/{lognumber}

Obtiene un log en detalle.

### GET /api/admin/reports/daily

Reporte del día actual o del indicado por `date`.

### GET /api/admin/reports/range

Reporte por rango de fechas con `from` y `to`.

### Usuarios

### GET /api/admin/users

Listado paginado de usuarios.

Filtros:

- search
- usertype
- enabled
- per_page

### POST /api/admin/users

Crea un usuario.

### GET /api/admin/users/{usernumber}

Obtiene un usuario.

### PUT /api/admin/users/{usernumber}

Actualiza un usuario.

### DELETE /api/admin/users/{usernumber}

Deshabilita un usuario de forma lógica.

### PATCH /api/admin/users/{usernumber}/toggle

Activa o desactiva un usuario.

### POST /api/admin/users/{usernumber}/password

Cambia la contraseña de un usuario.

### GET /api/admin/users/{usernumber}/specialties

Lista especialidades del usuario.

### Especialidades

### POST /api/admin/specialties/enroll

Inscribe un usuario en una clínica/especialidad.

### DELETE /api/admin/specialties/{userid}/{clinicalid}

Elimina la inscripción.

### POST /api/admin/specialties/responsible

Asigna responsable de una clínica.

### GET /api/admin/specialties/clinical/{clinicalid}/responsible

Consulta el responsable de una clínica.

---

## 4.2 Docente

Prefijo: `/api/docente`

### GET /api/docente/dashboard

Devuelve resumen docente con:

- historias por revisar
- historias revisadas
- insumos por autorizar

### GET /api/docente/historias

Lista historias clínicas asignadas al docente autenticado.

### POST /api/docente/historias/{id}/revisar

Marca una historia como revisada.

#### Body ejemplo

```json
{
    "reviewstatus": true,
    "reviewteacher": "Dr. García"
}
```

---

## 4.3 Estudiante

Prefijo: `/api/estudiante`

### GET /api/estudiante/dashboard

Panel del estudiante.

---

## 4.4 Cajas

Prefijo: `/api/cajas`

### GET /api/cajas/dashboard

Dashboard de caja con totales del usuario actual.

### GET /api/cajas

Lista cajas registradas de forma paginada.

### POST /api/cajas

Registra una operación en caja.

#### Body ejemplo

```json
{
    "boxreceipt": 1001,
    "boxamount": 2,
    "boxtotalcost": 150.0,
    "boxci": 12345678,
    "boxpatientfullname": "Ana Pérez",
    "treatmentid": 1,
    "clinicalid": 2,
    "remissionid": 3,
    "boxtypepayment": "Efectivo"
}
```

---

## 4.5 Esterilización

Prefijo: `/api/esterilizacion`

### GET /api/esterilizacion/dashboard

Panel del módulo de esterilización.

---

## 4.6 Insumos

Prefijo: `/api/insumos`

### GET /api/insumos/dashboard

Panel del módulo de insumos.

---

## 4.7 Radiología

Prefijo: `/api/radiologia`

### GET /api/radiologia/dashboard

Panel del módulo de radiología.

---

## 4.8 Subadmin

Prefijo: `/api/subadmin`

### GET /api/subadmin/dashboard

Panel del subadministrador.

---

## 5. Respuestas estándar

Las respuestas suelen seguir este formato:

```json
{
    "success": true,
    "message": "Operación realizada correctamente.",
    "data": {}
}
```

En errores de validación o permisos:

```json
{
    "success": false,
    "message": "Mensaje descriptivo.",
    "role": "admin"
}
```

## 6. Códigos HTTP principales

- 200 OK
- 201 Created
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 422 Unprocessable Entity

## 7. Recomendaciones

- Incluir el token en cada request autenticada.
- Enviar fechas como `YYYY-MM-DD` cuando se usen filtros.
- Revisar la validación del rol antes de consumir un módulo.
- Mantener el formato JSON en todas las peticiones.

## 8. Nota de implementación

Algunos módulos de rol tienen endpoints definidos en rutas pero sus controladores aún están vacíos o incompletos. Esta documentación refleja el diseño esperado y la estructura real que existe en el código actual.
