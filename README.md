# DIANA

Sistema integral para colegios de profesionistas: socios, eventos, registro de
asistencia, cuentas (cargos/pagos) y reportes. **Multi-colegio** desde el diseño:
una sola instalación y base de datos atiende a varios colegios, cada uno con su
clave, colores y padrón independientes.

DIANA es la reescritura desde cero del sistema SIE (`admin.php` →
`sistemaepc3.php`), conservando solo los módulos que realmente se usan.

## Módulos

| Módulo | Descripción |
| --- | --- |
| Dashboard | Indicadores: socios activos, eventos próximos, cartera y cobranza del mes |
| Socios | Padrón por colegio: alta, edición, baja lógica, búsqueda |
| Eventos | Cursos/congresos con puntos EPC, precios y cupo |
| Registro | Asistentes por evento; el cargo al socio se genera automáticamente |
| Cuentas | Estado de cuenta por socio; cargos y pagos, cancelación auditable |
| Reportes | Saldos, morosidad, cobranza por periodo y resultados por evento |
| Usuarios | Staff con roles y permisos por módulo (RBAC) |
| Colegios | Alta y configuración de cada colegio (solo superadmin) |

## Requisitos

- PHP **8.2+** (probado con 8.5) con extensión `pdo_mysql`
- MySQL 8 / MariaDB 10.6+
- Apache con `mod_rewrite` (producción) o el servidor embebido de PHP (desarrollo)

Sin dependencias de Composer: se despliega copiando archivos (compatible con
hosting compartido). Bootstrap 5 e iconos se cargan por CDN.

## Instalación

```bash
# 1. Base de datos (crea la BD "diana", tablas y datos iniciales)
mysql -u root -p < database/schema.sql

# 2. Configuración local (NUNCA se versiona)
cp config/config.example.php config/config.php
#    → editar credenciales de BD y app.url

# 3. Desarrollo
php -S localhost:8080 -t public public/router.php
```

Acceso inicial: usuario `admin`, contraseña `Diana.2026*` — **cámbiela de
inmediato** en Usuarios → editar.

En producción apunte el docroot a `public/` (o suba el contenido de `public/` al
docroot y `app/`, `config/`, `database/` a un nivel NO público).

## Arquitectura

```
public/          docroot: index.php (front controller), .htaccess, assets
app/
  Core/          Router, Database (PDO), Auth, Csrf, View, Controller
  Controllers/   un controlador por módulo
  Views/         vistas PHP + layout responsivo (Bootstrap 5, sin iframes)
config/          config.example.php → copiar a config.php (gitignored)
database/        schema.sql con esquema y datos semilla
```

Ruteo por convención: `/socios/editar/5` → `SociosController::editar('5')`.

### Multi-colegio

- Tabla `colegios`; todas las entidades (`socios`, `eventos`, `cuentas`,
  `asistencias`) llevan `colegio_id`.
- Cada usuario pertenece a un colegio; `colegio_id NULL` = superadmin con
  acceso a todos y selector de colegio en la barra superior.
- Toda consulta filtra por el colegio activo de la sesión.

### Seguridad (vs. SIE)

| SIE (antes) | DIANA (ahora) |
| --- | --- |
| Credenciales y hash md5 guardados en cookies | Sesión de servidor, cookie `HttpOnly` + `SameSite=Lax`, regeneración de ID al entrar |
| `md5()` con sal fija | `password_hash()` (bcrypt/argon2) con rehash transparente |
| SQL con variables interpoladas | PDO con consultas preparadas en el 100 % del código |
| Sin CSRF | Token CSRF obligatorio en todo POST |
| Permisos por columnas `k1..kN` | Roles con lista de módulos en JSON (RBAC) |
| Credenciales de BD hardcodeadas por colegio en `con2.php` | `config/config.php` fuera del repo; colegios en BD |
| Sin límite de intentos de login | Bloqueo temporal tras N intentos fallidos + bitácora |
| `error_reporting(0)` global | Errores visibles en desarrollo, registrados en producción |

## Pendientes conocidos

- Facturación CFDI (integración con PAC): el SIE la tiene acoplada; aquí se
  dejará como módulo aparte cuando se defina el PAC.
- Migración de datos del SIE: los esquemas están mapeados
  (`socios`, `eventos`, `cuentas`, `asistencia` → ver `database/schema.sql`);
  falta el script ETL por colegio.
- Envío de correos (recordatorios de cobranza / confirmaciones de registro).
