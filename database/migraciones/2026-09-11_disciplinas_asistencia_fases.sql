-- =====================================================================
-- Migración: catálogo de disciplinas DPC (editable, nunca se elimina) y
-- dos fases de asistencia a eventos (Confirmación / Asistencia).
-- Aplicar después de 2026-09-06_eventos_modulos_precios.sql.
-- =====================================================================
USE diana;

-- ---------------------------------------------------------------------
-- 1. Catálogo de disciplinas, uno por colegio. Se siembra con las mismas
--    12 disciplinas que antes venían fijas en el código, para no perder
--    continuidad con los puntos DPC ya capturados.
-- ---------------------------------------------------------------------
CREATE TABLE disciplinas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id INT UNSIGNED NOT NULL,
    nombre     VARCHAR(100) NOT NULL,
    orden      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    activo     TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_disciplinas_colegio_nombre (colegio_id, nombre),
    INDEX idx_disciplinas_colegio (colegio_id, activo, orden),
    CONSTRAINT fk_disc_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id)
) ENGINE=InnoDB;

INSERT INTO disciplinas (colegio_id, nombre, orden, activo)
SELECT c.id, x.nombre, x.orden, 1
FROM colegios c
CROSS JOIN (
    SELECT 1 AS orden, 'Fiscal' AS nombre UNION ALL
    SELECT 2, 'Auditoría' UNION ALL
    SELECT 3, 'Contabilidad' UNION ALL
    SELECT 4, 'Finanzas' UNION ALL
    SELECT 5, 'Ética profesional' UNION ALL
    SELECT 6, 'Administración' UNION ALL
    SELECT 7, 'Costos' UNION ALL
    SELECT 8, 'Legal y laboral' UNION ALL
    SELECT 9, 'Tecnologías de información' UNION ALL
    SELECT 10, 'Sector gubernamental' UNION ALL
    SELECT 11, 'Docencia y educación' UNION ALL
    SELECT 12, 'Otras disciplinas'
) x;

-- ---------------------------------------------------------------------
-- 2. evento_puntos.disciplina (varchar fijo) -> disciplina_id (FK editable)
-- ---------------------------------------------------------------------
ALTER TABLE evento_puntos ADD COLUMN disciplina_id INT UNSIGNED NULL AFTER modulo_id;

UPDATE evento_puntos p
JOIN disciplinas d ON d.colegio_id = p.colegio_id AND d.nombre = CASE p.disciplina
    WHEN 'fiscal'         THEN 'Fiscal'
    WHEN 'auditoria'      THEN 'Auditoría'
    WHEN 'contabilidad'   THEN 'Contabilidad'
    WHEN 'finanzas'       THEN 'Finanzas'
    WHEN 'etica'          THEN 'Ética profesional'
    WHEN 'administracion' THEN 'Administración'
    WHEN 'costos'         THEN 'Costos'
    WHEN 'legal'          THEN 'Legal y laboral'
    WHEN 'tecnologia'     THEN 'Tecnologías de información'
    WHEN 'gubernamental'  THEN 'Sector gubernamental'
    WHEN 'educacion'      THEN 'Docencia y educación'
    ELSE 'Otras disciplinas'
END
SET p.disciplina_id = d.id;

-- Cualquier punto que no haya emparejado (no debería ocurrir) cae en "Otras".
UPDATE evento_puntos p
JOIN disciplinas d ON d.colegio_id = p.colegio_id AND d.nombre = 'Otras disciplinas'
SET p.disciplina_id = d.id
WHERE p.disciplina_id IS NULL;

ALTER TABLE evento_puntos
    MODIFY disciplina_id INT UNSIGNED NOT NULL,
    DROP COLUMN disciplina,
    ADD CONSTRAINT fk_epun_disciplina FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id);

-- ---------------------------------------------------------------------
-- 3. Asistencias: fase 2 (pasar lista) separada de la fase 1 (confirmar).
-- ---------------------------------------------------------------------
ALTER TABLE asistencias
    ADD COLUMN asistio TINYINT(1) NOT NULL DEFAULT 0 AFTER modalidad,
    ADD COLUMN fecha_asistio DATETIME NULL AFTER asistio,
    ADD COLUMN lugar VARCHAR(50) NULL AFTER fecha_asistio,
    ADD COLUMN comentarios VARCHAR(255) NULL AFTER lugar,
    ADD COLUMN asistio_por INT UNSIGNED NULL AFTER creado_por,
    ADD CONSTRAINT fk_asist_asist_por FOREIGN KEY (asistio_por) REFERENCES usuarios(id);

-- Continuidad: antes registrar = asistir, así que los registros que ya
-- traían puntos otorgados se consideran asistidos desde su fecha de alta.
UPDATE asistencias SET asistio = 1, fecha_asistio = creado_en WHERE puntos_dpc > 0;
