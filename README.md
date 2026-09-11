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
| Socios | Expediente por colegio en pestañas: datos generales (nombre y apellidos, tipo, género, cumpleaños, límite de crédito, cuota anual, foto), adicionales (domicilio, teléfonos, correos), **documentos digitales** (PDF/imágenes: acta, cédula, CV…) y **perfiles fiscales** múltiples (RFC, régimen y uso CFDI, C.P.) para facturación |
| Eventos | Presencial/en línea/híbrido con enlace de sesión (Webex, Zoom, Teams…), galería de imágenes (una principal + opcionales), precios por categoría de asistente y por modalidad, **módulos** (uno por día o sesión, con generador automático) y **puntos DPC por disciplina** —a nivel evento o por módulo— tomados de un **catálogo de disciplinas propio de cada colegio** (se agregan o renombran, nunca se eliminan) |
| Registro | Dos fases: **1. Confirmaciones** (alta del asistente, cargo automático según su categoría/modalidad, pagos ligados al evento) y **2. Asistencia** (pasar lista: solo al marcar asistencia se otorgan los puntos DPC, con lugar/mesa y comentarios) |
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

**Instalaciones existentes:** si la base ya fue creada con un `schema.sql`
anterior, aplique en orden los scripts de `database/migraciones/` en lugar de
volver a ejecutar el esquema completo.

**Archivos subidos (fotos y documentos de socios):** se guardan en la carpeta
`archivos.ruta` de la configuración (por omisión `storage/uploads/`, fuera de
`public/`) y se entregan a través de la aplicación tras verificar sesión y
colegio. PHP debe permitir el tamaño configurado: `upload_max_filesize` y
`post_max_size` (php.ini o `.user.ini`) deben ser mayores o iguales a
`archivos.max_mb`.

## Despliegue

La aplicación funciona en cualquier subcarpeta sin configurar rutas: si
`app.url` está vacío, la URL base se deduce de la propia petición. Suba la
carpeta completa y apunte el navegador a `.../public/`.

**URLs amigables.** Por omisión (`urls_amigables => false`) las rutas se
generan como `index.php?r=socios/editar/5`, que funciona en todo hosting.
Actívelas (`true`) solo si el servidor aplica `.htaccess` con `mod_rewrite`;
compruébelo abriendo `.../public/auth/login`: si Apache responde su propio
*Not Found* ("The requested URL was not found on this server"), la reescritura
no está activa y debe dejarlas en `false`. Habilitarlas requiere `mod_rewrite`
cargado y `AllowOverride All` en el VirtualHost.

**Dónde colocar los archivos.** Lo ideal es que solo `public/` sea accesible
por web: apunte el DocumentRoot (o la raíz del subdominio) a `public/`, dejando
`app/`, `config/` y `database/` fuera del alcance del servidor. Si no puede
—hosting compartido, subcarpeta— quedan dentro del árbol público y su
protección depende de los `.htaccess` incluidos, que solo aplican con
`AllowOverride All`. En ese caso, además:

- **No suba `database/` al servidor.** `schema.sql` es texto plano y se
  descarga íntegro si el listado de directorios está activo; solo se necesita
  una vez para crear la base de datos.
- Los archivos `.php` no filtran su contenido mientras PHP esté activo (se
  ejecutan, no se muestran), por lo que `config/config.php` no expone las
  credenciales, pero conviene no dejarlo al alcance de todos modos.

## Seguridad al publicar

1. **Cambie de inmediato la contraseña del usuario `admin`.** La contraseña
   inicial está documentada en este README y su hash en `database/schema.sql`,
   ambos en un repositorio público: mientras no la cambie, cualquiera que
   encuentre la instalación puede entrar como administrador.
2. Ponga `'entorno' => 'produccion'` para que los errores no se muestren al
   usuario.
3. Con HTTPS, ponga `'solo_https' => true` en `sesion` para que la cookie de
   sesión no viaje en claro.
4. Use un usuario de MySQL exclusivo de la aplicación, con permisos solo sobre
   su base de datos.
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
