--
-- PostgreSQL database dump
--

-- Dumped from database version 12.22 (Ubuntu 12.22-0ubuntu0.20.04.4)
-- Dumped by pg_dump version 12.22 (Ubuntu 12.22-0ubuntu0.20.04.4)

-- Started on 2026-09-15 12:04:28 -04



--
-- TOC entry 218 (class 1259 OID 16647)
-- Name: authorizationtable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.authorizationtable (
    sterilizationid integer NOT NULL,
    remissionid integer NOT NULL,
    authorizationstatus boolean DEFAULT false NOT NULL,
    authorizationdesc character varying(200) DEFAULT ''::character varying NOT NULL,
    authorizationteacher character varying(200) DEFAULT ''::character varying NOT NULL,
    authorizationenvelope integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.authorizationtable OWNER TO postgres;

--
-- TOC entry 216 (class 1259 OID 16586)
-- Name: boxtable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.boxtable (
    boxid integer NOT NULL,
    boxreceipt integer NOT NULL,
    boxamount integer NOT NULL,
    boxtotalcost numeric(10,2) DEFAULT 0,
    boxcancelled numeric(10,2) DEFAULT 0,
    boxenabled boolean DEFAULT true NOT NULL,
    boxdescription character varying(100) DEFAULT NULL::character varying,
    boxci integer DEFAULT 0,
    boxtype character varying(100) DEFAULT NULL::character varying,
    boxtypepayment character varying(100) DEFAULT NULL::character varying,
    boxpatientfullname character varying(100) DEFAULT NULL::character varying,
    boxgrade character varying(100) DEFAULT NULL::character varying,
    treatmentid integer NOT NULL,
    clinicalid integer NOT NULL,
    remissionid integer,
    userid integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.boxtable OWNER TO postgres;

--
-- TOC entry 206 (class 1259 OID 16424)
-- Name: clinicaltable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clinicaltable (
    clinicalid integer NOT NULL,
    clinicalmodule character varying(200) DEFAULT ''::character varying,
    clinicalspecialty character varying(200) DEFAULT ''::character varying,
    clinicalcourse integer DEFAULT '-1'::integer,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.clinicaltable OWNER TO postgres;

--
-- TOC entry 205 (class 1259 OID 16422)
-- Name: clinicaltable_clinicalid_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.clinicaltable_clinicalid_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.clinicaltable_clinicalid_seq OWNER TO postgres;

--
-- TOC entry 3268 (class 0 OID 0)
-- Dependencies: 205
-- Name: clinicaltable_clinicalid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.clinicaltable_clinicalid_seq OWNED BY public.clinicaltable.clinicalid;


--
-- TOC entry 214 (class 1259 OID 16539)
-- Name: clinichistorytable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clinichistorytable (
    remissionid integer NOT NULL,
    admissionid integer NOT NULL,
    patientid integer NOT NULL,
    clinicalid integer NOT NULL,
    status character varying(50) DEFAULT ''::character varying,
    inputfilename character varying(100) DEFAULT ''::character varying,
    inputfile oid,
    inputfilehash character varying(50),
    authorized boolean DEFAULT false NOT NULL,
    studentid integer NOT NULL,
    studentclinicalid integer NOT NULL,
    teacherid integer NOT NULL,
    teacherclinicalid integer NOT NULL,
    reviewteacher text DEFAULT ''::text,
    reviewany boolean DEFAULT false NOT NULL,
    reviewstatus boolean DEFAULT false NOT NULL,
    stdatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL,
    endatetime integer DEFAULT '-1'::integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.clinichistorytable OWNER TO postgres;

--
-- TOC entry 204 (class 1259 OID 16406)
-- Name: logtable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.logtable (
    lognumber integer NOT NULL,
    loguser integer,
    logip character varying(20) NOT NULL,
    logdate integer NOT NULL,
    logtype character varying(20) NOT NULL,
    logdata text NOT NULL,
    logstatus character varying(20) DEFAULT ''::character varying
);


ALTER TABLE public.logtable OWNER TO postgres;

--
-- TOC entry 203 (class 1259 OID 16404)
-- Name: logtable_lognumber_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.logtable_lognumber_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.logtable_lognumber_seq OWNER TO postgres;

--
-- TOC entry 3269 (class 0 OID 0)
-- Dependencies: 203
-- Name: logtable_lognumber_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.logtable_lognumber_seq OWNED BY public.logtable.lognumber;


--
-- TOC entry 211 (class 1259 OID 16468)
-- Name: patientadmissiontable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.patientadmissiontable (
    patientadmissionid integer NOT NULL,
    patientid integer NOT NULL,
    patientcivilstatus character varying(200) DEFAULT ''::character varying,
    patientschool character varying(200) DEFAULT ''::character varying,
    patientoccupation character varying(200) DEFAULT ''::character varying,
    patientdirection character varying(200) DEFAULT ''::character varying,
    patientemergency character varying(200) DEFAULT ''::character varying,
    patientphone integer DEFAULT 0,
    patientmotconsult text DEFAULT ''::text,
    patientdiseasehistory text DEFAULT ''::text,
    patientgmh text DEFAULT ''::text,
    patientgpe text DEFAULT ''::text,
    patientvitalsigns text DEFAULT ''::text,
    patientee text DEFAULT ''::text,
    patientcn text DEFAULT ''::text,
    patientodontogram text DEFAULT ''::text,
    patientodontopaint text DEFAULT ''::text,
    patientatm text DEFAULT ''::text,
    patientbm text DEFAULT ''::text,
    patientgm text DEFAULT ''::text,
    patientcalculation character varying(50) DEFAULT ''::character varying,
    patientocclusion character varying(50) DEFAULT ''::character varying,
    patientdentalpiece character varying(200) DEFAULT ''::character varying,
    patientdiagnosis text DEFAULT ''::text,
    patientexam text DEFAULT ''::text,
    patientfirm text DEFAULT ''::text,
    studentid integer NOT NULL,
    studentspecialty integer NOT NULL,
    responsibleid integer NOT NULL,
    responsiblespecialty integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.patientadmissiontable OWNER TO postgres;

--
-- TOC entry 210 (class 1259 OID 16466)
-- Name: patientadmissiontable_patientadmissionid_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.patientadmissiontable_patientadmissionid_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.patientadmissiontable_patientadmissionid_seq OWNER TO postgres;

--
-- TOC entry 3270 (class 0 OID 0)
-- Dependencies: 210
-- Name: patientadmissiontable_patientadmissionid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.patientadmissiontable_patientadmissionid_seq OWNED BY public.patientadmissiontable.patientadmissionid;


--
-- TOC entry 209 (class 1259 OID 16459)
-- Name: patienttable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.patienttable (
    patientid integer NOT NULL,
    patientci character varying(50) NOT NULL,
    patientname character varying(50) NOT NULL,
    patientfatherlastname character varying(50) NOT NULL,
    patientmotherlastname character varying(50) NOT NULL,
    patientgender character varying(50) NOT NULL,
    patientbirthdate integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.patienttable OWNER TO postgres;

--
-- TOC entry 208 (class 1259 OID 16457)
-- Name: patienttable_patientid_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.patienttable_patientid_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.patienttable_patientid_seq OWNER TO postgres;

--
-- TOC entry 3271 (class 0 OID 0)
-- Dependencies: 208
-- Name: patienttable_patientid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.patienttable_patientid_seq OWNED BY public.patienttable.patientid;


--
-- TOC entry 222 (class 1259 OID 16727)
-- Name: radiologytable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.radiologytable (
    radiologyid integer NOT NULL,
    radiologydesc character varying(100) NOT NULL,
    radiologyobs character varying(100) NOT NULL,
    radiologystate character varying(50) NOT NULL,
    userid integer NOT NULL,
    boxid integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.radiologytable OWNER TO postgres;

--
-- TOC entry 213 (class 1259 OID 16520)
-- Name: remissiontable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.remissiontable (
    remissionid integer NOT NULL,
    admissionid integer NOT NULL,
    patientid integer NOT NULL,
    clinicalid integer NOT NULL,
    enabled boolean DEFAULT true NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.remissiontable OWNER TO postgres;

--
-- TOC entry 212 (class 1259 OID 16518)
-- Name: remissiontable_remissionid_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.remissiontable_remissionid_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.remissiontable_remissionid_seq OWNER TO postgres;

--
-- TOC entry 3272 (class 0 OID 0)
-- Dependencies: 212
-- Name: remissiontable_remissionid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.remissiontable_remissionid_seq OWNED BY public.remissiontable.remissionid;


--
-- TOC entry 207 (class 1259 OID 16436)
-- Name: specialtytable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.specialtytable (
    userid integer NOT NULL,
    clinicalid integer NOT NULL,
    enabled boolean DEFAULT true NOT NULL,
    rotate integer DEFAULT '-1'::integer,
    responsibleadmission boolean DEFAULT false NOT NULL,
    stdatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.specialtytable OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 16620)
-- Name: sterilizationtable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sterilizationtable (
    sterilizationid integer NOT NULL,
    sterilizationenvelope integer NOT NULL,
    sterilizationpackage integer NOT NULL,
    sterilizationenabled boolean DEFAULT true NOT NULL,
    sterilizationinfo character varying(200) DEFAULT ''::character varying NOT NULL,
    sterilizationdesc character varying(200) DEFAULT ''::character varying NOT NULL,
    deliverydatetime integer DEFAULT '-1'::integer NOT NULL,
    pickupdatetime integer DEFAULT '-1'::integer NOT NULL,
    studentid integer,
    clinicalid integer NOT NULL,
    userid integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.sterilizationtable OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 16694)
-- Name: suppliesrequestedtable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.suppliesrequestedtable (
    suppliesrequestedid integer NOT NULL,
    piece integer NOT NULL,
    supplies text DEFAULT ''::text,
    authorizedteacher integer DEFAULT 0 NOT NULL,
    teacherid integer,
    authorizedsupplies boolean DEFAULT false NOT NULL,
    suppliestreatmentid integer NOT NULL,
    clinicalid integer NOT NULL,
    remissionid integer NOT NULL,
    userid integer,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.suppliesrequestedtable OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 16680)
-- Name: suppliestable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.suppliestable (
    suppliesid integer NOT NULL,
    suppliesname character varying(100) NOT NULL,
    suppliesquantitynumber numeric(10,2) DEFAULT 0,
    suppliesquantity character varying(100) DEFAULT ''::character varying,
    suppliestreatmentid integer NOT NULL,
    clinicalid integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.suppliestable OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 16667)
-- Name: suppliestreatmenttable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.suppliestreatmenttable (
    suppliestreatmentid integer NOT NULL,
    suppliestreatmentname character varying(100) NOT NULL,
    suppliesdesc character varying(100) DEFAULT ''::character varying,
    clinicalid integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.suppliestreatmenttable OWNER TO postgres;

--
-- TOC entry 215 (class 1259 OID 16572)
-- Name: treatmenttable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.treatmenttable (
    treatmentid integer NOT NULL,
    treatmentname character varying(100) NOT NULL,
    treatmentcost numeric(10,2) DEFAULT 0,
    treatmentpgpercent character varying(100),
    treatmentdesc character varying(100) DEFAULT ''::character varying,
    clinicalid integer NOT NULL,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.treatmenttable OWNER TO postgres;

--
-- TOC entry 202 (class 1259 OID 16386)
-- Name: usertable; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usertable (
    usernumber integer NOT NULL,
    userci character varying(50) NOT NULL,
    username character varying(20) NOT NULL,
    userfullname character varying(200) NOT NULL,
    userdesc character varying(300),
    usertype character varying(20) NOT NULL,
    userenabled boolean DEFAULT true NOT NULL,
    userpermitlogin boolean DEFAULT true NOT NULL,
    usermultilogin boolean DEFAULT false NOT NULL,
    userpassword character varying(200) DEFAULT ''::character varying,
    userip character varying(300),
    userlastlogin integer,
    usersession character varying(50) DEFAULT ''::character varying,
    usersessionextra character varying(50) DEFAULT ''::character varying,
    userlastlogout integer,
    userpermitip character varying(300),
    userinfo character varying(300) DEFAULT ''::character varying,
    updatetime integer DEFAULT date_part('epoch'::text, now()) NOT NULL
);


ALTER TABLE public.usertable OWNER TO postgres;

--
-- TOC entry 2956 (class 2604 OID 16427)
-- Name: clinicaltable clinicalid; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clinicaltable ALTER COLUMN clinicalid SET DEFAULT nextval('public.clinicaltable_clinicalid_seq'::regclass);


--
-- TOC entry 2954 (class 2604 OID 16409)
-- Name: logtable lognumber; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logtable ALTER COLUMN lognumber SET DEFAULT nextval('public.logtable_lognumber_seq'::regclass);


--
-- TOC entry 2968 (class 2604 OID 16471)
-- Name: patientadmissiontable patientadmissionid; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patientadmissiontable ALTER COLUMN patientadmissionid SET DEFAULT nextval('public.patientadmissiontable_patientadmissionid_seq'::regclass);


--
-- TOC entry 2966 (class 2604 OID 16462)
-- Name: patienttable patientid; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patienttable ALTER COLUMN patientid SET DEFAULT nextval('public.patienttable_patientid_seq'::regclass);


--
-- TOC entry 2994 (class 2604 OID 16523)
-- Name: remissiontable remissionid; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remissiontable ALTER COLUMN remissionid SET DEFAULT nextval('public.remissiontable_remissionid_seq'::regclass);


--
-- TOC entry 3075 (class 2606 OID 16655)
-- Name: authorizationtable authorization_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.authorizationtable
    ADD CONSTRAINT authorization_pkey PRIMARY KEY (sterilizationid, remissionid);


--
-- TOC entry 3069 (class 2606 OID 16603)
-- Name: boxtable box_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boxtable
    ADD CONSTRAINT box_pkey PRIMARY KEY (boxid);


--
-- TOC entry 3049 (class 2606 OID 16433)
-- Name: clinicaltable clinical_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clinicaltable
    ADD CONSTRAINT clinical_pkey PRIMARY KEY (clinicalid);


--
-- TOC entry 3045 (class 2606 OID 16415)
-- Name: logtable log_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logtable
    ADD CONSTRAINT log_pkey PRIMARY KEY (lognumber);


--
-- TOC entry 3054 (class 2606 OID 16465)
-- Name: patienttable patient_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patienttable
    ADD CONSTRAINT patient_pkey PRIMARY KEY (patientid);


--
-- TOC entry 3057 (class 2606 OID 16501)
-- Name: patientadmissiontable patientadmission_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patientadmissiontable
    ADD CONSTRAINT patientadmission_pkey PRIMARY KEY (patientadmissionid, patientid);


--
-- TOC entry 3063 (class 2606 OID 16555)
-- Name: clinichistorytable pediatricsi_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clinichistorytable
    ADD CONSTRAINT pediatricsi_pkey PRIMARY KEY (remissionid);


--
-- TOC entry 3087 (class 2606 OID 16732)
-- Name: radiologytable radiology_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.radiologytable
    ADD CONSTRAINT radiology_pkey PRIMARY KEY (radiologyid, userid, boxid);


--
-- TOC entry 3060 (class 2606 OID 16527)
-- Name: remissiontable remission_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remissiontable
    ADD CONSTRAINT remission_pkey PRIMARY KEY (remissionid, admissionid, patientid, clinicalid);


--
-- TOC entry 3052 (class 2606 OID 16445)
-- Name: specialtytable specialty_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.specialtytable
    ADD CONSTRAINT specialty_pkey PRIMARY KEY (userid, clinicalid);


--
-- TOC entry 3072 (class 2606 OID 16630)
-- Name: sterilizationtable sterilization_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sterilizationtable
    ADD CONSTRAINT sterilization_pkey PRIMARY KEY (sterilizationid);


--
-- TOC entry 3081 (class 2606 OID 16687)
-- Name: suppliestable supplies_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.suppliestable
    ADD CONSTRAINT supplies_pkey PRIMARY KEY (suppliesid);


--
-- TOC entry 3084 (class 2606 OID 16705)
-- Name: suppliesrequestedtable suppliesrequested_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT suppliesrequested_pkey PRIMARY KEY (suppliesrequestedid);


--
-- TOC entry 3078 (class 2606 OID 16673)
-- Name: suppliestreatmenttable suppliestreatment_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.suppliestreatmenttable
    ADD CONSTRAINT suppliestreatment_pkey PRIMARY KEY (suppliestreatmentid, clinicalid);


--
-- TOC entry 3066 (class 2606 OID 16579)
-- Name: treatmenttable treatment_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.treatmenttable
    ADD CONSTRAINT treatment_pkey PRIMARY KEY (treatmentid, clinicalid);


--
-- TOC entry 3042 (class 2606 OID 16401)
-- Name: usertable user_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usertable
    ADD CONSTRAINT user_pkey PRIMARY KEY (usernumber);


--
-- TOC entry 3073 (class 1259 OID 16666)
-- Name: authorization_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX authorization_index ON public.authorizationtable USING btree (sterilizationid, remissionid);


--
-- TOC entry 3067 (class 1259 OID 16619)
-- Name: box_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX box_index ON public.boxtable USING btree (boxid);


--
-- TOC entry 3046 (class 1259 OID 16434)
-- Name: clinical_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX clinical_index ON public.clinicaltable USING btree (clinicalid);


--
-- TOC entry 3047 (class 1259 OID 16435)
-- Name: clinical_index2; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX clinical_index2 ON public.clinicaltable USING btree (clinicalspecialty varchar_ops);


--
-- TOC entry 3061 (class 1259 OID 16571)
-- Name: clinichistory_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX clinichistory_index ON public.clinichistorytable USING btree (remissionid, patientid, studentid);


--
-- TOC entry 3043 (class 1259 OID 16421)
-- Name: log_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX log_index ON public.logtable USING btree (loguser, logdate);


--
-- TOC entry 3055 (class 1259 OID 16517)
-- Name: patientadmission_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX patientadmission_index ON public.patientadmissiontable USING btree (patientadmissionid, patientid);


--
-- TOC entry 3085 (class 1259 OID 16743)
-- Name: radiology_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX radiology_index ON public.radiologytable USING btree (radiologyid, userid, boxid);


--
-- TOC entry 3058 (class 1259 OID 16538)
-- Name: remission_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX remission_index ON public.remissiontable USING btree (remissionid, admissionid, patientid);


--
-- TOC entry 3050 (class 1259 OID 16456)
-- Name: specialty_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX specialty_index ON public.specialtytable USING btree (userid, clinicalid);


--
-- TOC entry 3070 (class 1259 OID 16646)
-- Name: sterilization_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sterilization_index ON public.sterilizationtable USING btree (sterilizationid);


--
-- TOC entry 3079 (class 1259 OID 16693)
-- Name: supplies_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX supplies_index ON public.suppliestable USING btree (suppliesid, suppliestreatmentid, clinicalid);


--
-- TOC entry 3082 (class 1259 OID 16726)
-- Name: suppliesrequested_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX suppliesrequested_index ON public.suppliesrequestedtable USING btree (suppliesrequestedid);


--
-- TOC entry 3076 (class 1259 OID 16679)
-- Name: suppliestreatment_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX suppliestreatment_index ON public.suppliestreatmenttable USING btree (suppliestreatmentid, clinicalid);


--
-- TOC entry 3064 (class 1259 OID 16585)
-- Name: treatment_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX treatment_index ON public.treatmenttable USING btree (treatmentid, clinicalid);


--
-- TOC entry 3039 (class 1259 OID 16402)
-- Name: user_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX user_index ON public.usertable USING btree (usernumber);


--
-- TOC entry 3040 (class 1259 OID 16403)
-- Name: user_index2; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX user_index2 ON public.usertable USING btree (userci varchar_ops, username varchar_ops);


--
-- TOC entry 3115 (class 2606 OID 16738)
-- Name: radiologytable box_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.radiologytable
    ADD CONSTRAINT box_fk FOREIGN KEY (boxid) REFERENCES public.boxtable(boxid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3090 (class 2606 OID 16451)
-- Name: specialtytable clinical_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.specialtytable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid) REFERENCES public.clinicaltable(clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3095 (class 2606 OID 16533)
-- Name: remissiontable clinical_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remissiontable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid) REFERENCES public.clinicaltable(clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3099 (class 2606 OID 16580)
-- Name: treatmenttable clinical_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.treatmenttable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid) REFERENCES public.clinicaltable(clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3104 (class 2606 OID 16636)
-- Name: sterilizationtable clinical_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sterilizationtable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid) REFERENCES public.clinicaltable(clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3108 (class 2606 OID 16674)
-- Name: suppliestreatmenttable clinical_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.suppliestreatmenttable
    ADD CONSTRAINT clinical_fk FOREIGN KEY (clinicalid) REFERENCES public.clinicaltable(clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3102 (class 2606 OID 16614)
-- Name: boxtable clinichistory_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boxtable
    ADD CONSTRAINT clinichistory_fk FOREIGN KEY (remissionid) REFERENCES public.clinichistorytable(remissionid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3106 (class 2606 OID 16656)
-- Name: authorizationtable clinichistory_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.authorizationtable
    ADD CONSTRAINT clinichistory_fk FOREIGN KEY (remissionid) REFERENCES public.clinichistorytable(remissionid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3113 (class 2606 OID 16721)
-- Name: suppliesrequestedtable clinichistory_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT clinichistory_fk FOREIGN KEY (remissionid) REFERENCES public.clinichistorytable(remissionid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3088 (class 2606 OID 16416)
-- Name: logtable loguser; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logtable
    ADD CONSTRAINT loguser FOREIGN KEY (loguser) REFERENCES public.usertable(usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3091 (class 2606 OID 16502)
-- Name: patientadmissiontable patientadmission; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patientadmissiontable
    ADD CONSTRAINT patientadmission FOREIGN KEY (patientid) REFERENCES public.patienttable(patientid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3094 (class 2606 OID 16528)
-- Name: remissiontable patientadmission_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.remissiontable
    ADD CONSTRAINT patientadmission_fk FOREIGN KEY (admissionid, patientid) REFERENCES public.patientadmissiontable(patientadmissionid, patientid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3098 (class 2606 OID 16566)
-- Name: clinichistorytable remission_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clinichistorytable
    ADD CONSTRAINT remission_fk FOREIGN KEY (remissionid, admissionid, patientid, clinicalid) REFERENCES public.remissiontable(remissionid, admissionid, patientid, clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3097 (class 2606 OID 16561)
-- Name: clinichistorytable specialty2_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clinichistorytable
    ADD CONSTRAINT specialty2_fk FOREIGN KEY (teacherid, teacherclinicalid) REFERENCES public.specialtytable(userid, clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3092 (class 2606 OID 16507)
-- Name: patientadmissiontable specialty_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patientadmissiontable
    ADD CONSTRAINT specialty_fk FOREIGN KEY (studentid, studentspecialty) REFERENCES public.specialtytable(userid, clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3096 (class 2606 OID 16556)
-- Name: clinichistorytable specialty_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clinichistorytable
    ADD CONSTRAINT specialty_fk FOREIGN KEY (studentid, studentclinicalid) REFERENCES public.specialtytable(userid, clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3093 (class 2606 OID 16512)
-- Name: patientadmissiontable specialty_fk2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.patientadmissiontable
    ADD CONSTRAINT specialty_fk2 FOREIGN KEY (responsibleid, responsiblespecialty) REFERENCES public.specialtytable(userid, clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3107 (class 2606 OID 16661)
-- Name: authorizationtable sterilization_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.authorizationtable
    ADD CONSTRAINT sterilization_fk FOREIGN KEY (sterilizationid) REFERENCES public.sterilizationtable(sterilizationid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3103 (class 2606 OID 16631)
-- Name: sterilizationtable studentuser_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sterilizationtable
    ADD CONSTRAINT studentuser_fk FOREIGN KEY (studentid) REFERENCES public.usertable(usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3109 (class 2606 OID 16688)
-- Name: suppliestable suppliestreatment_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.suppliestable
    ADD CONSTRAINT suppliestreatment_fk FOREIGN KEY (suppliestreatmentid, clinicalid) REFERENCES public.suppliestreatmenttable(suppliestreatmentid, clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3111 (class 2606 OID 16711)
-- Name: suppliesrequestedtable suppliestreatment_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT suppliestreatment_fk FOREIGN KEY (suppliestreatmentid, clinicalid) REFERENCES public.suppliestreatmenttable(suppliestreatmentid, clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3100 (class 2606 OID 16604)
-- Name: boxtable treatment_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boxtable
    ADD CONSTRAINT treatment_fk FOREIGN KEY (treatmentid, clinicalid) REFERENCES public.treatmenttable(treatmentid, clinicalid) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3089 (class 2606 OID 16446)
-- Name: specialtytable user_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.specialtytable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid) REFERENCES public.usertable(usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3101 (class 2606 OID 16609)
-- Name: boxtable user_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.boxtable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid) REFERENCES public.usertable(usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3105 (class 2606 OID 16641)
-- Name: sterilizationtable user_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sterilizationtable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid) REFERENCES public.usertable(usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3112 (class 2606 OID 16716)
-- Name: suppliesrequestedtable user_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid) REFERENCES public.usertable(usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3114 (class 2606 OID 16733)
-- Name: radiologytable user_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.radiologytable
    ADD CONSTRAINT user_fk FOREIGN KEY (userid) REFERENCES public.usertable(usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3110 (class 2606 OID 16706)
-- Name: suppliesrequestedtable userteacher_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.suppliesrequestedtable
    ADD CONSTRAINT userteacher_fk FOREIGN KEY (teacherid) REFERENCES public.usertable(usernumber) ON UPDATE CASCADE ON DELETE CASCADE;


-- Completed on 2026-09-15 12:04:28 -04

--
-- PostgreSQL database dump complete
--

