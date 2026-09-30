-- ============================================================
-- BASE DE DATOS: Sistema Clínico Odontológico
-- Motor: PostgreSQL
-- Esquema: public
-- Versión: Español con columnas limpias (sin prefijos)
-- ============================================================

SET client_encoding = 'UTF8';
SET search_path TO public;

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
-- 3. MÓDULO DE PACIENTES
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


CREATE TABLE public.admisiones (
    id                  integer      NOT NULL,
    paciente_id         integer      NOT NULL,
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
    odontograma_pintado  text         DEFAULT '',
    atm                 text         DEFAULT '',
    bm                  text         DEFAULT '',
    gm                  text         DEFAULT '',
    calculo             varchar(50)  DEFAULT '',
    oclusion            varchar(50)  DEFAULT '',
    pieza_dental        varchar(200) DEFAULT '',
    diagnostico         text         DEFAULT '',
    examen              text         DEFAULT '',
    firma               text         DEFAULT '',
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


-- ============================================================
-- 4. MÓDULO DE REMISIONES Y CASOS CLÍNICOS
-- ============================================================

CREATE TABLE public.remisiones (
    id                  integer  NOT NULL,
    admision_id         integer  NOT NULL,
    paciente_id         integer  NOT NULL,
    clinica_id          integer  NOT NULL,
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


CREATE TABLE public.historias_clinicas (
    id                  integer      NOT NULL,
    admision_id         integer      NOT NULL,
    paciente_id         integer      NOT NULL,
    clinica_id          integer      NOT NULL,
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


-- ============================================================
-- 5. MÓDULO DE TRATAMIENTOS Y CAJAS
-- ============================================================

CREATE TABLE public.tratamientos (
    id                  integer      NOT NULL,
    nombre              varchar(100) NOT NULL,
    costo               numeric(10,2) DEFAULT 0,
    porcentaje_pg       varchar(100),
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


-- ============================================================
-- 6. MÓDULO DE INSUMOS Y ESTERILIZACIÓN
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
    usuario_id              integer,
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
    fecha_actualizacion integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.esterilizaciones OWNER TO postgres;

CREATE SEQUENCE public.esterilizaciones_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.esterilizaciones_id_seq
    OWNED BY public.esterilizaciones.id;
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
-- 7. MÓDULO DE RADIOLOGÍA
-- ============================================================

CREATE TABLE public.radiologias (
    id                  integer      NOT NULL,
    descripcion         varchar(100) NOT NULL,
    observacion         varchar(100) NOT NULL,
    estado              varchar(50)  NOT NULL,
    usuario_id          integer      NOT NULL,
    caja_id             integer      NOT NULL,
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
-- 8. CLAVES FORÁNEAS (respetando orden de dependencia)
-- ============================================================

-- --- usuarios / bitacoras ---
ALTER TABLE ONLY public.bitacoras
    ADD CONSTRAINT bitacora_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- especialidades ---
ALTER TABLE ONLY public.especialidades
    ADD CONSTRAINT especialidad_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.especialidades
    ADD CONSTRAINT especialidad_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- admisiones ---
ALTER TABLE ONLY public.admisiones
    ADD CONSTRAINT admision_paciente FOREIGN KEY (paciente_id)
    REFERENCES public.pacientes(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.admisiones
    ADD CONSTRAINT admision_estudiante FOREIGN KEY (estudiante_id, especialidad_estudiante)
    REFERENCES public.especialidades(usuario_id, clinica_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.admisiones
    ADD CONSTRAINT admision_responsable FOREIGN KEY (responsable_id, especialidad_responsable)
    REFERENCES public.especialidades(usuario_id, clinica_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- remisiones ---
ALTER TABLE ONLY public.remisiones
    ADD CONSTRAINT remision_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.remisiones
    ADD CONSTRAINT remision_admision FOREIGN KEY (admision_id, paciente_id)
    REFERENCES public.admisiones(id, paciente_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- historias_clinicas ---
ALTER TABLE ONLY public.historias_clinicas
    ADD CONSTRAINT historia_remision FOREIGN KEY (id, admision_id, paciente_id, clinica_id)
    REFERENCES public.remisiones(id, admision_id, paciente_id, clinica_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.historias_clinicas
    ADD CONSTRAINT historia_estudiante FOREIGN KEY (estudiante_id, especialidad_estudiante)
    REFERENCES public.especialidades(usuario_id, clinica_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.historias_clinicas
    ADD CONSTRAINT historia_docente FOREIGN KEY (docente_id, especialidad_docente)
    REFERENCES public.especialidades(usuario_id, clinica_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- tratamientos ---
ALTER TABLE ONLY public.tratamientos
    ADD CONSTRAINT tratamiento_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- cajas ---
ALTER TABLE ONLY public.cajas
    ADD CONSTRAINT caja_tratamiento FOREIGN KEY (tratamiento_id, clinica_id)
    REFERENCES public.tratamientos(id, clinica_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.cajas
    ADD CONSTRAINT caja_historia FOREIGN KEY (remision_id)
    REFERENCES public.historias_clinicas(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.cajas
    ADD CONSTRAINT caja_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- insumos_tratamiento ---
ALTER TABLE ONLY public.insumos_tratamiento
    ADD CONSTRAINT insumo_tratamiento_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- insumos ---
ALTER TABLE ONLY public.insumos
    ADD CONSTRAINT insumo_insumo_tratamiento FOREIGN KEY (insumo_tratamiento_id, clinica_id)
    REFERENCES public.insumos_tratamiento(id, clinica_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- insumos_solicitados ---
ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_insumo_tratamiento FOREIGN KEY (insumo_tratamiento_id, clinica_id)
    REFERENCES public.insumos_tratamiento(id, clinica_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_historia FOREIGN KEY (remision_id)
    REFERENCES public.historias_clinicas(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.insumos_solicitados
    ADD CONSTRAINT insumo_solicitado_docente FOREIGN KEY (docente_id)
    REFERENCES public.usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- esterilizaciones ---
ALTER TABLE ONLY public.esterilizaciones
    ADD CONSTRAINT esterilizacion_clinica FOREIGN KEY (clinica_id)
    REFERENCES public.clinicas(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.esterilizaciones
    ADD CONSTRAINT esterilizacion_estudiante FOREIGN KEY (estudiante_id)
    REFERENCES public.usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.esterilizaciones
    ADD CONSTRAINT esterilizacion_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- autorizaciones ---
ALTER TABLE ONLY public.autorizaciones
    ADD CONSTRAINT autorizacion_historia FOREIGN KEY (remision_id)
    REFERENCES public.historias_clinicas(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.autorizaciones
    ADD CONSTRAINT autorizacion_esterilizacion FOREIGN KEY (esterilizacion_id)
    REFERENCES public.esterilizaciones(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- radiologias ---
ALTER TABLE ONLY public.radiologias
    ADD CONSTRAINT radiologia_caja FOREIGN KEY (caja_id)
    REFERENCES public.cajas(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.radiologias
    ADD CONSTRAINT radiologia_usuario FOREIGN KEY (usuario_id)
    REFERENCES public.usuarios(id)
    ON UPDATE CASCADE ON DELETE CASCADE;


-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================