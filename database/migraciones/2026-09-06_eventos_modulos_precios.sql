-- =====================================================================
-- Migración: eventos con modalidad y enlace en línea, galería de
-- imágenes, precios por categoría y modalidad, módulos y puntos DPC
-- por disciplina. Conserva los datos existentes:
--   puntos_epc     -> evento_puntos (disciplina "otras", nivel evento)
--   precio_socio   -> evento_precios (socio, presencial)
--   precio_publico -> evento_precios (no_socio, presencial)
-- Aplicar después de 2026-09-06_socios_ampliado.sql.
-- =====================================================================
USE diana;

-- --- Eventos: nuevas columnas ---------------------------------------
ALTER TABLE eventos
    ADD COLUMN modalidad ENUM('presencial','linea','hibrido') NOT NULL DEFAULT 'presencial' AFTER tipo,
    ADD COLUMN enlace_sesion VARCHAR(500) NULL AFTER expositores,
    ADD COLUMN clave_sesion  VARCHAR(60)  NULL AFTER enlace_sesion,
    ADD COLUMN esquema_puntos ENUM('evento','modulo') NOT NULL DEFAULT 'evento' AFTER clave_sesion;

-- --- Tablas nuevas ---------------------------------------------------
CREATE TABLE evento_modulos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id  INT UNSIGNED NOT NULL,
    evento_id   INT UNSIGNED NOT NULL,
    orden       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    nombre      VARCHAR(200) NOT NULL,
    fecha       DATE NULL,
    hora_inicio TIME NULL,
    hora_fin    TIME NULL,
    expositores VARCHAR(255) NULL,
    sede        VARCHAR(150) NULL,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_modulos_evento (evento_id, orden),
    CONSTRAINT fk_emod_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_emod_evento  FOREIGN KEY (evento_id)  REFERENCES eventos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evento_puntos (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id INT UNSIGNED NOT NULL,
    evento_id  INT UNSIGNED NOT NULL,
    modulo_id  INT UNSIGNED NULL,
    disciplina VARCHAR(30)  NOT NULL,
    puntos     DECIMAL(6,2) NOT NULL DEFAULT 0,
    INDEX idx_puntos_evento (evento_id, modulo_id),
    CONSTRAINT fk_epun_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_epun_evento  FOREIGN KEY (evento_id)  REFERENCES eventos(id) ON DELETE CASCADE,
    CONSTRAINT fk_epun_modulo  FOREIGN KEY (modulo_id)  REFERENCES evento_modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evento_precios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id INT UNSIGNED NOT NULL,
    evento_id  INT UNSIGNED NOT NULL,
    categoria  VARCHAR(20) NOT NULL,
    modalidad  ENUM('presencial','linea') NOT NULL DEFAULT 'presencial',
    precio     DECIMAL(10,2) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_precio (evento_id, categoria, modalidad),
    CONSTRAINT fk_epre_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_epre_evento  FOREIGN KEY (evento_id)  REFERENCES eventos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evento_imagenes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id      INT UNSIGNED NOT NULL,
    evento_id       INT UNSIGNED NOT NULL,
    archivo         VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(150) NOT NULL,
    mime            VARCHAR(80)  NOT NULL,
    tamano          INT UNSIGNED NOT NULL,
    titulo          VARCHAR(150) NULL,
    principal       TINYINT(1)   NOT NULL DEFAULT 0,
    orden           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    subido_por      INT UNSIGNED NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_imagenes_evento (evento_id, principal, orden),
    CONSTRAINT fk_eimg_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_eimg_evento  FOREIGN KEY (evento_id)  REFERENCES eventos(id) ON DELETE CASCADE,
    CONSTRAINT fk_eimg_usuario FOREIGN KEY (subido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- --- Migrar puntos y precios existentes ------------------------------
INSERT INTO evento_puntos (colegio_id, evento_id, modulo_id, disciplina, puntos)
SELECT colegio_id, id, NULL, 'otras', puntos_epc FROM eventos WHERE puntos_epc > 0;

INSERT INTO evento_precios (colegio_id, evento_id, categoria, modalidad, precio)
SELECT colegio_id, id, 'socio', 'presencial', precio_socio FROM eventos WHERE precio_socio > 0;

INSERT INTO evento_precios (colegio_id, evento_id, categoria, modalidad, precio)
SELECT colegio_id, id, 'no_socio', 'presencial', precio_publico FROM eventos WHERE precio_publico > 0;

ALTER TABLE eventos
    DROP COLUMN puntos_epc,
    DROP COLUMN precio_socio,
    DROP COLUMN precio_publico;

-- --- Asistencias -----------------------------------------------------
ALTER TABLE asistencias
    ADD COLUMN email VARCHAR(120) NULL AFTER asistente,
    ADD COLUMN categoria VARCHAR(20) NOT NULL DEFAULT 'socio' AFTER tipo,
    ADD COLUMN modalidad ENUM('presencial','linea') NOT NULL DEFAULT 'presencial' AFTER categoria,
    CHANGE puntos_epc puntos_dpc DECIMAL(6,2) NOT NULL DEFAULT 0;

UPDATE asistencias SET categoria = 'no_socio' WHERE tipo = 'publico';
