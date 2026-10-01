# DIANA — Puntos clave para continuar el proyecto

Documento para quien retome el desarrollo: estado actual, convenciones que hay
que respetar, hallazgos de la revisión del código y una hoja de ruta sugerida
por prioridad. El manual para usuarios finales está en
[MANUAL_USUARIO.md](MANUAL_USUARIO.md).

*Revisión basada en el estado de `main` en el commit `3641e0d` (catálogo de disciplinas
DPC y dos fases de registro).*

---

## 1. Estado actual en una página

| Área | Estado |
| --- | --- |
| Núcleo (router, sesión, CSRF, RBAC, PDO, archivos) | Completo y consistente. |
| Socios (generales, adicionales, foto, documentos, perfiles fiscales) | Completo. |
| Eventos (modalidad, precios, módulos, DPC por disciplina, galería) | Completo. |
| Disciplinas DPC por colegio | Completo (alta, renombrar, activar/desactivar). |
| Registro en dos fases (confirmación / asistencia) | Funcional; con huecos de diseño (ver §4). |
| Cuentas (cargos, pagos, cancelación) | Funcional; sin aplicación de pagos a cargos. |
| Reportes (saldos, morosidad, cobranza, eventos) | Básicos, solo pantalla/impresión. |
| Usuarios y colegios | Funcional; roles solo editables por SQL. |
| Facturación CFDI | **No iniciado** (los perfiles fiscales ya existen). |
| Migración de datos del SIE | **No iniciado** (falta ETL). |
| Correos (confirmaciones, cobranza, enlaces en línea) | **No iniciado**. |
| Pruebas automatizadas / CI | **No existen**. `php -l` pasa en todos los archivos. |

Tamaño: ~6,700 líneas (PHP + SQL + CSS), 10 controladores, 8 clases de núcleo,
32 vistas, 15 tablas. Sin Composer ni dependencias de servidor; Bootstrap 5 y
Bootstrap Icons por CDN.

---

## 2. Mapa del código

```
public/index.php          Front controller: carga bootstrap y despacha ?r=
public/router.php         Router para `php -S` (solo desarrollo)
app/bootstrap.php         Config, autoloader PSR-4 (Diana\ → app/), sesión, cabeceras
app/helpers.php           cfg(), e(), url(), redirigir(), flash(), dinero(), fecha_corta()…
app/Core/
  Router.php              /seccion/accion/params → SeccionController::accion(...params)
  Auth.php                Login con bloqueo, sesión, puede($modulo), colegio activo
  Csrf.php                Token por sesión, validado en TODO POST por el Router
  Database.php            PDO: todas(), una(), valor(), ejecutar(), ultimoId()
  Controller.php          Base: vista(), esPost(), post(), colegioId(), usuarioId()
  View.php                render() con layout / parcial() sin layout
  Archivos.php            Subidas seguras (lista blanca + finfo + nombre aleatorio)
  Catalogos.php           Constantes: estados, tipos de socio, categorías, SAT…
app/Controllers/          Uno por módulo (MODULO = clave RBAC)
app/Views/<modulo>/       Vistas; los parciales empiezan con "_"
database/schema.sql       Esquema completo + semilla (instalaciones nuevas)
database/migraciones/     Scripts incrementales (instalaciones existentes)
storage/uploads/          Fotos, documentos e imágenes (gitignored, fuera de public/)
```

### Relación módulo → tablas

| Módulo (RBAC) | Controlador | Tablas |
| --- | --- | --- |
| `dashboard` | DashboardController | lectura de socios, eventos, cuentas |
| `socios` | SociosController | socios, socio_documentos, socio_perfiles_fiscales |
| `eventos` | EventosController, DisciplinasController | eventos, evento_precios, evento_modulos, evento_puntos, evento_imagenes, disciplinas |
| `registro` | RegistroController | asistencias, cuentas (cargo/pago de inscripción) |
| `cuentas` | CuentasController | cuentas |
| `reportes` | ReportesController | lectura |
| `usuarios` | UsuariosController | usuarios, roles |
| `colegios` | ColegiosController | colegios (+ siembra disciplinas) |
| *(público)* | AuthController | usuarios, login_intentos |

---

## 3. Convenciones que hay que respetar

1. **Multi-colegio siempre.** Toda tabla de negocio lleva `colegio_id` y
   **toda consulta filtra por `$this->colegioId()`** (o valida antes la
   entidad padre con un `...OAbortar()` que ya filtra). Es la principal
   garantía de aislamiento entre colegios: revisarlo en cada PR.
2. **Consultas preparadas al 100 %.** Nada de interpolar valores del usuario.
   Los únicos fragmentos dinámicos permitidos son nombres de columna generados
   desde arreglos internos (patrón `$sets` / `$cols` de los controladores).
3. **Escapar toda salida con `e()`** en las vistas.
4. **POST para todo lo que modifica.** El Router valida el CSRF; cada
   formulario debe incluir `<?= Csrf::campo() ?>`.
5. **Nunca borrar historial.** Socios → baja; cuentas → `cancelado`;
   disciplinas → `activo = 0`; usuarios → `activo = 0`.
6. **Nuevo módulo** = `app/Controllers/XxxController.php` con
   `public const MODULO = 'xxx'`, vistas en `app/Views/xxx/`, entrada en el
   `$menu` de `app/Views/layouts/main.php` y la clave agregada a los roles que
   correspondan. Métodos públicos = rutas; los auxiliares deben ser `private`.
7. **Rutas con `url()` y `redirigir()`**, nunca escritas a mano: soportan
   `index.php?r=` y URLs amigables.
8. **Archivos subidos solo con `Archivos::guardar()`** y servidos por un
   controlador que verifique colegio (`Archivos::enviar()`).
9. **Cambios de esquema** en dos lugares: `database/schema.sql` (instalación
   nueva) **y** un script nuevo en `database/migraciones/` (instalaciones
   existentes) que preserve los datos.
10. **Nada de credenciales en el repo.** `config/config.php` está en
    `.gitignore`.
11. Idioma: identificadores, comentarios, mensajes y commits en español.

---

## 4. Hallazgos de la revisión (por prioridad)

### Alta — afectan datos o reglas de negocio

| # | Hallazgo | Dónde | Sugerencia |
| --- | --- | --- | --- |
| A1 | **El reporte de Morosidad no considera pagos.** Lista todo cargo vigente vencido aunque el socio ya lo haya pagado, porque los pagos no se aplican a cargos específicos. | `ReportesController::morosidad` | Opción simple: mostrar solo socios con saldo > 0 y antigüedad del cargo vencido más antiguo. Opción completa: tabla `cuenta_aplicaciones` (pago → cargo) y saldo por documento. |
| A2 | **En el esquema "por módulo" los puntos DPC se otorgan completos** al marcar asistencia al evento; no existe asistencia por módulo. | `RegistroController::marcarAsistencia` | Tabla `asistencia_modulos (asistencia_id, modulo_id, asistio)` y puntos = suma de módulos asistidos. |
| A3 | **Los puntos se congelan al marcar asistencia** y se guarda solo el total, no el detalle por disciplina. Si cambian los puntos del evento después, los asistentes quedan desfasados, y no se pueden emitir constancias por disciplina fiables. | `asistencias.puntos_dpc` | Guardar el detalle `asistencia_puntos (asistencia_id, disciplina_id, puntos)` y ofrecer "recalcular puntos" por evento. |
| A4 | **"Quitar" una confirmación borra la asistencia aunque ya haya asistido** y cancela también los **pagos** del evento (dinero ya recibido desaparece del saldo y de la cobranza). | `RegistroController::quitar` | Impedir quitar si `asistio = 1`; cancelar solo el cargo y, si hay pagos, dejarlos como saldo a favor o pedir confirmación explícita de reembolso. |
| A5 | **El público en general no tiene registro contable.** `cuentas.socio_id` es NOT NULL, así que los cobros "en caja" no aparecen en Cobranza ni en el reporte de Eventos. | `RegistroController::agregar`, tabla `cuentas` | Permitir `cuentas.asistencia_id` (o `socio_id` NULL + `asistencia_id`) para registrar cargo y pago del público. |
| A6 | **Orden de migraciones ambiguo.** `2026-09-06_eventos_modulos_precios.sql` se ordena alfabéticamente antes que `2026-09-06_socios_ampliado.sql`, pero debe ejecutarse después. No hay tabla de control de migraciones aplicadas. | `database/migraciones/` | Renombrar con consecutivo (`001_`, `002_`, `003_`) y agregar tabla `migraciones` + script `aplicar_migraciones.php`. |

### Media — seguridad y robustez

| # | Hallazgo | Dónde | Sugerencia |
| --- | --- | --- | --- |
| M1 | Bloqueo de login **solo por usuario**: cualquiera que conozca un nombre de usuario puede bloquearlo (negación de servicio) y no hay límite por IP. `login_intentos` crece sin depuración. | `Auth::entrar` | Contar también por IP; purgar registros viejos (p. ej. > 90 días). |
| M2 | Si el colegio del usuario está **inactivo**, el login tiene éxito pero la sesión queda sin colegio activo (`colegioId() = 0`): pantallas vacías y altas con `colegio_id = 0` fallarían por FK. | `Auth::entrar` / `cambiarColegio` | Rechazar el login con mensaje claro cuando el colegio está inactivo. |
| M3 | Un administrador de colegio puede asignar **cualquier rol** (incluido `["*"]`); los roles son globales y solo se editan por SQL. | `UsuariosController::formulario` | Pantalla de roles para superadmin; restringir los roles asignables por un admin de colegio. |
| M4 | El menú muestra **Colegios** a cualquier rol con `*` aunque no sea superadmin (luego redirige con aviso). | `layouts/main.php` | Ocultar el ítem si `!Auth::esGlobal()`. |
| M5 | No hay pantalla de **"Mi cuenta / cambiar mi contraseña"**: un Operador no puede cambiar la suya. Tampoco se fuerza el cambio de la contraseña inicial de `admin`. | — | Acción `auth/contrasena` accesible para todo usuario autenticado; bandera `debe_cambiar_password`. |
| M6 | Las cancelaciones de movimientos no guardan **quién, cuándo ni por qué**. | `CuentasController::cancelar` | Columnas `cancelado_por`, `cancelado_en`, `motivo_cancelacion`. Idealmente bitácora general de cambios. |
| M7 | Sin cabecera **Content-Security-Policy** y dependencia de CDN (jsdelivr) para CSS/JS. | `bootstrap.php`, `layouts/main.php` | Servir Bootstrap localmente desde `public/assets/` y añadir CSP. |
| M8 | La sesión siempre corre con `display_errors` según `entorno`; no hay manejador global de excepciones en producción (un error de BD muestra pantalla en blanco). | `bootstrap.php` | `set_exception_handler` con página 500 amigable y registro en log. |

### Baja — detalles de uso

| # | Hallazgo | Dónde |
| --- | --- | --- |
| B1 | El formulario de pago en Registro no envía `referencia`, aunque el controlador la acepta. | `Views/registro/evento.php` |
| B2 | Listados con `LIMIT` fijo (300 socios, 200 eventos, 100 en registro, 500 movimientos) sin paginación. | varios controladores |
| B3 | "Eventos próximos" del tablero excluye eventos de varios días ya iniciados. | `DashboardController` |
| B4 | Campos capturados que no se usan en ninguna lógica: `limite_credito`, `paga_cuota_anual`, cumpleaños, `colegios.logo_url` (el menú muestra la clave, no el logo). | — |
| B5 | El cupo cuenta todas las confirmaciones; no hay lista de espera. | `RegistroController::agregar` |
| B6 | Eventos `cerrado` desaparecen de Registro (solo accesibles por URL directa); no se puede consultar su asistencia desde el menú. | `RegistroController::index` |
| B7 | Reportes sin exportación a Excel/CSV (solo impresión). | `ReportesController` |

---

## 5. Hoja de ruta sugerida

### Fase 0 — Cimientos (antes de crecer)
- [ ] Renombrar migraciones con consecutivo y crear tabla/script de control (A6).
- [ ] Base de pruebas: PHPUnit (o un runner mínimo sin Composer) con BD de
      prueba sembrada desde `schema.sql`; pruebas de aislamiento por colegio,
      CSRF y permisos.
- [ ] CI en GitHub Actions: `php -l`, pruebas y carga de `schema.sql` +
      migraciones en un MySQL de servicio.
- [ ] Manejador global de errores y página 500 (M8).
- [ ] "Cambiar mi contraseña" y cambio forzado de la contraseña inicial (M5).

### Fase 1 — Corregir reglas de negocio
- [ ] Asistencia por módulo y puntos por disciplina por asistente (A2, A3).
- [ ] Proteger "Quitar confirmación" y manejo de pagos ya recibidos (A4).
- [ ] Registro contable del público en general (A5).
- [ ] Morosidad real con aplicación de pagos (A1).
- [ ] Bitácora de cancelaciones (M6) y endurecimiento de login (M1, M2).

### Fase 2 — Funciones pendientes del README
- [ ] **Migración de datos del SIE**: script ETL por colegio
      (`socios`, `eventos`, `cuentas`, `asistencia` → tablas DIANA), con modo
      "simulación" que reporte diferencias de saldos antes de confirmar.
- [ ] **Correo saliente** (SMTP configurable en `config.php`): confirmación de
      inscripción con enlace/clave de la sesión en línea, recordatorios de
      cobranza, envío de constancias.
- [ ] **Constancias DPC** en PDF por asistente y **historial de puntos por
      socio** (pestaña nueva en Socios: puntos por año y disciplina).
- [ ] **Generación masiva de cuota anual** para socios con
      `paga_cuota_anual = 1` (B4).

### Fase 3 — Facturación y autoservicio
- [ ] Módulo **Facturación CFDI 4.0** con PAC (a definir): timbrado desde un
      pago o cargo, usando el perfil fiscal predeterminado del socio;
      cancelación y complemento de pagos.
- [ ] Administración de **roles y permisos** desde la UI (M3).
- [ ] Exportación a Excel/CSV de todos los reportes; paginación (B2, B7).
- [ ] **Portal del socio** (opcional): consulta de estado de cuenta, puntos
      DPC, constancias e inscripción en línea a eventos publicados con pago
      en línea.

---

## 6. Lista de verificación para cada cambio

- [ ] ¿Toda consulta nueva filtra por `colegio_id`?
- [ ] ¿Toda entrada se usa con parámetros preparados y toda salida con `e()`?
- [ ] ¿El formulario lleva `Csrf::campo()` y la acción solo modifica en POST?
- [ ] ¿El controlador declara `MODULO` y los métodos auxiliares son `private`?
- [ ] ¿El cambio de esquema está en `schema.sql` **y** en una migración nueva?
- [ ] ¿Se conserva el historial (baja/cancelación en lugar de borrar)?
- [ ] ¿`php -l` limpio y probado con `php -S localhost:8080 -t public public/router.php`?
- [ ] ¿Se actualizó `README.md` y, si cambia algo visible, `docs/MANUAL_USUARIO.md`?

---

## 7. Arranque rápido para desarrollo

```bash
mysql -u root -p < database/schema.sql
cp config/config.example.php config/config.php   # ajustar credenciales de BD
php -S localhost:8080 -t public public/router.php
# Entrar con admin / Diana.2026*  (y cambiar la contraseña)
```

Datos semilla: colegios **MOR** (CCP Michoacán) y **HGO** (CCP Hidalgo),
roles Administrador / Operador / Consulta, usuario `admin` superadministrador
y 12 disciplinas DPC por colegio.
