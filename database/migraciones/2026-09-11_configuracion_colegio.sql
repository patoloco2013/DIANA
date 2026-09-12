-- =====================================================================
-- Migración: Configuración por colegio (Fiscal/CSD, Correo SMTP, SAT PAC,
-- Constancia). Aplicar después de 2026-09-11_evento_documentos.sql.
--
-- IMPORTANTE: antes de usar la sección Configuración, agregue en
-- config/config.php la clave 'app.clave_cifrado' (ver config.example.php);
-- sin ella, guardar u leer estos datos lanza un error explícito.
-- =====================================================================
USE diana;

CREATE TABLE colegio_fiscal (
    colegio_id        INT UNSIGNED PRIMARY KEY,
    razon_social      VARCHAR(254) NULL,
    rfc               VARCHAR(13)  NULL,
    regimen_fiscal    CHAR(3)      NULL,
    calle             VARCHAR(150) NULL,
    numero_ext        VARCHAR(20)  NULL,
    numero_int        VARCHAR(20)  NULL,
    colonia           VARCHAR(100) NULL,
    municipio         VARCHAR(100) NULL,
    estado            VARCHAR(60)  NULL,
    codigo_postal     CHAR(5)      NULL,
    csd_cer_archivo   VARCHAR(255) NULL,
    csd_key_archivo   VARCHAR(255) NULL,
    csd_key_password  VARCHAR(255) NULL,
    csd_numero_serie  VARCHAR(40)  NULL,
    csd_titular       VARCHAR(150) NULL,
    csd_vigente_desde DATE NULL,
    csd_vigente_hasta DATE NULL,
    actualizado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cfis_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id)
) ENGINE=InnoDB;

CREATE TABLE colegio_correo (
    colegio_id        INT UNSIGNED PRIMARY KEY,
    smtp_host         VARCHAR(150) NULL,
    smtp_puerto       SMALLINT UNSIGNED NULL DEFAULT 587,
    smtp_seguridad    ENUM('ninguna','tls','ssl') NOT NULL DEFAULT 'tls',
    smtp_usuario      VARCHAR(150) NULL,
    smtp_password     VARCHAR(255) NULL,
    remitente_nombre  VARCHAR(150) NULL,
    remitente_email   VARCHAR(120) NULL,
    actualizado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ccor_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id)
) ENGINE=InnoDB;

CREATE TABLE colegio_pac (
    colegio_id      INT UNSIGNED PRIMARY KEY,
    proveedor       VARCHAR(30)  NOT NULL DEFAULT 'timbox',
    modo            ENUM('pruebas','produccion') NOT NULL DEFAULT 'pruebas',
    usuario         VARCHAR(150) NULL,
    password        VARCHAR(255) NULL,
    api_key         VARCHAR(255) NULL,
    url_servicio    VARCHAR(255) NULL,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cpac_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id)
) ENGINE=InnoDB;

CREATE TABLE colegio_constancia (
    colegio_id      INT UNSIGNED PRIMARY KEY,
    titulo          VARCHAR(150) NOT NULL DEFAULT 'Constancia de participación',
    cuerpo          TEXT NULL,
    orientacion     ENUM('horizontal','vertical') NOT NULL DEFAULT 'horizontal',
    imagen          VARCHAR(255) NULL,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ccon_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id)
) ENGINE=InnoDB;

INSERT INTO colegio_constancia (colegio_id, titulo, cuerpo, orientacion)
SELECT id, 'Constancia de participación',
       'Por medio de la presente, {colegio} otorga la presente constancia a {socio} por su participación en {evento}, celebrado el {fecha_inicio}, con una duración equivalente a {puntos_dpc} puntos de Desarrollo Profesional Continuo (DPC).',
       'horizontal'
FROM colegios;
