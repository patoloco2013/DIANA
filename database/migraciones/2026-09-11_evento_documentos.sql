-- =====================================================================
-- Migración: archivos adjuntos del evento (convocatoria, programa,
-- presentaciones, etc.), independientes de la galería de imágenes.
-- Aplicar después de 2026-09-11_disciplinas_asistencia_fases.sql.
-- =====================================================================
USE diana;

CREATE TABLE evento_documentos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id      INT UNSIGNED NOT NULL,
    evento_id       INT UNSIGNED NOT NULL,
    tipo            VARCHAR(30)  NOT NULL,
    descripcion     VARCHAR(150) NULL,
    archivo         VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(150) NOT NULL,
    mime            VARCHAR(80)  NOT NULL,
    tamano          INT UNSIGNED NOT NULL,
    subido_por      INT UNSIGNED NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evento_documentos (evento_id, tipo),
    CONSTRAINT fk_edoc_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_edoc_evento  FOREIGN KEY (evento_id)  REFERENCES eventos(id) ON DELETE CASCADE,
    CONSTRAINT fk_edoc_usuario FOREIGN KEY (subido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;
