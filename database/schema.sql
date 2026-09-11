-- =====================================================================
-- DIANA — Esquema de base de datos (MySQL 8 / MariaDB 10.6+)
-- Multi-colegio: una sola base de datos, todo cuelga de colegios.id
-- Reemplaza el modelo SIE de una BD por colegio y credenciales en código.
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS diana CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE diana;

-- ---------------------------------------------------------------------
-- Colegios (tenants). Sustituye los if($coll==2) de con2.php.
-- ---------------------------------------------------------------------
CREATE TABLE colegios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave           VARCHAR(20)  NOT NULL UNIQUE,      -- ej. MOR, HGO
    nombre          VARCHAR(150) NOT NULL,             -- razón social / nombre completo
    nombre_corto    VARCHAR(60)  NOT NULL,             -- para la barra superior
    ciudad          VARCHAR(80)  NULL,
    email_contacto  VARCHAR(120) NULL,
    telefono        VARCHAR(30)  NULL,
    color_primario  CHAR(7)      NOT NULL DEFAULT '#1f0512',  -- tema por colegio
    logo_url        VARCHAR(255) NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Roles y permisos. Sustituye niveles + permisos(k1..kN) del SIE.
-- permisos = arreglo JSON de módulos permitidos; ["*"] = todos.
-- Módulos válidos: dashboard, socios, eventos, registro, cuentas,
--                  reportes, usuarios, colegios
-- ---------------------------------------------------------------------
CREATE TABLE roles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    permisos    JSON NOT NULL,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Usuarios del sistema (staff). colegio_id NULL = acceso a todos
-- los colegios (superadmin); de lo contrario queda limitado al suyo.
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id     INT UNSIGNED NULL,
    rol_id         INT UNSIGNED NOT NULL,
    usuario        VARCHAR(50)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,             -- password_hash() / bcrypt-argon2
    nombre         VARCHAR(120) NOT NULL,
    email          VARCHAR(120) NULL,
    telefono       VARCHAR(30)  NULL,
    activo         TINYINT(1)   NOT NULL DEFAULT 1,
    ultimo_acceso  DATETIME     NULL,
    creado_en      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_usuarios_rol     FOREIGN KEY (rol_id)     REFERENCES roles(id)
) ENGINE=InnoDB;

-- Bitácora de intentos de acceso (para bloqueo temporal por fuerza bruta)
CREATE TABLE login_intentos (
    id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario   VARCHAR(50)  NOT NULL,
    ip        VARCHAR(45)  NOT NULL,
    exitoso   TINYINT(1)   NOT NULL DEFAULT 0,
    creado_en TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_intentos_usuario (usuario, creado_en)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Socios (miembros del colegio). Antes: socios(cid, prof, nombrec...)
-- numero es el número de socio visible, único por colegio.
-- ---------------------------------------------------------------------
CREATE TABLE socios (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id        INT UNSIGNED NOT NULL,
    numero            VARCHAR(20)  NOT NULL,
    titulo            VARCHAR(30)  NULL,              -- C.P., L.C., Dr., etc.
    nombre            VARCHAR(100) NOT NULL,          -- nombre(s)
    apellido_paterno  VARCHAR(60)  NULL,
    apellido_materno  VARCHAR(60)  NULL,
    -- Columna generada: se usa en listados, búsquedas y reportes
    nombre_completo   VARCHAR(230)
        AS (TRIM(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno))) STORED,
    rfc               VARCHAR(13)  NULL,
    tipo              ENUM('normal','estudiante','vitalicio','honorario','no_socio') NOT NULL DEFAULT 'normal',
    genero            ENUM('sin_especificar','femenino','masculino','otro') NOT NULL DEFAULT 'sin_especificar',
    cumple_dia        TINYINT UNSIGNED NULL,
    cumple_mes        TINYINT UNSIGNED NULL,
    limite_credito    DECIMAL(10,2) NOT NULL DEFAULT 0,
    paga_cuota_anual  TINYINT(1)   NOT NULL DEFAULT 1,
    foto              VARCHAR(255) NULL,              -- ruta relativa en storage/uploads
    direccion         VARCHAR(200) NULL,
    colonia           VARCHAR(100) NULL,
    codigo_postal     VARCHAR(10)  NULL,
    localidad         VARCHAR(100) NULL,
    ciudad            VARCHAR(100) NULL,
    estado            VARCHAR(60)  NULL,
    email             VARCHAR(120) NULL,
    email2            VARCHAR(120) NULL,
    telefono_oficina  VARCHAR(30)  NULL,
    telefono_oficina2 VARCHAR(30)  NULL,
    celular           VARCHAR(30)  NULL,
    estatus           ENUM('activo','suspendido','baja') NOT NULL DEFAULT 'activo',
    observaciones     TEXT NULL,
    creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_socios_colegio_numero (colegio_id, numero),
    INDEX idx_socios_nombre (colegio_id, nombre_completo),
    CONSTRAINT fk_socios_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Documentos digitales del socio (acta, cédula, CV...). El archivo vive
-- fuera de public/ con nombre aleatorio; se sirve vía SociosController.
-- ---------------------------------------------------------------------
CREATE TABLE socio_documentos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id      INT UNSIGNED NOT NULL,
    socio_id        INT UNSIGNED NOT NULL,
    tipo            VARCHAR(30)  NOT NULL,             -- clave de Catalogos::TIPOS_DOCUMENTO
    descripcion     VARCHAR(150) NULL,
    archivo         VARCHAR(255) NOT NULL,             -- ruta relativa en storage/uploads
    nombre_original VARCHAR(150) NOT NULL,
    mime            VARCHAR(80)  NOT NULL,
    tamano          INT UNSIGNED NOT NULL,             -- bytes
    subido_por      INT UNSIGNED NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_socio_documentos (socio_id, tipo),
    CONSTRAINT fk_sdoc_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_sdoc_socio   FOREIGN KEY (socio_id)   REFERENCES socios(id),
    CONSTRAINT fk_sdoc_usuario FOREIGN KEY (subido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Perfiles fiscales del socio (varios por socio) para facturación CFDI 4.0.
-- ---------------------------------------------------------------------
CREATE TABLE socio_perfiles_fiscales (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id        INT UNSIGNED NOT NULL,
    socio_id          INT UNSIGNED NOT NULL,
    alias             VARCHAR(60)  NOT NULL,           -- "Personal", "Despacho"...
    razon_social      VARCHAR(254) NOT NULL,           -- tal como aparece en la CSF
    rfc               VARCHAR(13)  NOT NULL,
    regimen_fiscal    CHAR(3)      NOT NULL,           -- c_RegimenFiscal
    uso_cfdi          VARCHAR(4)   NOT NULL DEFAULT 'G03', -- c_UsoCFDI
    codigo_postal     CHAR(5)      NOT NULL,           -- domicilio fiscal del receptor
    email_facturacion VARCHAR(120) NULL,
    predeterminado    TINYINT(1)   NOT NULL DEFAULT 0,
    activo            TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_perfiles_socio (socio_id, predeterminado),
    CONSTRAINT fk_spf_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_spf_socio   FOREIGN KEY (socio_id)   REFERENCES socios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Eventos (cursos, congresos). Antes: eventos(eid, evento, fechai...)
-- ---------------------------------------------------------------------
CREATE TABLE eventos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id     INT UNSIGNED NOT NULL,
    nombre         VARCHAR(200) NOT NULL,
    tipo           VARCHAR(60)  NULL,                 -- curso, congreso, taller...
    modalidad      ENUM('presencial','linea','hibrido') NOT NULL DEFAULT 'presencial',
    fecha_inicio   DATE NOT NULL,
    fecha_fin      DATE NULL,
    hora_inicio    TIME NULL,
    hora_fin       TIME NULL,
    sede           VARCHAR(150) NULL,                 -- solo presencial/híbrido
    expositores    VARCHAR(255) NULL,
    enlace_sesion  VARCHAR(500) NULL,                 -- Webex, Zoom, Teams...
    clave_sesion   VARCHAR(60)  NULL,
    -- 'evento': los puntos DPC se fijan para todo el evento.
    -- 'modulo': cada módulo tiene sus propios puntos y el total es su suma.
    esquema_puntos ENUM('evento','modulo') NOT NULL DEFAULT 'evento',
    cupo           INT UNSIGNED NULL,
    descripcion    TEXT NULL,
    estatus        ENUM('borrador','publicado','cerrado','cancelado') NOT NULL DEFAULT 'publicado',
    creado_por     INT UNSIGNED NULL,
    creado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_eventos_colegio_fecha (colegio_id, fecha_inicio),
    CONSTRAINT fk_eventos_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_eventos_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Módulos del evento (p. ej. un módulo por día en un diplomado).
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- Catálogo de disciplinas para puntos DPC, administrado por cada colegio.
-- Nunca se elimina (solo se desactiva): borrarla rompería los puntos ya
-- otorgados en eventos pasados. DisciplinasController::SEMILLA la puebla
-- al dar de alta un colegio nuevo; desde ahí se agregan o renombran más.
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

-- ---------------------------------------------------------------------
-- Puntos DPC por disciplina. modulo_id NULL = puntos de todo el evento;
-- con modulo_id = puntos de ese módulo (esquema_puntos = 'modulo').
-- ---------------------------------------------------------------------
CREATE TABLE evento_puntos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id    INT UNSIGNED NOT NULL,
    evento_id     INT UNSIGNED NOT NULL,
    modulo_id     INT UNSIGNED NULL,
    disciplina_id INT UNSIGNED NOT NULL,
    puntos        DECIMAL(6,2) NOT NULL DEFAULT 0,
    INDEX idx_puntos_evento (evento_id, modulo_id),
    CONSTRAINT fk_epun_colegio    FOREIGN KEY (colegio_id)    REFERENCES colegios(id),
    CONSTRAINT fk_epun_evento     FOREIGN KEY (evento_id)     REFERENCES eventos(id) ON DELETE CASCADE,
    CONSTRAINT fk_epun_modulo     FOREIGN KEY (modulo_id)     REFERENCES evento_modulos(id) ON DELETE CASCADE,
    CONSTRAINT fk_epun_disciplina FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Precio por categoría de asistente y modalidad.
-- ---------------------------------------------------------------------
CREATE TABLE evento_precios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id INT UNSIGNED NOT NULL,
    evento_id  INT UNSIGNED NOT NULL,
    categoria  VARCHAR(20) NOT NULL,                  -- clave de Catalogos::CATEGORIAS_ASISTENTE
    modalidad  ENUM('presencial','linea') NOT NULL DEFAULT 'presencial',
    precio     DECIMAL(10,2) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_precio (evento_id, categoria, modalidad),
    CONSTRAINT fk_epre_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_epre_evento  FOREIGN KEY (evento_id)  REFERENCES eventos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Imágenes del evento: una principal y las demás opcionales (galería).
-- ---------------------------------------------------------------------
CREATE TABLE evento_imagenes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id      INT UNSIGNED NOT NULL,
    evento_id       INT UNSIGNED NOT NULL,
    archivo         VARCHAR(255) NOT NULL,            -- ruta relativa en storage/uploads
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

-- ---------------------------------------------------------------------
-- Asistencias a eventos, en dos fases:
--   1. Confirmación (alta aquí): se genera el cargo, asistio = 0.
--   2. Asistencia (registro/asistencia): al pasar lista se marca asistio = 1,
--      ahí se otorgan los puntos_dpc (no antes: solo se ganan si asistió).
-- socio_id NULL = asistente del público en general (nombre en asistente).
-- ---------------------------------------------------------------------
CREATE TABLE asistencias (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id    INT UNSIGNED NOT NULL,
    evento_id     INT UNSIGNED NOT NULL,
    socio_id      INT UNSIGNED NULL,
    asistente     VARCHAR(150) NULL,                  -- nombre si no es socio
    email         VARCHAR(120) NULL,                  -- para enviarle el enlace en línea
    tipo          ENUM('socio','publico') NOT NULL DEFAULT 'socio',
    categoria     VARCHAR(20)  NOT NULL DEFAULT 'socio',-- categoría de precio aplicada
    modalidad     ENUM('presencial','linea') NOT NULL DEFAULT 'presencial',
    asistio       TINYINT(1)   NOT NULL DEFAULT 0,     -- fase 2: pasó lista
    fecha_asistio DATETIME NULL,
    lugar         VARCHAR(50)  NULL,                  -- mesa/lugar asignado al pasar lista
    comentarios   VARCHAR(255) NULL,
    puntos_dpc    DECIMAL(6,2) NOT NULL DEFAULT 0,     -- otorgados al marcar asistio = 1
    creado_por    INT UNSIGNED NULL,
    asistio_por   INT UNSIGNED NULL,
    creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asistencia_evento_socio (evento_id, socio_id),
    INDEX idx_asistencias_colegio (colegio_id, evento_id),
    CONSTRAINT fk_asist_colegio  FOREIGN KEY (colegio_id)  REFERENCES colegios(id),
    CONSTRAINT fk_asist_evento   FOREIGN KEY (evento_id)   REFERENCES eventos(id),
    CONSTRAINT fk_asist_socio    FOREIGN KEY (socio_id)    REFERENCES socios(id),
    CONSTRAINT fk_asist_asist_por FOREIGN KEY (asistio_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Cuentas: cargos y pagos por socio. Antes: cuentas(idsoc, doc, tdoc...)
-- El saldo del socio = SUM(cargos) - SUM(pagos); no se guarda redundante.
-- ---------------------------------------------------------------------
CREATE TABLE cuentas (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colegio_id        INT UNSIGNED NOT NULL,
    socio_id          INT UNSIGNED NOT NULL,
    evento_id         INT UNSIGNED NULL,              -- si el cargo viene de un evento
    tipo              ENUM('cargo','pago') NOT NULL,
    concepto          VARCHAR(200) NOT NULL,          -- cuota anual, inscripción evento...
    referencia        VARCHAR(60)  NULL,              -- folio, no. de recibo
    importe           DECIMAL(10,2) NOT NULL,
    fecha             DATE NOT NULL,
    fecha_vencimiento DATE NULL,                      -- solo cargos
    forma_pago        ENUM('efectivo','transferencia','tarjeta','cheque','otro') NULL, -- solo pagos
    estatus           ENUM('vigente','cancelado') NOT NULL DEFAULT 'vigente',
    observaciones     VARCHAR(255) NULL,
    creado_por        INT UNSIGNED NULL,
    creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cuentas_socio (socio_id, fecha),
    INDEX idx_cuentas_colegio_fecha (colegio_id, fecha),
    CONSTRAINT fk_cuentas_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id),
    CONSTRAINT fk_cuentas_socio   FOREIGN KEY (socio_id)   REFERENCES socios(id),
    CONSTRAINT fk_cuentas_evento  FOREIGN KEY (evento_id)  REFERENCES eventos(id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Datos iniciales
-- =====================================================================
INSERT INTO colegios (clave, nombre, nombre_corto, ciudad, color_primario) VALUES
('MOR', 'Colegio de Contadores Públicos de Michoacán, A.C.', 'CCP Michoacán', 'Morelia',  '#1f0512'),
('HGO', 'Colegio de Contadores Públicos de Hidalgo, A.C.',   'CCP Hidalgo',   'Pachuca',  '#0d2b45');

INSERT INTO roles (nombre, descripcion, permisos) VALUES
('Administrador', 'Acceso total al sistema',                              '["*"]'),
('Operador',      'Captura de socios, eventos, registro y cobranza',      '["dashboard","socios","eventos","registro","cuentas"]'),
('Consulta',      'Solo lectura de reportes y tablero',                   '["dashboard","reportes"]');

-- Usuario inicial: admin / Diana.2026*  — CAMBIAR el password al primer acceso.
-- colegio_id NULL = superadmin con acceso a todos los colegios.
INSERT INTO usuarios (colegio_id, rol_id, usuario, password_hash, nombre, email) VALUES
(NULL, 1, 'admin', '$2y$12$.AEHDS8z58806dlpJ74TJeNiS6PGfOJm2TmLo4ilAsd3ZGV1bdqSC', 'Administrador DIANA', NULL);

-- Catálogo inicial de disciplinas DPC para cada colegio (editable después
-- desde Eventos → Disciplinas; ver DisciplinasController::SEMILLA).
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
