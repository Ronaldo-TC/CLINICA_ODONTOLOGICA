-- ============================================================
-- BASE DE DATOS: Sistema Clínico Odontológico
-- Motor: PostgreSQL
-- Esquema: public
-- ============================================================

-- (Opcional) Crear la base de datos
-- CREATE DATABASE clinica_odontologica
--     WITH ENCODING 'UTF8'
--     LC_COLLATE='es_BO.UTF-8'
--     LC_CTYPE='es_BO.UTF-8'
--     TEMPLATE=template0;

-- \c clinica_odontologica

SET client_encoding = 'UTF8';
SET search_path TO public;

-- ============================================================
-- 1. MÓDULO DE USUARIOS Y SEGURIDAD
-- ============================================================

CREATE TABLE public.usertable (
    usernumber        integer       NOT NULL,
    userci            varchar(50)   NOT NULL,
    username          varchar(20)   NOT NULL,
    userfullname      varchar(200)  NOT NULL,
    userdesc          varchar(300),
    usertype          varchar(20)   NOT NULL,
    userenabled       boolean       DEFAULT true NOT NULL,
    userpermitlogin   boolean       DEFAULT true NOT NULL,
    usermultilogin    boolean       DEFAULT false NOT NULL,
    userpassword      varchar(200)  DEFAULT '',
    userip            varchar(300),
    userlastlogin     integer,
    usersession       varchar(50)   DEFAULT '',
    usersessionextra  varchar(50)   DEFAULT '',
    userlastlogout    integer,
    userpermitip      varchar(300),
    userinfo          varchar(300)  DEFAULT '',
    updatetime        integer       DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.usertable OWNER TO postgres;

-- Secuencia para usernumber
CREATE SEQUENCE public.usertable_usernumber_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.usertable_usernumber_seq OWNED BY public.usertable.usernumber;
ALTER TABLE ONLY public.usertable
    ALTER COLUMN usernumber SET DEFAULT nextval('public.usertable_usernumber_seq');

-- PK y únicos
ALTER TABLE ONLY public.usertable
    ADD CONSTRAINT user_pkey PRIMARY KEY (usernumber);
CREATE UNIQUE INDEX user_index  ON public.usertable USING btree (usernumber);
CREATE UNIQUE INDEX user_index2 ON public.usertable USING btree (userci, username);


CREATE TABLE public.logtable (
    lognumber   integer      NOT NULL,
    loguser     integer,
    logip       varchar(20)  NOT NULL,
    logdate     integer      NOT NULL,
    logtype     varchar(20)  NOT NULL,
    logdata     text         NOT NULL,
    logstatus   varchar(20)  DEFAULT ''
);

ALTER TABLE public.logtable OWNER TO postgres;

CREATE SEQUENCE public.logtable_lognumber_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.logtable_lognumber_seq OWNED BY public.logtable.lognumber;
ALTER TABLE ONLY public.logtable
    ALTER COLUMN lognumber SET DEFAULT nextval('public.logtable_lognumber_seq');

ALTER TABLE ONLY public.logtable
    ADD CONSTRAINT log_pkey PRIMARY KEY (lognumber);
CREATE INDEX log_index ON public.logtable USING btree (loguser, logdate);


-- ============================================================
-- 2. MÓDULO DE ESPECIALIDADES CLÍNICAS
-- ============================================================

CREATE TABLE public.clinicaltable (
    clinicalid         integer      NOT NULL,
    clinicalmodule     varchar(200) DEFAULT '',
    clinicalspecialty  varchar(200) DEFAULT '',
    clinicalcourse     integer      DEFAULT -1,
    updatetime         integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.clinicaltable OWNER TO postgres;

CREATE SEQUENCE public.clinicaltable_clinicalid_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.clinicaltable_clinicalid_seq OWNED BY public.clinicaltable.clinicalid;
ALTER TABLE ONLY public.clinicaltable
    ALTER COLUMN clinicalid SET DEFAULT nextval('public.clinicaltable_clinicalid_seq');

ALTER TABLE ONLY public.clinicaltable
    ADD CONSTRAINT clinical_pkey PRIMARY KEY (clinicalid);
CREATE UNIQUE INDEX clinical_index  ON public.clinicaltable USING btree (clinicalid);
CREATE UNIQUE INDEX clinical_index2 ON public.clinicaltable USING btree (clinicalspecialty varchar_ops);


CREATE TABLE public.specialtytable (
    userid               integer  NOT NULL,
    clinicalid           integer  NOT NULL,
    enabled              boolean  DEFAULT true NOT NULL,
    rotate               integer  DEFAULT -1,
    responsibleadmission boolean  DEFAULT false NOT NULL,
    stdatetime           integer  DEFAULT date_part('epoch', now()) NOT NULL,
    updatetime           integer  DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.specialtytable OWNER TO postgres;

ALTER TABLE ONLY public.specialtytable
    ADD CONSTRAINT specialty_pkey PRIMARY KEY (userid, clinicalid);
CREATE INDEX specialty_index ON public.specialtytable USING btree (userid, clinicalid);


-- ============================================================
-- 3. MÓDULO DE PACIENTES
-- ============================================================

CREATE TABLE public.patienttable (
    patientid             integer      NOT NULL,
    patientci             varchar(50)  NOT NULL,
    patientname           varchar(50)  NOT NULL,
    patientfatherlastname varchar(50)  NOT NULL,
    patientmotherlastname varchar(50)  NOT NULL,
    patientgender         varchar(50)  NOT NULL,
    patientbirthdate      integer      NOT NULL,
    updatetime            integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.patienttable OWNER TO postgres;

CREATE SEQUENCE public.patienttable_patientid_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.patienttable_patientid_seq OWNED BY public.patienttable.patientid;
ALTER TABLE ONLY public.patienttable
    ALTER COLUMN patientid SET DEFAULT nextval('public.patienttable_patientid_seq');

ALTER TABLE ONLY public.patienttable
    ADD CONSTRAINT patient_pkey PRIMARY KEY (patientid);


CREATE TABLE public.patientadmissiontable (
    patientadmissionid       integer      NOT NULL,
    patientid                integer      NOT NULL,
    patientcivilstatus       varchar(200) DEFAULT '',
    patientschool            varchar(200) DEFAULT '',
    patientoccupation        varchar(200) DEFAULT '',
    patientdirection         varchar(200) DEFAULT '',
    patientemergency         varchar(200) DEFAULT '',
    patientphone             integer      DEFAULT 0,
    patientmotconsult        text         DEFAULT '',
    patientdiseasehistory    text         DEFAULT '',
    patientgmh               text         DEFAULT '',
    patientgpe               text         DEFAULT '',
    patientvitalsigns        text         DEFAULT '',
    patientee                text         DEFAULT '',
    patientcn                text         DEFAULT '',
    patientodontogram        text         DEFAULT '',
    patientodontopaint       text         DEFAULT '',
    patientatm               text         DEFAULT '',
    patientbm                text         DEFAULT '',
    patientgm                text         DEFAULT '',
    patientcalculation       varchar(50)  DEFAULT '',
    patientocclusion         varchar(50)  DEFAULT '',
    patientdentalpiece       varchar(200) DEFAULT '',
    patientdiagnosis         text         DEFAULT '',
    patientexam              text         DEFAULT '',
    patientfirm              text         DEFAULT '',
    studentid                integer      NOT NULL,
    studentspecialty         integer      NOT NULL,
    responsibleid            integer      NOT NULL,
    responsiblespecialty     integer      NOT NULL,
    updatetime               integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.patientadmissiontable OWNER TO postgres;

CREATE SEQUENCE public.patientadmissiontable_patientadmissionid_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.patientadmissiontable_patientadmissionid_seq
    OWNED BY public.patientadmissiontable.patientadmissionid;
ALTER TABLE ONLY public.patientadmissiontable
    ALTER COLUMN patientadmissionid
    SET DEFAULT nextval('public.patientadmissiontable_patientadmissionid_seq');

ALTER TABLE ONLY public.patientadmissiontable
    ADD CONSTRAINT patientadmission_pkey PRIMARY KEY (patientadmissionid, patientid);
CREATE INDEX patientadmission_index
    ON public.patientadmissiontable USING btree (patientadmissionid, patientid);


-- ============================================================
-- 4. MÓDULO DE REMISIONES Y CASOS CLÍNICOS
-- ============================================================

CREATE TABLE public.remissiontable (
    remissionid  integer  NOT NULL,
    admissionid  integer  NOT NULL,
    patientid    integer  NOT NULL,
    clinicalid   integer  NOT NULL,
    enabled      boolean  DEFAULT true NOT NULL,
    updatetime   integer  DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.remissiontable OWNER TO postgres;

CREATE SEQUENCE public.remissiontable_remissionid_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.remissiontable_remissionid_seq OWNED BY public.remissiontable.remissionid;
ALTER TABLE ONLY public.remissiontable
    ALTER COLUMN remissionid SET DEFAULT nextval('public.remissiontable_remissionid_seq');

ALTER TABLE ONLY public.remissiontable
    ADD CONSTRAINT remission_pkey PRIMARY KEY (remissionid, admissionid, patientid, clinicalid);
CREATE INDEX remission_index
    ON public.remissiontable USING btree (remissionid, admissionid, patientid);


CREATE TABLE public.clinichistorytable (
    remissionid        integer      NOT NULL,
    admissionid        integer      NOT NULL,
    patientid          integer      NOT NULL,
    clinicalid         integer      NOT NULL,
    status             varchar(50)  DEFAULT '',
    inputfilename      varchar(100) DEFAULT '',
    inputfile          oid,
    inputfilehash      varchar(50),
    authorized         boolean      DEFAULT false NOT NULL,
    studentid          integer      NOT NULL,
    studentclinicalid  integer      NOT NULL,
    teacherid          integer      NOT NULL,
    teacherclinicalid  integer      NOT NULL,
    reviewteacher      text         DEFAULT '',
    reviewany          boolean      DEFAULT false NOT NULL,
    reviewstatus       boolean      DEFAULT false NOT NULL,
    stdatetime         integer      DEFAULT date_part('epoch', now()) NOT NULL,
    endatetime         integer      DEFAULT -1 NOT NULL,
    updatetime         integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.clinichistorytable OWNER TO postgres;

ALTER TABLE ONLY public.clinichistorytable
    ADD CONSTRAINT pediatricsi_pkey PRIMARY KEY (remissionid);
CREATE INDEX clinichistory_index
    ON public.clinichistorytable USING btree (remissionid, patientid, studentid);


-- ============================================================
-- 5. MÓDULO DE TRATAMIENTOS Y CAJAS
-- ============================================================

CREATE TABLE public.treatmenttable (
    treatmentid       integer      NOT NULL,
    treatmentname     varchar(100) NOT NULL,
    treatmentcost     numeric(10,2) DEFAULT 0,
    treatmentpgpercent varchar(100),
    treatmentdesc     varchar(100) DEFAULT '',
    clinicalid        integer      NOT NULL,
    updatetime        integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.treatmenttable OWNER TO postgres;

-- Secuencia nueva (no existía en el dump original)
CREATE SEQUENCE public.treatmenttable_treatmentid_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.treatmenttable_treatmentid_seq
    OWNED BY public.treatmenttable.treatmentid;
ALTER TABLE ONLY public.treatmenttable
    ALTER COLUMN treatmentid SET DEFAULT nextval('public.treatmenttable_treatmentid_seq');

ALTER TABLE ONLY public.treatmenttable
    ADD CONSTRAINT treatment_pkey PRIMARY KEY (treatmentid, clinicalid);
CREATE INDEX treatment_index
    ON public.treatmenttable USING btree (treatmentid, clinicalid);


CREATE TABLE public.boxtable (
    boxid             integer      NOT NULL,
    boxreceipt        integer      NOT NULL,
    boxamount         integer      NOT NULL,
    boxtotalcost      numeric(10,2) DEFAULT 0,
    boxcancelled      numeric(10,2) DEFAULT 0,
    boxenabled        boolean      DEFAULT true NOT NULL,
    boxdescription    varchar(100),
    boxci             integer      DEFAULT 0,
    boxtype           varchar(100),
    boxtypepayment    varchar(100),
    boxpatientfullname varchar(100),
    boxgrade          varchar(100),
    treatmentid       integer      NOT NULL,
    clinicalid        integer      NOT NULL,
    remissionid       integer,
    userid            integer      NOT NULL,
    updatetime        integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.boxtable OWNER TO postgres;

-- Secuencia nueva
CREATE SEQUENCE public.boxtable_boxid_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.boxtable_boxid_seq OWNED BY public.boxtable.boxid;
ALTER TABLE ONLY public.boxtable
    ALTER COLUMN boxid SET DEFAULT nextval('public.boxtable_boxid_seq');

ALTER TABLE ONLY public.boxtable
    ADD CONSTRAINT box_pkey PRIMARY KEY (boxid);
CREATE INDEX box_index ON public.boxtable USING btree (boxid);


-- ============================================================
-- 6. MÓDULO DE INSUMOS Y ESTERILIZACIÓN
-- ============================================================

CREATE TABLE public.suppliestreatmenttable (
    suppliestreatmentid   integer      NOT NULL,
    suppliestreatmentname varchar(100) NOT NULL,
    suppliesdesc          varchar(100) DEFAULT '',
    clinicalid            integer      NOT NULL,
    updatetime            integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.suppliestreatmenttable OWNER TO postgres;

CREATE SEQUENCE public.suppliestreatmenttable_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.suppliestreatmenttable_id_seq
    OWNED BY public.suppliestreatmenttable.suppliestreatmentid;
ALTER TABLE ONLY public.suppliestreatmenttable
    ALTER COLUMN suppliestreatmentid
    SET DEFAULT nextval('public.suppliestreatmenttable_id_seq');

ALTER TABLE ONLY public.suppliestreatmenttable
    ADD CONSTRAINT suppliestreatment_pkey PRIMARY KEY (suppliestreatmentid, clinicalid);
CREATE INDEX suppliestreatment_index
    ON public.suppliestreatmenttable USING btree (suppliestreatmentid, clinicalid);


CREATE TABLE public.suppliestable (
    suppliesid             integer      NOT NULL,
    suppliesname           varchar(100) NOT NULL,
    suppliesquantitynumber numeric(10,2) DEFAULT 0,
    suppliesquantity       varchar(100) DEFAULT '',
    suppliestreatmentid    integer      NOT NULL,
    clinicalid             integer      NOT NULL,
    updatetime             integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.suppliestable OWNER TO postgres;

CREATE SEQUENCE public.suppliestable_suppliesid_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.suppliestable_suppliesid_seq
    OWNED BY public.suppliestable.suppliesid;
ALTER TABLE ONLY public.suppliestable
    ALTER COLUMN suppliesid SET DEFAULT nextval('public.suppliestable_suppliesid_seq');

ALTER TABLE ONLY public.suppliestable
    ADD CONSTRAINT supplies_pkey PRIMARY KEY (suppliesid);
CREATE INDEX supplies_index
    ON public.suppliestable USING btree (suppliesid, suppliestreatmentid, clinicalid);


CREATE TABLE public.suppliesrequestedtable (
    suppliesrequestedid integer  NOT NULL,
    piece               integer  NOT NULL,
    supplies            text     DEFAULT '',
    authorizedteacher   integer  DEFAULT 0 NOT NULL,
    teacherid           integer,
    authorizedsupplies  boolean  DEFAULT false NOT NULL,
    suppliestreatmentid integer  NOT NULL,
    clinicalid          integer  NOT NULL,
    remissionid         integer  NOT NULL,
    userid              integer,
    updatetime          integer  DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.suppliesrequestedtable OWNER TO postgres;

CREATE SEQUENCE public.suppliesrequestedtable_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.suppliesrequestedtable_id_seq
    OWNED BY public.suppliesrequestedtable.suppliesrequestedid;
ALTER TABLE ONLY public.suppliesrequestedtable
    ALTER COLUMN suppliesrequestedid
    SET DEFAULT nextval('public.suppliesrequestedtable_id_seq');

ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT suppliesrequested_pkey PRIMARY KEY (suppliesrequestedid);
CREATE INDEX suppliesrequested_index
    ON public.suppliesrequestedtable USING btree (suppliesrequestedid);


CREATE TABLE public.sterilizationtable (
    sterilizationid       integer      NOT NULL,
    sterilizationenvelope integer      NOT NULL,
    sterilizationpackage  integer      NOT NULL,
    sterilizationenabled  boolean      DEFAULT true NOT NULL,
    sterilizationinfo     varchar(200) DEFAULT '' NOT NULL,
    sterilizationdesc     varchar(200) DEFAULT '' NOT NULL,
    deliverydatetime      integer      DEFAULT -1 NOT NULL,
    pickupdatetime        integer      DEFAULT -1 NOT NULL,
    studentid             integer,
    clinicalid            integer      NOT NULL,
    userid                integer      NOT NULL,
    updatetime            integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.sterilizationtable OWNER TO postgres;

CREATE SEQUENCE public.sterilizationtable_sterilizationid_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.sterilizationtable_sterilizationid_seq
    OWNED BY public.sterilizationtable.sterilizationid;
ALTER TABLE ONLY public.sterilizationtable
    ALTER COLUMN sterilizationid
    SET DEFAULT nextval('public.sterilizationtable_sterilizationid_seq');

ALTER TABLE ONLY public.sterilizationtable
    ADD CONSTRAINT sterilization_pkey PRIMARY KEY (sterilizationid);
CREATE INDEX sterilization_index
    ON public.sterilizationtable USING btree (sterilizationid);


CREATE TABLE public.authorizationtable (
    sterilizationid       integer      NOT NULL,
    remissionid           integer      NOT NULL,
    authorizationstatus   boolean      DEFAULT false NOT NULL,
    authorizationdesc     varchar(200) DEFAULT '' NOT NULL,
    authorizationteacher  varchar(200) DEFAULT '' NOT NULL,
    authorizationenvelope integer      NOT NULL,
    updatetime            integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.authorizationtable OWNER TO postgres;

ALTER TABLE ONLY public.authorizationtable
    ADD CONSTRAINT authorization_pkey PRIMARY KEY (sterilizationid, remissionid);
CREATE INDEX authorization_index
    ON public.authorizationtable USING btree (sterilizationid, remissionid);


-- ============================================================
-- 7. MÓDULO DE RADIOLOGÍA
-- ============================================================

CREATE TABLE public.radiologytable (
    radiologyid    integer      NOT NULL,
    radiologydesc  varchar(100) NOT NULL,
    radiologyobs   varchar(100) NOT NULL,
    radiologystate varchar(50)  NOT NULL,
    userid         integer      NOT NULL,
    boxid          integer      NOT NULL,
    updatetime     integer      DEFAULT date_part('epoch', now()) NOT NULL
);

ALTER TABLE public.radiologytable OWNER TO postgres;

CREATE SEQUENCE public.radiologytable_radiologyid_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;
ALTER SEQUENCE public.radiologytable_radiologyid_seq
    OWNED BY public.radiologytable.radiologyid;
ALTER TABLE ONLY public.radiologytable
    ALTER COLUMN radiologyid
    SET DEFAULT nextval('public.radiologytable_radiologyid_seq');

ALTER TABLE ONLY public.radiologytable
    ADD CONSTRAINT radiology_pkey PRIMARY KEY (radiologyid, userid, boxid);
CREATE INDEX radiology_index
    ON public.radiologytable USING btree (radiologyid, userid, boxid);


-- ============================================================
-- 8. FOREIGN KEYS (respetando orden de dependencia)
-- ============================================================

-- --- usertable / logtable ---
ALTER TABLE ONLY public.logtable
    ADD CONSTRAINT loguser FOREIGN KEY (loguser)
    REFERENCES public.usertable(usernumber)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- specialtytable ---
ALTER TABLE ONLY public.specialtytable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid)
    REFERENCES public.usertable(usernumber)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.specialtytable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid)
    REFERENCES public.clinicaltable(clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- patientadmissiontable ---
ALTER TABLE ONLY public.patientadmissiontable
    ADD CONSTRAINT patientadmission FOREIGN KEY (patientid)
    REFERENCES public.patienttable(patientid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.patientadmissiontable
    ADD CONSTRAINT specialty_fk FOREIGN KEY (studentid, studentspecialty)
    REFERENCES public.specialtytable(userid, clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.patientadmissiontable
    ADD CONSTRAINT specialty_fk2 FOREIGN KEY (responsibleid, responsiblespecialty)
    REFERENCES public.specialtytable(userid, clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- remissiontable ---
ALTER TABLE ONLY public.remissiontable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid)
    REFERENCES public.clinicaltable(clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.remissiontable
    ADD CONSTRAINT patientadmission_fk FOREIGN KEY (admissionid, patientid)
    REFERENCES public.patientadmissiontable(patientadmissionid, patientid)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- clinichistorytable ---
ALTER TABLE ONLY public.clinichistorytable
    ADD CONSTRAINT remission_fk FOREIGN KEY (remissionid, admissionid, patientid, clinicalid)
    REFERENCES public.remissiontable(remissionid, admissionid, patientid, clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.clinichistorytable
    ADD CONSTRAINT specialty_fk FOREIGN KEY (studentid, studentclinicalid)
    REFERENCES public.specialtytable(userid, clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.clinichistorytable
    ADD CONSTRAINT specialty2_fk FOREIGN KEY (teacherid, teacherclinicalid)
    REFERENCES public.specialtytable(userid, clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- treatmenttable ---
ALTER TABLE ONLY public.treatmenttable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid)
    REFERENCES public.clinicaltable(clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- boxtable ---
ALTER TABLE ONLY public.boxtable
    ADD CONSTRAINT treatment_fk FOREIGN KEY (treatmentid, clinicalid)
    REFERENCES public.treatmenttable(treatmentid, clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.boxtable
    ADD CONSTRAINT clinichistory_fk FOREIGN KEY (remissionid)
    REFERENCES public.clinichistorytable(remissionid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.boxtable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid)
    REFERENCES public.usertable(usernumber)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- suppliestreatmenttable ---
ALTER TABLE ONLY public.suppliestreatmenttable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid)
    REFERENCES public.clinicaltable(clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- suppliestable ---
ALTER TABLE ONLY public.suppliestable
    ADD CONSTRAINT suppliestreatment_fk FOREIGN KEY (suppliestreatmentid, clinicalid)
    REFERENCES public.suppliestreatmenttable(suppliestreatmentid, clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- suppliesrequestedtable ---
ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT suppliestreatment_fk FOREIGN KEY (suppliestreatmentid, clinicalid)
    REFERENCES public.suppliestreatmenttable(suppliestreatmentid, clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT clinichistory_fk FOREIGN KEY (remissionid)
    REFERENCES public.clinichistorytable(remissionid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid)
    REFERENCES public.usertable(usernumber)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT userteacher_fk FOREIGN KEY (teacherid)
    REFERENCES public.usertable(usernumber)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- sterilizationtable ---
ALTER TABLE ONLY public.sterilizationtable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid)
    REFERENCES public.clinicaltable(clinicalid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.sterilizationtable
    ADD CONSTRAINT studentuser_fk FOREIGN KEY (studentid)
    REFERENCES public.usertable(usernumber)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.sterilizationtable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid)
    REFERENCES public.usertable(usernumber)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- authorizationtable ---
ALTER TABLE ONLY public.authorizationtable
    ADD CONSTRAINT clinichistory_fk FOREIGN KEY (remissionid)
    REFERENCES public.clinichistorytable(remissionid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.authorizationtable
    ADD CONSTRAINT sterilization_fk FOREIGN KEY (sterilizationid)
    REFERENCES public.sterilizationtable(sterilizationid)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- --- radiologytable ---
ALTER TABLE ONLY public.radiologytable
    ADD CONSTRAINT box_fk FOREIGN KEY (boxid)
    REFERENCES public.boxtable(boxid)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE ONLY public.radiologytable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid)
    REFERENCES public.usertable(usernumber)
    ON UPDATE CASCADE ON DELETE CASCADE;


-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================