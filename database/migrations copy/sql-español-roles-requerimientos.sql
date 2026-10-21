-- ============================================================
-- BASE DE DATOS: Sistema Clínico Odontológico - UPEA
-- Motor: PostgreSQL
-- Esquema: public
-- Versión: Español con columnas limpias + tablas faltantes
--          + limpieza automática de DEFAULT ''
-- ============================================================

SET client_encoding = 'UTF8';
SET search_path TO public;

-- ============================================================
-- 0. MÓDULO DE SEGURIDAD: ROLES Y PERMISOS
-- ============================================================

CREATE TABLE public.roles (
    id                  integer      NOT NULL,
    nombre              varchar(50)  NOT NULL,
    descripcion         varchar(200) DEFAULT '',
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.roles OWNER TO postgres;
CREATE SEQUENCE public.roles_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;
ALTER TABLE ONLY public.roles
    ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq');
ALTER TABLE ONLY public.roles
    ADD CONSTRAINT rol_pkey PRIMARY KEY (id);
CREATE UNIQUE INDEX rol_index ON public.roles USING btree (id);
CREATE UNIQUE INDEX rol_index2 ON public.roles USING btree (nombre);


CREATE TABLE public.permisos (
    id                  integer      NOT NULL,
    codigo              varchar(50)  NOT NULL,
    descripcion         varchar(200) DEFAULT '',
    modulo              varchar(50)  NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.permisos OWNER TO postgres;
CREATE SEQUENCE public.permisos_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.permisos_id_seq OWNED BY public.permisos.id;
ALTER TABLE ONLY public.permisos
    ALTER COLUMN id SET DEFAULT nextval('public.permisos_id_seq');
ALTER TABLE ONLY public.permisos
    ADD CONSTRAINT permiso_pkey PRIMARY KEY (id);
CREATE UNIQUE INDEX permiso_index ON public.permisos USING btree (id);
CREATE UNIQUE INDEX permiso_index2 ON public.permisos USING btree (codigo);


CREATE TABLE public.rol_permiso (
    rol_id              integer NOT NULL,
    permiso_id          integer NOT NULL,
    habilitado          boolean DEFAULT true NOT NULL,
    fecha_actualizacion integer DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.rol_permiso OWNER TO postgres;
ALTER TABLE ONLY public.rol_permiso
    ADD CONSTRAINT rol_permiso_pkey PRIMARY KEY (rol_id, permiso_id);
CREATE INDEX rol_permiso_index ON public.rol_permiso USING btree (rol_id, permiso_id);


CREATE TABLE public.usuario_rol (
    usuario_id          integer NOT NULL,
    rol_id              integer NOT NULL,
    fecha_asignacion    integer DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.usuario_rol OWNER TO postgres;
ALTER TABLE ONLY public.usuario_rol
    ADD CONSTRAINT usuario_rol_pkey PRIMARY KEY (usuario_id, rol_id);
CREATE INDEX usuario_rol_index ON public.usuario_rol USING btree (usuario_id, rol_id);


CREATE TABLE public.sesiones (
    id                  integer      NOT NULL,
    usuario_id          integer      NOT NULL,
    token               varchar(255) NOT NULL,
    ip                  varchar(45),
    user_agent          varchar(300),
    inicio              integer      DEFAULT date_part('epoch', now()) NOT NULL,
    ultimo_acceso       integer      DEFAULT date_part('epoch', now()) NOT NULL,
    fin                 integer      DEFAULT -1 NOT NULL,
    activa              boolean      DEFAULT true NOT NULL
);
ALTER TABLE public.sesiones OWNER TO postgres;
CREATE SEQUENCE public.sesiones_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.sesiones_id_seq OWNED BY public.sesiones.id;
ALTER TABLE ONLY public.sesiones
    ALTER COLUMN id SET DEFAULT nextval('public.sesiones_id_seq');
ALTER TABLE ONLY public.sesiones
    ADD CONSTRAINT sesion_pkey PRIMARY KEY (id);
CREATE UNIQUE INDEX sesion_index ON public.sesiones USING btree (token);
CREATE INDEX sesion_index2 ON public.sesiones USING btree (usuario_id, activa);


-- ============================================================
-- 1. MÓDULO DE USUARIOS Y SEGURIDAD
-- ============================================================

CREATE TABLE public.usuarios (
    id                  integer       NOT NULL,
    ci                  varchar(50)   NOT NULL,
    nombre_usuario      varchar(20)   NOT NULL,
    nombre_completo     varchar(200)  NOT NULL,
    descripcion         varchar(300),
    tipo                varchar(20)   NOT NULL,
    habilitado          boolean       DEFAULT true NOT NULL,
    permitir_login      boolean       DEFAULT true NOT NULL,
    multilogin          boolean       DEFAULT false NOT NULL,
    contrasena          varchar(200)  DEFAULT '',
    ip                  varchar(300),
    ultimo_login        integer,
    sesion              varchar(50)   DEFAULT '',
    sesion_extra        varchar(50)   DEFAULT '',
    ultimo_logout       integer,
    ip_permitida        varchar(300),
    info                varchar(300)  DEFAULT '',
    fecha_actualizacion integer       DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.usuarios OWNER TO postgres;
CREATE SEQUENCE public.usuarios_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.usuarios_id_seq OWNED BY public.usuarios.id;
ALTER TABLE ONLY public.usuarios
    ALTER COLUMN id SET DEFAULT nextval('public.usuarios_id_seq');
ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id);
CREATE UNIQUE INDEX usuario_index  ON public.usuarios USING btree (id);
CREATE UNIQUE INDEX usuario_index2 ON public.usuarios USING btree (ci, nombre_usuario);


CREATE TABLE public.bitacoras (
    id                  integer      NOT NULL,
    usuario_id          integer,
    ip                  varchar(20)  NOT NULL,
    fecha               integer      NOT NULL,
    tipo                varchar(20)  NOT NULL,
    datos               text         NOT NULL,
    estado              varchar(20)  DEFAULT ''
);
ALTER TABLE public.bitacoras OWNER TO postgres;
CREATE SEQUENCE public.bitacoras_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.bitacoras_id_seq OWNED BY public.bitacoras.id;
ALTER TABLE ONLY public.bitacoras
    ALTER COLUMN id SET DEFAULT nextval('public.bitacoras_id_seq');
ALTER TABLE ONLY public.bitacoras
    ADD CONSTRAINT bitacora_pkey PRIMARY KEY (id);
CREATE INDEX bitacora_index ON public.bitacoras USING btree (usuario_id, fecha);


-- ============================================================
-- 2. MÓDULO DE ESPECIALIDADES CLÍNICAS
-- ============================================================

CREATE TABLE public.clinicas (
    id                  integer      NOT NULL,
    modulo              varchar(200) DEFAULT '',
    especialidad        varchar(200) DEFAULT '',
    curso               integer      DEFAULT -1,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.clinicas OWNER TO postgres;
CREATE SEQUENCE public.clinicas_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.clinicas_id_seq OWNED BY public.clinicas.id;
ALTER TABLE ONLY public.clinicas
    ALTER COLUMN id SET DEFAULT nextval('public.clinicas_id_seq');
ALTER TABLE ONLY public.clinicas
    ADD CONSTRAINT clinica_pkey PRIMARY KEY (id);
CREATE UNIQUE INDEX clinica_index  ON public.clinicas USING btree (id);
CREATE UNIQUE INDEX clinica_index2 ON public.clinicas USING btree (especialidad varchar_ops);


CREATE TABLE public.especialidades (
    usuario_id          integer  NOT NULL,
    clinica_id          integer  NOT NULL,
    habilitado          boolean  DEFAULT true NOT NULL,
    rotacion            integer  DEFAULT -1,
    responsable_admision boolean DEFAULT false NOT NULL,
    fecha_inicio        integer  DEFAULT date_part('epoch', now()) NOT NULL,
    fecha_actualizacion integer  DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.especialidades OWNER TO postgres;
ALTER TABLE ONLY public.especialidades
    ADD CONSTRAINT especialidad_pkey PRIMARY KEY (usuario_id, clinica_id);
CREATE INDEX especialidad_index ON public.especialidades USING btree (usuario_id, clinica_id);


-- ============================================================
-- 3. MÓDULO DE PERIODOS ACADÉMICOS
-- ============================================================

CREATE TABLE public.periodos (
    id                  integer      NOT NULL,
    nombre              varchar(100) NOT NULL,
    gestion             integer      NOT NULL,
    fecha_inicio        integer      NOT NULL,
    fecha_fin           integer      NOT NULL,
    habilitado          boolean      DEFAULT true NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.periodos OWNER TO postgres;
CREATE SEQUENCE public.periodos_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.periodos_id_seq OWNED BY public.periodos.id;
ALTER TABLE ONLY public.periodos
    ALTER COLUMN id SET DEFAULT nextval('public.periodos_id_seq');
ALTER TABLE ONLY public.periodos
    ADD CONSTRAINT periodo_pkey PRIMARY KEY (id);
CREATE UNIQUE INDEX periodo_index ON public.periodos USING btree (id);
CREATE UNIQUE INDEX periodo_index2 ON public.periodos USING btree (gestion);


-- ============================================================
-- 4. MÓDULO DE PACIENTES
-- ============================================================

CREATE TABLE public.pacientes (
    id                  integer      NOT NULL,
    ci                  varchar(50)  NOT NULL,
    nombre              varchar(50)  NOT NULL,
    apellido_paterno    varchar(50)  NOT NULL,
    apellido_materno    varchar(50)  NOT NULL,
    genero              varchar(50)  NOT NULL,
    fecha_nacimiento    integer      NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.pacientes OWNER TO postgres;
CREATE SEQUENCE public.pacientes_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.pacientes_id_seq OWNED BY public.pacientes.id;
ALTER TABLE ONLY public.pacientes
    ALTER COLUMN id SET DEFAULT nextval('public.pacientes_id_seq');
ALTER TABLE ONLY public.pacientes
    ADD CONSTRAINT paciente_pkey PRIMARY KEY (id);


CREATE TABLE public.motivos_consulta (
    id                  integer      NOT NULL,
    nombre              varchar(200) NOT NULL,
    descripcion         varchar(300) DEFAULT '',
    habilitado          boolean      DEFAULT true NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.motivos_consulta OWNER TO postgres;
CREATE SEQUENCE public.motivos_consulta_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.motivos_consulta_id_seq OWNED BY public.motivos_consulta.id;
ALTER TABLE ONLY public.motivos_consulta
    ALTER COLUMN id SET DEFAULT nextval('public.motivos_consulta_id_seq');
ALTER TABLE ONLY public.motivos_consulta
    ADD CONSTRAINT motivo_consulta_pkey PRIMARY KEY (id);
CREATE UNIQUE INDEX motivo_consulta_index ON public.motivos_consulta USING btree (nombre);


CREATE TABLE public.admisiones (
    id                  integer      NOT NULL,
    paciente_id         integer      NOT NULL,
    periodo_id          integer      NOT NULL,
    motivo_consulta_id  integer,
    estado_civil        varchar(200) DEFAULT '',
    escuela             varchar(200) DEFAULT '',
    ocupacion           varchar(200) DEFAULT '',
    direccion           varchar(200) DEFAULT '',
    emergencia          varchar(200) DEFAULT '',
    telefono            integer      DEFAULT 0,
    motivo_consulta     text         DEFAULT '',
    antecedentes        text         DEFAULT '',
    gmh                 text         DEFAULT '',
    gpe                 text         DEFAULT '',
    signos_vitales      text         DEFAULT '',
    ee                  text         DEFAULT '',
    cn                  text         DEFAULT '',
    odontograma         text         DEFAULT '',
    odontograma_pintado text         DEFAULT '',
    atm                 text         DEFAULT '',
    bm                  text         DEFAULT '',
    gm                  text         DEFAULT '',
    calculo             varchar(50)  DEFAULT '',
    oclusion            varchar(50)  DEFAULT '',
    pieza_dental        varchar(200) DEFAULT '',
    diagnostico         text         DEFAULT '',
    examen              text         DEFAULT '',
    firma               text         DEFAULT '',
    consentimiento_datos boolean     DEFAULT false NOT NULL,
    consentimiento_investigacion boolean DEFAULT false NOT NULL,
    estudiante_id       integer      NOT NULL,
    especialidad_estudiante integer  NOT NULL,
    responsable_id      integer      NOT NULL,
    especialidad_responsable integer NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.admisiones OWNER TO postgres;
CREATE SEQUENCE public.admisiones_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.admisiones_id_seq OWNED BY public.admisiones.id;
ALTER TABLE ONLY public.admisiones
    ALTER COLUMN id SET DEFAULT nextval('public.admisiones_id_seq');
ALTER TABLE ONLY public.admisiones
    ADD CONSTRAINT admision_pkey PRIMARY KEY (id, paciente_id);
CREATE INDEX admision_index
    ON public.admisiones USING btree (id, paciente_id);
CREATE INDEX admision_periodo_index
    ON public.admisiones USING btree (periodo_id);


CREATE TABLE public.consentimientos (
    id                  integer      NOT NULL,
    admision_id         integer      NOT NULL,
    paciente_id         integer      NOT NULL,
    tipo                varchar(50)  NOT NULL,
    aceptado            boolean      DEFAULT false NOT NULL,
    observacion         text         DEFAULT '',
    fecha               integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.consentimientos OWNER TO postgres;
CREATE SEQUENCE public.consentimientos_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.consentimientos_id_seq OWNED BY public.consentimientos.id;
ALTER TABLE ONLY public.consentimientos
    ALTER COLUMN id SET DEFAULT nextval('public.consentimientos_id_seq');
ALTER TABLE ONLY public.consentimientos
    ADD CONSTRAINT consentimiento_pkey PRIMARY KEY (id);
CREATE INDEX consentimiento_index ON public.consentimientos USING btree (admision_id, paciente_id);


-- ============================================================
-- 5. MÓDULO DE REMISIONES Y CASOS CLÍNICOS
-- ============================================================

CREATE TABLE public.remisiones (
    id                  integer  NOT NULL,
    admision_id         integer  NOT NULL,
    paciente_id         integer  NOT NULL,
    clinica_id          integer  NOT NULL,
    periodo_id          integer  NOT NULL,
    habilitado          boolean  DEFAULT true NOT NULL,
    fecha_actualizacion integer  DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.remisiones OWNER TO postgres;
CREATE SEQUENCE public.remisiones_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.remisiones_id_seq OWNED BY public.remisiones.id;
ALTER TABLE ONLY public.remisiones
    ALTER COLUMN id SET DEFAULT nextval('public.remisiones_id_seq');
ALTER TABLE ONLY public.remisiones
    ADD CONSTRAINT remision_pkey PRIMARY KEY (id, admision_id, paciente_id, clinica_id);
CREATE INDEX remision_index
    ON public.remisiones USING btree (id, admision_id, paciente_id);
CREATE INDEX remision_periodo_index
    ON public.remisiones USING btree (periodo_id);


CREATE TABLE public.historias_clinicas (
    id                  integer      NOT NULL,
    admision_id         integer      NOT NULL,
    paciente_id         integer      NOT NULL,
    clinica_id          integer      NOT NULL,
    periodo_id          integer      NOT NULL,
    estado              varchar(50)  DEFAULT '',
    archivo_nombre      varchar(100) DEFAULT '',
    archivo             oid,
    archivo_hash        varchar(50),
    autorizado          boolean      DEFAULT false NOT NULL,
    estudiante_id       integer      NOT NULL,
    especialidad_estudiante integer  NOT NULL,
    docente_id          integer      NOT NULL,
    especialidad_docente integer     NOT NULL,
    revision_docente    text         DEFAULT '',
    revision_cualquiera boolean      DEFAULT false NOT NULL,
    estado_revision     boolean      DEFAULT false NOT NULL,
    fecha_inicio        integer      DEFAULT date_part('epoch', now()) NOT NULL,
    fecha_fin           integer      DEFAULT -1 NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.historias_clinicas OWNER TO postgres;
ALTER TABLE ONLY public.historias_clinicas
    ADD CONSTRAINT historia_clinica_pkey PRIMARY KEY (id);
CREATE INDEX historia_clinica_index
    ON public.historias_clinicas USING btree (id, paciente_id, estudiante_id);
CREATE INDEX historia_periodo_index
    ON public.historias_clinicas USING btree (periodo_id);


-- ============================================================
-- 6. MÓDULO DE TRATAMIENTOS Y CAJAS
-- ============================================================

CREATE TABLE public.tratamientos (
    id                  integer      NOT NULL,
    nombre              varchar(100) NOT NULL,
    costo               numeric(10,2) DEFAULT 0,
    descripcion         varchar(100) DEFAULT '',
    clinica_id          integer      NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.tratamientos OWNER TO postgres;
CREATE SEQUENCE public.tratamientos_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.tratamientos_id_seq OWNED BY public.tratamientos.id;
ALTER TABLE ONLY public.tratamientos
    ALTER COLUMN id SET DEFAULT nextval('public.tratamientos_id_seq');
ALTER TABLE ONLY public.tratamientos
    ADD CONSTRAINT tratamiento_pkey PRIMARY KEY (id, clinica_id);
CREATE INDEX tratamiento_index
    ON public.tratamientos USING btree (id, clinica_id);


CREATE TABLE public.tratamientos_realizados (
    id                  integer      NOT NULL,
    historia_clinica_id integer      NOT NULL,
    tratamiento_id      integer      NOT NULL,
    clinica_id          integer      NOT NULL,
    pieza_dental        varchar(20),
    estado              varchar(20)  DEFAULT 'pendiente',
    costo_cobrado       numeric(10,2) DEFAULT 0,
    observacion         text         DEFAULT '',
    fecha_inicio        integer      DEFAULT date_part('epoch', now()) NOT NULL,
    fecha_fin           integer      DEFAULT -1 NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.tratamientos_realizados OWNER TO postgres;
CREATE SEQUENCE public.tratamientos_realizados_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.tratamientos_realizados_id_seq
    OWNED BY public.tratamientos_realizados.id;
ALTER TABLE ONLY public.tratamientos_realizados
    ALTER COLUMN id SET DEFAULT nextval('public.tratamientos_realizados_id_seq');
ALTER TABLE ONLY public.tratamientos_realizados
    ADD CONSTRAINT tratamiento_realizado_pkey PRIMARY KEY (id);
CREATE INDEX tratamiento_realizado_index
    ON public.tratamientos_realizados USING btree (historia_clinica_id, tratamiento_id);


CREATE TABLE public.sesiones_atencion (
    id                      integer      NOT NULL,
    tratamiento_realizado_id integer     NOT NULL,
    fecha                   integer      DEFAULT date_part('epoch', now()) NOT NULL,
    descripcion             text         DEFAULT '',
    estudiante_id           integer      NOT NULL,
    docente_id              integer      NOT NULL,
    observacion_docente     text         DEFAULT '',
    autorizado              boolean      DEFAULT false NOT NULL,
    fecha_actualizacion     integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.sesiones_atencion OWNER TO postgres;
CREATE SEQUENCE public.sesiones_atencion_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.sesiones_atencion_id_seq
    OWNED BY public.sesiones_atencion.id;
ALTER TABLE ONLY public.sesiones_atencion
    ALTER COLUMN id SET DEFAULT nextval('public.sesiones_atencion_id_seq');
ALTER TABLE ONLY public.sesiones_atencion
    ADD CONSTRAINT sesion_atencion_pkey PRIMARY KEY (id);
CREATE INDEX sesion_atencion_index
    ON public.sesiones_atencion USING btree (tratamiento_realizado_id, fecha);


CREATE TABLE public.cajas (
    id                  integer      NOT NULL,
    recibo              integer      NOT NULL,
    monto               integer      NOT NULL,
    costo_total         numeric(10,2) DEFAULT 0,
    cancelado           numeric(10,2) DEFAULT 0,
    habilitada          boolean      DEFAULT true NOT NULL,
    descripcion         varchar(100),
    ci                  integer      DEFAULT 0,
    tipo                varchar(100),
    tipo_pago           varchar(100),
    paciente_nombre_completo varchar(100),
    grado               varchar(100),
    tratamiento_id      integer      NOT NULL,
    clinica_id          integer      NOT NULL,
    remision_id         integer,
    usuario_id          integer      NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.cajas OWNER TO postgres;
CREATE SEQUENCE public.cajas_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.cajas_id_seq OWNED BY public.cajas.id;
ALTER TABLE ONLY public.cajas
    ALTER COLUMN id SET DEFAULT nextval('public.cajas_id_seq');
ALTER TABLE ONLY public.cajas
    ADD CONSTRAINT caja_pkey PRIMARY KEY (id);
CREATE INDEX caja_index ON public.cajas USING btree (id);


CREATE TABLE public.caja_detalles (
    id                      integer      NOT NULL,
    caja_id                 integer      NOT NULL,
    tratamiento_realizado_id integer,
    concepto                varchar(255) NOT NULL,
    cantidad                numeric(8,2) DEFAULT 1,
    precio_unitario         numeric(10,2) DEFAULT 0,
    monto                   numeric(10,2) DEFAULT 0,
    fecha                   integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.caja_detalles OWNER TO postgres;
CREATE SEQUENCE public.caja_detalles_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.caja_detalles_id_seq OWNED BY public.caja_detalles.id;
ALTER TABLE ONLY public.caja_detalles
    ALTER COLUMN id SET DEFAULT nextval('public.caja_detalles_id_seq');
ALTER TABLE ONLY public.caja_detalles
    ADD CONSTRAINT caja_detalle_pkey PRIMARY KEY (id);
CREATE INDEX caja_detalle_index ON public.caja_detalles USING btree (caja_id);


-- ============================================================
-- 7. MÓDULO DE INSUMOS Y ESTERILIZACIÓN
-- ============================================================

CREATE TABLE public.insumos_tratamiento (
    id                  integer      NOT NULL,
    nombre              varchar(100) NOT NULL,
    descripcion         varchar(100) DEFAULT '',
    clinica_id          integer      NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.insumos_tratamiento OWNER TO postgres;
CREATE SEQUENCE public.insumos_tratamiento_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.insumos_tratamiento_id_seq
    OWNED BY public.insumos_tratamiento.id;
ALTER TABLE ONLY public.insumos_tratamiento
    ALTER COLUMN id SET DEFAULT nextval('public.insumos_tratamiento_id_seq');
ALTER TABLE ONLY public.insumos_tratamiento
    ADD CONSTRAINT insumo_tratamiento_pkey PRIMARY KEY (id, clinica_id);
CREATE INDEX insumo_tratamiento_index
    ON public.insumos_tratamiento USING btree (id, clinica_id);


CREATE TABLE public.insumos (
    id                      integer      NOT NULL,
    nombre                  varchar(100) NOT NULL,
    cantidad_numerica       numeric(10,2) DEFAULT 0,
    cantidad                varchar(100) DEFAULT '',
    stock_minimo            numeric(10,2) DEFAULT 0,
    costo_unitario          numeric(10,2) DEFAULT 0,
    insumo_tratamiento_id   integer      NOT NULL,
    clinica_id              integer      NOT NULL,
    fecha_actualizacion     integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.insumos OWNER TO postgres;
CREATE SEQUENCE public.insumos_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.insumos_id_seq OWNED BY public.insumos.id;
ALTER TABLE ONLY public.insumos
    ALTER COLUMN id SET DEFAULT nextval('public.insumos_id_seq');
ALTER TABLE ONLY public.insumos
    ADD CONSTRAINT insumo_pkey PRIMARY KEY (id);
CREATE INDEX insumo_index
    ON public.insumos USING btree (id, insumo_tratamiento_id, clinica_id);


CREATE TABLE public.insumos_solicitados (
    id                      integer  NOT NULL,
    pieza                   integer  NOT NULL,
    insumos                 text     DEFAULT '',
    docente_autoriza        integer  DEFAULT 0 NOT NULL,
    docente_id              integer,
    autorizado              boolean  DEFAULT false NOT NULL,
    insumo_tratamiento_id   integer  NOT NULL,
    clinica_id              integer  NOT NULL,
    remision_id             integer  NOT NULL,
    tratamiento_realizado_id integer,
    usuario_id              integer,
    estado                  varchar(20) DEFAULT 'pendiente',
    fecha_autorizacion      integer  DEFAULT -1,
    fecha_actualizacion     integer  DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.insumos_solicitados OWNER TO postgres;
CREATE SEQUENCE public.insumos_solicitados_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.insumos_solicitados_id_seq
    OWNED BY public.insumos_solicitados.id;
ALTER TABLE ONLY public.insumos_solicitados
    ALTER COLUMN id SET DEFAULT nextval('public.insumos_solicitados_id_seq');
ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_pkey PRIMARY KEY (id);
CREATE INDEX insumo_solicitado_index
    ON public.insumos_solicitados USING btree (id);


CREATE TABLE public.insumos_solicitados_detalle (
    id                      integer      NOT NULL,
    insumo_solicitado_id    integer      NOT NULL,
    insumo_id               integer      NOT NULL,
    cantidad_solicitada     numeric(10,2) NOT NULL,
    cantidad_entregada      numeric(10,2) DEFAULT 0,
    fecha                   integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.insumos_solicitados_detalle OWNER TO postgres;
CREATE SEQUENCE public.insumos_solicitados_detalle_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.insumos_solicitados_detalle_id_seq
    OWNED BY public.insumos_solicitados_detalle.id;
ALTER TABLE ONLY public.insumos_solicitados_detalle
    ALTER COLUMN id SET DEFAULT nextval('public.insumos_solicitados_detalle_id_seq');
ALTER TABLE ONLY public.insumos_solicitados_detalle
    ADD CONSTRAINT insumo_solicitado_detalle_pkey PRIMARY KEY (id);
CREATE INDEX insumo_solicitado_detalle_index
    ON public.insumos_solicitados_detalle USING btree (insumo_solicitado_id);


CREATE TABLE public.insumos_tratamiento_usados (
    id                      integer      NOT NULL,
    insumo_id               integer      NOT NULL,
    tratamiento_realizado_id integer     NOT NULL,
    cantidad_usada          numeric(10,2) DEFAULT 1,
    costo_al_momento        numeric(10,2) DEFAULT 0,
    fecha                   integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.insumos_tratamiento_usados OWNER TO postgres;
CREATE SEQUENCE public.insumos_tratamiento_usados_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.insumos_tratamiento_usados_id_seq
    OWNED BY public.insumos_tratamiento_usados.id;
ALTER TABLE ONLY public.insumos_tratamiento_usados
    ALTER COLUMN id SET DEFAULT nextval('public.insumos_tratamiento_usados_id_seq');
ALTER TABLE ONLY public.insumos_tratamiento_usados
    ADD CONSTRAINT insumo_tratamiento_usado_pkey PRIMARY KEY (id);
CREATE INDEX insumo_tratamiento_usado_index
    ON public.insumos_tratamiento_usados USING btree (insumo_id, tratamiento_realizado_id);


CREATE TABLE public.esterilizaciones (
    id                  integer      NOT NULL,
    cantidad_sobres     integer      NOT NULL,
    cantidad_paquetes   integer      NOT NULL,
    habilitada          boolean      DEFAULT true NOT NULL,
    info                varchar(200) DEFAULT '' NOT NULL,
    descripcion         varchar(200) DEFAULT '' NOT NULL,
    fecha_entrega       integer      DEFAULT -1 NOT NULL,
    fecha_recojo        integer      DEFAULT -1 NOT NULL,
    estudiante_id       integer,
    clinica_id          integer      NOT NULL,
    usuario_id          integer      NOT NULL,
    periodo_id          integer      NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.esterilizaciones OWNER TO postgres;
CREATE SEQUENCE public.esterilizaciones_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.esterilizaciones_id_seq OWNED BY public.esterilizaciones.id;
ALTER TABLE ONLY public.esterilizaciones
    ALTER COLUMN id SET DEFAULT nextval('public.esterilizaciones_id_seq');
ALTER TABLE ONLY public.esterilizaciones
    ADD CONSTRAINT esterilizacion_pkey PRIMARY KEY (id);
CREATE INDEX esterilizacion_index
    ON public.esterilizaciones USING btree (id);


CREATE TABLE public.autorizaciones (
    esterilizacion_id   integer      NOT NULL,
    remision_id         integer      NOT NULL,
    estado              boolean      DEFAULT false NOT NULL,
    descripcion         varchar(200) DEFAULT '' NOT NULL,
    docente             varchar(200) DEFAULT '' NOT NULL,
    sobre               integer      NOT NULL,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.autorizaciones OWNER TO postgres;
ALTER TABLE ONLY public.autorizaciones
    ADD CONSTRAINT autorizacion_pkey PRIMARY KEY (esterilizacion_id, remision_id);
CREATE INDEX autorizacion_index
    ON public.autorizaciones USING btree (esterilizacion_id, remision_id);


-- ============================================================
-- 8. MÓDULO DE RADIOLOGÍA
-- ============================================================

CREATE TABLE public.radiologias (
    id                  integer      NOT NULL,
    descripcion         varchar(100) NOT NULL,
    observacion         varchar(100) NOT NULL,
    estado              varchar(50)  NOT NULL,
    usuario_id          integer      NOT NULL,
    caja_id             integer      NOT NULL,
    paciente_id         integer,
    remision_id         integer,
    archivo_ruta        varchar(500) DEFAULT '',
    fecha_toma          integer      DEFAULT -1,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.radiologias OWNER TO postgres;
CREATE SEQUENCE public.radiologias_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.radiologias_id_seq OWNED BY public.radiologias.id;
ALTER TABLE ONLY public.radiologias
    ALTER COLUMN id SET DEFAULT nextval('public.radiologias_id_seq');
ALTER TABLE ONLY public.radiologias
    ADD CONSTRAINT radiologia_pkey PRIMARY KEY (id, usuario_id, caja_id);
CREATE INDEX radiologia_index
    ON public.radiologias USING btree (id, usuario_id, caja_id);


-- ============================================================
-- 9. MÓDULO DE ARCHIVOS (unificado)
-- ============================================================

CREATE TABLE public.archivos (
    id                  integer      NOT NULL,
    paciente_id         integer,
    historia_clinica_id integer,
    remision_id         integer,
    radiologia_id       integer,
    nombre_original     varchar(255),
    nombre_guardado     varchar(255),
    ruta                varchar(500),
    extension           varchar(20),
    tamano_bytes        bigint       DEFAULT 0,
    tipo                varchar(50),
    hash                varchar(128),
    usuario_id          integer,
    clinica_id          integer,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.archivos OWNER TO postgres;
CREATE SEQUENCE public.archivos_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.archivos_id_seq OWNED BY public.archivos.id;
ALTER TABLE ONLY public.archivos
    ALTER COLUMN id SET DEFAULT nextval('public.archivos_id_seq');
ALTER TABLE ONLY public.archivos
    ADD CONSTRAINT archivo_pkey PRIMARY KEY (id);
CREATE INDEX archivo_index ON public.archivos USING btree (paciente_id, historia_clinica_id);


-- ============================================================
-- 10. MÓDULO DE INTEGRACIÓN CON SHICO
-- ============================================================

CREATE TABLE public.shico_sincronizacion (
    id                  integer      NOT NULL,
    entidad             varchar(50)  NOT NULL,
    id_local            integer      NOT NULL,
    id_shico            varchar(100),
    estado              varchar(20)  DEFAULT 'pendiente',
    ultimo_intento      integer      DEFAULT -1,
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.shico_sincronizacion OWNER TO postgres;
CREATE SEQUENCE public.shico_sincronizacion_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.shico_sincronizacion_id_seq
    OWNED BY public.shico_sincronizacion.id;
ALTER TABLE ONLY public.shico_sincronizacion
    ALTER COLUMN id SET DEFAULT nextval('public.shico_sincronizacion_id_seq');
ALTER TABLE ONLY public.shico_sincronizacion
    ADD CONSTRAINT shico_sincronizacion_pkey PRIMARY KEY (id);
CREATE INDEX shico_sincronizacion_index
    ON public.shico_sincronizacion USING btree (entidad, id_local);


-- ============================================================
-- 11. MÓDULO DE REPORTES
-- ============================================================

CREATE TABLE public.reportes (
    id                  integer      NOT NULL,
    nombre              varchar(200) NOT NULL,
    tipo                varchar(50)  NOT NULL,
    parametros          text         DEFAULT '',
    ruta_archivo        varchar(500) DEFAULT '',
    usuario_id          integer      NOT NULL,
    fecha_generacion    integer      DEFAULT date_part('epoch', now()) NOT NULL
);
ALTER TABLE public.reportes OWNER TO postgres;
CREATE SEQUENCE public.reportes_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.reportes_id_seq OWNED BY public.reportes.id;
ALTER TABLE ONLY public.reportes
    ALTER COLUMN id SET DEFAULT nextval('public.reportes_id_seq');
ALTER TABLE ONLY public.reportes
    ADD CONSTRAINT reporte_pkey PRIMARY KEY (id);
CREATE INDEX reporte_index ON public.reportes USING btree (usuario_id, fecha_generacion);


-- ============================================================
-- 12. CLAVES FORÁNEAS
-- ============================================================

ALTER TABLE ONLY public.rol_permiso
    ADD CONSTRAINT rol_permiso_rol FOREIGN KEY (rol_id)
    REFERENCES public.roles(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.rol_permiso
    ADD CONSTRAINT rol_permiso_permiso FOREIGN KEY (permiso_id)
    REFERENCES public.permisos(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.usuario_rol
    ADD CONSTRAINT usuario_rol_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.usuario_rol
    ADD CONSTRAINT usuario_rol_rol FOREIGN KEY (rol_id)
    REFERENCES public.roles(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.sesiones
    ADD CONSTRAINT sesion_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.bitacoras
    ADD CONSTRAINT bitacora_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.especialidades
    ADD CONSTRAINT especialidad_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.especialidades
    ADD CONSTRAINT especialidad_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.admisiones
    ADD CONSTRAINT admision_paciente FOREIGN KEY (paciente_id)
    REFERENCES public.pacientes(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.admisiones
    ADD CONSTRAINT admision_periodo FOREIGN KEY (periodo_id)
    REFERENCES public.periodos(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.admisiones
    ADD CONSTRAINT admision_motivo FOREIGN KEY (motivo_consulta_id)
    REFERENCES public.motivos_consulta(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.admisiones
    ADD CONSTRAINT admision_estudiante FOREIGN KEY (estudiante_id, especialidad_estudiante)
    REFERENCES public.especialidades(usuario_id, clinica_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.admisiones
    ADD CONSTRAINT admision_responsable FOREIGN KEY (responsable_id, especialidad_responsable)
    REFERENCES public.especialidades(usuario_id, clinica_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.consentimientos
    ADD CONSTRAINT consentimiento_admision FOREIGN KEY (admision_id, paciente_id)
    REFERENCES public.admisiones(id, paciente_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.remisiones
    ADD CONSTRAINT remision_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.remisiones
    ADD CONSTRAINT remision_admision FOREIGN KEY (admision_id, paciente_id)
    REFERENCES public.admisiones(id, paciente_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.remisiones
    ADD CONSTRAINT remision_periodo FOREIGN KEY (periodo_id)
    REFERENCES public.periodos(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.historias_clinicas
    ADD CONSTRAINT historia_remision FOREIGN KEY (id, admision_id, paciente_id, clinica_id)
    REFERENCES public.remisiones(id, admision_id, paciente_id, clinica_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.historias_clinicas
    ADD CONSTRAINT historia_estudiante FOREIGN KEY (estudiante_id, especialidad_estudiante)
    REFERENCES public.especialidades(usuario_id, clinica_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.historias_clinicas
    ADD CONSTRAINT historia_docente FOREIGN KEY (docente_id, especialidad_docente)
    REFERENCES public.especialidades(usuario_id, clinica_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.historias_clinicas
    ADD CONSTRAINT historia_periodo FOREIGN KEY (periodo_id)
    REFERENCES public.periodos(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.tratamientos
    ADD CONSTRAINT tratamiento_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.tratamientos_realizados
    ADD CONSTRAINT tratamiento_realizado_historia FOREIGN KEY (historia_clinica_id)
    REFERENCES public.historias_clinicas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.tratamientos_realizados
    ADD CONSTRAINT tratamiento_realizado_tratamiento FOREIGN KEY (tratamiento_id, clinica_id)
    REFERENCES public.tratamientos(id, clinica_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.sesiones_atencion
    ADD CONSTRAINT sesion_atencion_tratamiento FOREIGN KEY (tratamiento_realizado_id)
    REFERENCES public.tratamientos_realizados(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.sesiones_atencion
    ADD CONSTRAINT sesion_atencion_estudiante FOREIGN KEY (estudiante_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.sesiones_atencion
    ADD CONSTRAINT sesion_atencion_docente FOREIGN KEY (docente_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.cajas
    ADD CONSTRAINT caja_tratamiento FOREIGN KEY (tratamiento_id, clinica_id)
    REFERENCES public.tratamientos(id, clinica_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.cajas
    ADD CONSTRAINT caja_historia FOREIGN KEY (remision_id)
    REFERENCES public.historias_clinicas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.cajas
    ADD CONSTRAINT caja_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.caja_detalles
    ADD CONSTRAINT caja_detalle_caja FOREIGN KEY (caja_id)
    REFERENCES public.cajas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.caja_detalles
    ADD CONSTRAINT caja_detalle_tratamiento FOREIGN KEY (tratamiento_realizado_id)
    REFERENCES public.tratamientos_realizados(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.insumos_tratamiento
    ADD CONSTRAINT insumo_tratamiento_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos
    ADD CONSTRAINT insumo_insumo_tratamiento FOREIGN KEY (insumo_tratamiento_id, clinica_id)
    REFERENCES public.insumos_tratamiento(id, clinica_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_insumo_tratamiento FOREIGN KEY (insumo_tratamiento_id, clinica_id)
    REFERENCES public.insumos_tratamiento(id, clinica_id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_historia FOREIGN KEY (remision_id)
    REFERENCES public.historias_clinicas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_docente FOREIGN KEY (docente_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_tratamiento FOREIGN KEY (tratamiento_realizado_id)
    REFERENCES public.tratamientos_realizados(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.insumos_solicitados_detalle
    ADD CONSTRAINT insumo_solicitado_detalle_solicitud FOREIGN KEY (insumo_solicitado_id)
    REFERENCES public.insumos_solicitados(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos_solicitados_detalle
    ADD CONSTRAINT insumo_solicitado_detalle_insumo FOREIGN KEY (insumo_id)
    REFERENCES public.insumos(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.insumos_tratamiento_usados
    ADD CONSTRAINT insumo_tratamiento_usado_insumo FOREIGN KEY (insumo_id)
    REFERENCES public.insumos(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.insumos_tratamiento_usados
    ADD CONSTRAINT insumo_tratamiento_usado_tratamiento FOREIGN KEY (tratamiento_realizado_id)
    REFERENCES public.tratamientos_realizados(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.esterilizaciones
    ADD CONSTRAINT esterilizacion_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.esterilizaciones
    ADD CONSTRAINT esterilizacion_estudiante FOREIGN KEY (estudiante_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.esterilizaciones
    ADD CONSTRAINT esterilizacion_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.esterilizaciones
    ADD CONSTRAINT esterilizacion_periodo FOREIGN KEY (periodo_id)
    REFERENCES public.periodos(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.autorizaciones
    ADD CONSTRAINT autorizacion_historia FOREIGN KEY (remision_id)
    REFERENCES public.historias_clinicas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.autorizaciones
    ADD CONSTRAINT autorizacion_esterilizacion FOREIGN KEY (esterilizacion_id)
    REFERENCES public.esterilizaciones(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.radiologias
    ADD CONSTRAINT radiologia_caja FOREIGN KEY (caja_id)
    REFERENCES public.cajas(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.radiologias
    ADD CONSTRAINT radiologia_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.radiologias
    ADD CONSTRAINT radiologia_paciente FOREIGN KEY (paciente_id)
    REFERENCES public.pacientes(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.radiologias
    ADD CONSTRAINT radiologia_remision FOREIGN KEY (remision_id)
    REFERENCES public.historias_clinicas(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.archivos
    ADD CONSTRAINT archivo_paciente FOREIGN KEY (paciente_id)
    REFERENCES public.pacientes(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.archivos
    ADD CONSTRAINT archivo_historia FOREIGN KEY (historia_clinica_id)
    REFERENCES public.historias_clinicas(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.archivos
    ADD CONSTRAINT archivo_remision FOREIGN KEY (remision_id)
    REFERENCES public.historias_clinicas(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.archivos
    ADD CONSTRAINT archivo_radiologia FOREIGN KEY (radiologia_id)
    REFERENCES public.radiologias(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.archivos
    ADD CONSTRAINT archivo_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.archivos
    ADD CONSTRAINT archivo_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ONLY public.reportes
    ADD CONSTRAINT reporte_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


-- ============================================================
-- 13. VISTAS DE APOYO PARA REPORTES
-- ============================================================

CREATE OR REPLACE VIEW public.vista_pacientes_grupo_etario AS
SELECT
    p.id,
    p.ci,
    p.nombre,
    p.apellido_paterno,
    p.apellido_materno,
    p.genero,
    date_part('year', age(to_timestamp(p.fecha_nacimiento))) AS edad,
    CASE
        WHEN date_part('year', age(to_timestamp(p.fecha_nacimiento))) < 6 THEN '0-5'
        WHEN date_part('year', age(to_timestamp(p.fecha_nacimiento))) < 13 THEN '6-12'
        WHEN date_part('year', age(to_timestamp(p.fecha_nacimiento))) < 18 THEN '13-17'
        WHEN date_part('year', age(to_timestamp(p.fecha_nacimiento))) < 30 THEN '18-29'
        WHEN date_part('year', age(to_timestamp(p.fecha_nacimiento))) < 60 THEN '30-59'
        ELSE '60+'
    END AS grupo_etario
FROM public.pacientes p;

CREATE OR REPLACE VIEW public.vista_afluencia_clinica_periodo AS
SELECT
    pe.id AS periodo_id,
    pe.nombre AS periodo,
    c.id AS clinica_id,
    c.especialidad,
    COUNT(DISTINCT r.paciente_id) AS total_pacientes
FROM public.remisiones r
JOIN public.clinicas c ON c.id = r.clinica_id
JOIN public.periodos pe ON pe.id = r.periodo_id
WHERE r.habilitado = true
GROUP BY pe.id, pe.nombre, c.id, c.especialidad;

CREATE OR REPLACE VIEW public.vista_cajas_periodo AS
SELECT
    pe.id AS periodo_id,
    pe.nombre AS periodo,
    COUNT(ca.id) AS total_recibos,
    SUM(ca.costo_total) AS total_cobrado,
    SUM(ca.cancelado) AS total_cancelado
FROM public.cajas ca
LEFT JOIN public.remisiones r ON r.id = ca.remision_id
LEFT JOIN public.periodos pe ON pe.id = r.periodo_id
GROUP BY pe.id, pe.nombre;


-- ============================================================
-- 14. LIMPIEZA AUTOMÁTICA DE DEFAULT ''
-- Elimina el DEFAULT '' de todas las columnas varchar/char
-- del esquema public que lo tengan definido.
-- ============================================================

DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT
            n.nspname AS esquema,
            c.relname AS tabla,
            a.attname AS columna
        FROM pg_attribute a
        JOIN pg_class c ON c.oid = a.attrelid
        JOIN pg_namespace n ON n.oid = c.relnamespace
        JOIN pg_attrdef d ON d.adrelid = a.attrelid AND d.adnum = a.attnum
        WHERE a.attnum > 0
          AND NOT a.attisdropped
          AND c.relkind = 'r'
          AND n.nspname = 'public'
          AND pg_get_expr(d.adbin, d.adrelid) = '''''::character varying'
    LOOP
        EXECUTE format('ALTER TABLE %I.%I ALTER COLUMN %I DROP DEFAULT;',
                       r.esquema, r.tabla, r.columna);
        RAISE NOTICE 'DEFAULT eliminado de: %.%.%', r.esquema, r.tabla, r.columna;
    END LOOP;
END $$;


-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
