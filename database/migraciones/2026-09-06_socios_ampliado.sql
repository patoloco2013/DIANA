-- =====================================================================
-- Migración: socios ampliado (datos generales, documentos y perfiles
-- fiscales). Aplicar SOLO a instalaciones creadas con el schema.sql
-- anterior a esta fecha. Las instalaciones nuevas ya lo traen.
-- =====================================================================
USE diana;

ALTER TABLE socios
    MODIFY nombre VARCHAR(100) NOT NULL,
    ADD COLUMN apellido_paterno VARCHAR(60) NULL AFTER nombre,
    ADD COLUMN apellido_materno VARCHAR(60) NULL AFTER apellido_paterno,
    ADD COLUMN nombre_completo VARCHAR(230)
        AS (TRIM(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno))) STORED AFTER apellido_materno,
    ADD COLUMN tipo ENUM('normal','estudiante','vitalicio','honorario','no_socio') NOT NULL DEFAULT 'normal' AFTER rfc,
    ADD COLUMN genero ENUM('sin_especificar','femenino','masculino','otro') NOT NULL DEFAULT 'sin_especificar' AFTER tipo,
    ADD COLUMN cumple_dia TINYINT UNSIGNED NULL AFTER genero,
    ADD COLUMN cumple_mes TINYINT UNSIGNED NULL AFTER cumple_dia,
    ADD COLUMN limite_credito DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER cumple_mes,
    ADD COLUMN paga_cuota_anual TINYINT(1) NOT NULL DEFAULT 1 AFTER limite_credito,
    ADD COLUMN foto VARCHAR(255) NULL AFTER paga_cuota_anual,
    ADD COLUMN direccion VARCHAR(200) NULL AFTER foto,
    ADD COLUMN colonia VARCHAR(100) NULL AFTER direccion,
    ADD COLUMN codigo_postal VARCHAR(10) NULL AFTER colonia,
    ADD COLUMN localidad VARCHAR(100) NULL AFTER codigo_postal,
    ADD COLUMN ciudad VARCHAR(100) NULL AFTER localidad,
    ADD COLUMN estado VARCHAR(60) NULL AFTER ciudad,
    ADD COLUMN email2 VARCHAR(120) NULL AFTER email,
    CHANGE telefono celular VARCHAR(30) NULL,
    ADD COLUMN telefono_oficina VARCHAR(30) NULL AFTER email2,
    ADD COLUMN telefono_oficina2 VARCHAR(30) NULL AFTER telefono_oficina,
    DROP INDEX idx_socios_nombre,
    ADD INDEX idx_socios_nombre (colegio_id, nombre_completo);

CREATE TABLE socio_documentos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id      INT UNSIGNED NOT NULL,
    socio_id        INT UNSIGNED NOT NULL,
    tipo            VARCHAR(30)  NOT NULL,
    descripcion     VARCHAR(150) NULL,
    archivo         VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(150) NOT NULL,
    mime            VARCHAR(80)  NOT NULL,
    tamano          INT UNSIGNED NOT NULL,
    subido_por      INT UNSIGNED NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_socio_documentos (socio_id, tipo),
    CONSTRAINT fk_sdoc_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_sdoc_socio   FOREIGN KEY (socio_id)   REFERENCES socios(id),
    CONSTRAINT fk_sdoc_usuario FOREIGN KEY (subido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE socio_perfiles_fiscales (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id        INT UNSIGNED NOT NULL,
    socio_id          INT UNSIGNED NOT NULL,
    alias             VARCHAR(60)  NOT NULL,
    razon_social      VARCHAR(254) NOT NULL,
    rfc               VARCHAR(13)  NOT NULL,
    regimen_fiscal    CHAR(3)      NOT NULL,
    uso_cfdi          VARCHAR(4)   NOT NULL DEFAULT 'G03',
    codigo_postal     CHAR(5)      NOT NULL,
    email_facturacion VARCHAR(120) NULL,
    predeterminado    TINYINT(1)   NOT NULL DEFAULT 0,
    activo            TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_perfiles_socio (socio_id, predeterminado),
    CONSTRAINT fk_spf_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_spf_socio   FOREIGN KEY (socio_id)   REFERENCES socios(id)
) ENGINE=InnoDB;
