-- =====================================================================
-- Migración: sección SAT (certificados CSD / e.firma por colegio).
-- Aplicar después de 2026-09-11_disciplinas_asistencia_fases.sql.
-- Requiere configurar seguridad.clave_cifrado en config/config.php.
-- =====================================================================
USE diana;

-- ---------------------------------------------------------------------
-- Certificados del SAT del colegio (CSD para facturar, e.firma/FIEL).
-- Los archivos viven fuera de public/; la contraseña de la llave se
-- guarda cifrada con seguridad.clave_cifrado (ver Diana\Core\Cifrado).
-- Solo un certificado activo por colegio y tipo. "Quitar" borra los
-- archivos y la contraseña, pero conserva el renglón como historial.
-- ---------------------------------------------------------------------
CREATE TABLE sat_certificados (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id         INT UNSIGNED NOT NULL,
    tipo               ENUM('csd','fiel') NOT NULL DEFAULT 'csd',
    rfc                VARCHAR(13)  NOT NULL,
    titular            VARCHAR(254) NULL,
    numero_certificado VARCHAR(40)  NOT NULL,          -- 20 dígitos del SAT
    valido_desde       DATETIME     NOT NULL,
    valido_hasta       DATETIME     NOT NULL,
    archivo_cer        VARCHAR(255) NULL,              -- ruta relativa en storage/uploads
    archivo_key        VARCHAR(255) NULL,
    password_cifrada   TEXT         NULL,
    activo             TINYINT(1)   NOT NULL DEFAULT 0, -- el que se usará para timbrar/firmar
    subido_por         INT UNSIGNED NULL,
    creado_en          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    eliminado_en       DATETIME     NULL,
    eliminado_por      INT UNSIGNED NULL,
    INDEX idx_sat_colegio (colegio_id, tipo, activo),
    INDEX idx_sat_numero (colegio_id, numero_certificado),
    CONSTRAINT fk_sat_colegio   FOREIGN KEY (colegio_id)    REFERENCES colegios(id),
    CONSTRAINT fk_sat_subido    FOREIGN KEY (subido_por)    REFERENCES usuarios(id),
    CONSTRAINT fk_sat_eliminado FOREIGN KEY (eliminado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;
