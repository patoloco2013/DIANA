# DIANA — Manual de usuario

Manual para el personal de los colegios (capturistas, cajeros, coordinadores
de eventos y administradores). Describe, módulo por módulo, qué se puede hacer
en DIANA y cómo hacerlo.

> Para instalar o configurar el servidor consulte el [README](../README.md).
> Para el plan técnico del proyecto consulte [Puntos clave](PUNTOS_CLAVE.md).

## Contenido

1. [Conceptos básicos](#1-conceptos-básicos)
2. [Acceso al sistema](#2-acceso-al-sistema)
3. [Pantalla principal y menú](#3-pantalla-principal-y-menú)
4. [Inicio (tablero)](#4-inicio-tablero)
5. [Socios](#5-socios)
6. [Eventos](#6-eventos)
7. [Disciplinas DPC](#7-disciplinas-dpc)
8. [Registro de eventos](#8-registro-de-eventos)
9. [Cuentas](#9-cuentas)
10. [Reportes](#10-reportes)
11. [Usuarios](#11-usuarios)
12. [Colegios](#12-colegios)
13. [Flujos de trabajo frecuentes](#13-flujos-de-trabajo-frecuentes)
14. [Mensajes frecuentes y solución de problemas](#14-mensajes-frecuentes-y-solución-de-problemas)
15. [Glosario](#15-glosario)

---

## 1. Conceptos básicos

| Concepto | Qué significa en DIANA |
| --- | --- |
| **Colegio** | Cada colegio de profesionistas que usa el sistema (p. ej. *CCP Michoacán*, *CCP Hidalgo*). Su padrón, eventos y cuentas están separados de los demás. |
| **Colegio activo** | El colegio con el que está trabajando en este momento. Todo lo que ve y captura pertenece a él. Su nombre aparece en la parte superior del menú. |
| **Usuario** | Persona del personal que entra al sistema. Pertenece a un colegio, o a todos si es *superadministrador*. |
| **Rol** | Define qué módulos puede usar cada usuario (ver [Usuarios](#11-usuarios)). |
| **Socio** | Miembro del colegio, identificado por su **ID de socio** (único dentro del colegio). |
| **Evento** | Curso, diplomado, congreso, taller, etc. Puede ser presencial, en línea o híbrido. |
| **Puntos DPC** | Puntos de Desarrollo Profesional Continuo que otorga un evento, repartidos por **disciplina**. Solo se otorgan a quien **asistió**. |
| **Cargo / Pago** | Movimientos del estado de cuenta del socio. **Saldo = cargos − pagos** (solo movimientos vigentes). |

**Reglas generales que aplican a todo el sistema:**

- Los campos marcados con **\*** son obligatorios.
- Después de guardar aparece un mensaje en color en la parte superior:
  verde (éxito), amarillo (aviso) o rojo (error). Léalo siempre.
- Casi nada se borra definitivamente: los socios se dan de **baja**, los
  movimientos se **cancelan**, las disciplinas se **desactivan** y los
  usuarios se **desactivan**. Así se conserva el historial.
- Las acciones delicadas piden confirmación (*"¿Dar de baja a este socio?"*,
  *"¿Cancelar este movimiento?"*…).
- El sistema funciona en computadora, tableta y celular. En pantallas pequeñas
  el menú se abre con el botón ☰ y algunas columnas de las tablas se ocultan.

---

## 2. Acceso al sistema

### Iniciar sesión

1. Abra la dirección de DIANA que le proporcionó su administrador.
2. Capture **Usuario** y **Contraseña** y pulse **Entrar**.
3. Si sus datos son correctos llegará a la pantalla de **Inicio**.

### Bloqueo por intentos fallidos

Después de **5 intentos fallidos** con el mismo usuario, la cuenta se bloquea
temporalmente **15 minutos** (valores por omisión; el administrador puede
cambiarlos). Verá el mensaje *"Cuenta bloqueada temporalmente por intentos
fallidos. Espere 15 minutos."* Espere y vuelva a intentar, o pida a su
administrador que verifique su contraseña.

### Cierre de sesión

- Pulse el icono de **Cerrar sesión** (al pie del menú lateral).
- Por seguridad, la sesión se cierra sola tras **120 minutos sin actividad**.
  Si al guardar aparece *"Sesión expirada o petición no válida"*, regrese,
  recargue la página e intente de nuevo (lo capturado en ese formulario se
  pierde).

### Cambio de contraseña

Las contraseñas se cambian en **Usuarios → Editar** (mínimo 10 caracteres). Si
usted no tiene acceso al módulo Usuarios, pida el cambio a su administrador.

> **Importante para el primer arranque:** el usuario inicial `admin` tiene una
> contraseña conocida públicamente. Cámbiela antes de cualquier otra cosa.

---

## 3. Pantalla principal y menú

El menú lateral está agrupado en tres secciones; **solo verá los módulos que
su rol permite**:

| Sección | Módulos |
| --- | --- |
| **Operación** | Inicio, Socios, Eventos, Registro, Cuentas |
| **Análisis** | Reportes |
| **Administración** | Usuarios, Colegios |

- **Selector "Colegio activo"** (solo superadministradores): lista desplegable
  en la parte superior del menú. Al elegir otro colegio, todo el sistema cambia
  a ese colegio y aparece *"Colegio activo cambiado."*
- El color del menú y la clave mostrada corresponden al colegio activo.
- Si intenta entrar a un módulo sin permiso verá **"Sin permiso"**; si la
  dirección no existe, **"Página no encontrada"**. En ambos casos use **Ir al
  inicio**.

---

## 4. Inicio (tablero)

Resumen del colegio activo. Es de solo consulta.

| Indicador | Qué muestra |
| --- | --- |
| **Socios activos** | Socios con estatus *activo*. |
| **Eventos próximos** | Eventos *publicados* con fecha de inicio de hoy en adelante. |
| **Cartera por cobrar** | Suma de todos los saldos (cargos − pagos vigentes) del colegio. |
| **Cobrado este mes** | Pagos vigentes con fecha dentro del mes en curso. |

Debajo se muestran dos tablas:

- **Próximos eventos** (hasta 8): nombre, fecha, sede y registrados. Al pulsar
  el nombre se abre el evento para editarlo.
- **Últimos pagos** (hasta 8): fecha, socio e importe.

---

## 5. Socios

Padrón de miembros del colegio activo, con su expediente completo.

### 5.1 Listado y búsqueda

- La búsqueda acepta **nombre, ID, RFC, correo o celular** (basta una parte).
- Se muestran hasta 300 resultados ordenados por nombre. Si no encuentra a
  alguien, afine la búsqueda.
- Columnas: ID, socio, tipo, contacto, estatus y **saldo** actual.
- Acciones por renglón:
  - 💰 **Estado de cuenta** → abre el socio en el módulo [Cuentas](#9-cuentas).
  - ✏️ **Editar** → abre su expediente.
  - 🚫 **Baja** → cambia su estatus a *baja* (pide confirmación).

### 5.2 Alta de un socio

1. Pulse **Nuevo** en el listado.
2. Llene la pestaña **Generales** (ver tabla) y, si lo desea, **Adicionales**
   (puede pasar con el botón **Siguiente: Adicionales**).
3. Pulse **Guardar**. El sistema confirma *"Socio registrado. Ya puede agregar
   su foto, documentos y perfiles fiscales."*
4. Las pestañas **Documentos** y **Fiscal** se habilitan **después de guardar**
   el socio por primera vez.

**Pestaña Generales**

| Campo | Notas |
| --- | --- |
| Nombre(s) \* | |
| Apellido paterno / materno | Se combinan con el nombre para listados y búsquedas. |
| Título / profesión | C.P., L.C., Dr., etc. |
| ID socio \* | Número de socio. **No puede repetirse** dentro del colegio. |
| RFC | 12 caracteres (moral) o 13 (física). Se convierte a mayúsculas. |
| Límite de crédito | Importe informativo (no bloquea cargos). |
| Tipo de socio | Normal, Estudiante, Vitalicio, Honorario o No socio. **Determina el precio** que se le cobra en eventos (ver [8.2](#82-fase-1-confirmaciones)). |
| Género | Opcional. |
| Estatus | Activo, Suspendido o Baja. Solo los **activos** pueden inscribirse a eventos. |
| Cumpleaños | Día y mes. Capture ambos o ninguno. |
| Fotografía | JPG, PNG o WEBP (máx. 10 MB por omisión). Para retirarla marque **Quitar la foto actual**. |
| Paga cuota anual | Marca informativa. |

**Pestaña Adicionales**

Dirección, código postal (5 dígitos), colonia, localidad, ciudad, estado
(lista de entidades), celular, teléfonos de oficina 1 y 2, correos 1 y 2
(deben tener formato válido) y observaciones/notas.

### 5.3 Editar un socio

Abra el socio con ✏️, cambie lo necesario en la pestaña correspondiente y pulse
**Guardar**. El sistema regresa a la misma pestaña.

### 5.4 Pestaña Documentos

Expediente digital del socio (acta de nacimiento, CURP, INE, cédula, título,
CV, comprobante de domicilio, constancia de situación fiscal, fotografía u
otro).

1. En **Agregar documento** elija el **Tipo**, escriba una **Descripción**
   opcional y seleccione el **Archivo** (PDF, JPG, PNG o WEBP; máx. 10 MB).
2. Pulse **Subir documento**.
3. En la tabla **Documentos del socio** puede **Ver** (se abre en el
   navegador), **Descargar** o **Eliminar**. Eliminar un documento **no se
   puede deshacer**.

> Los archivos se protegen: solo se pueden ver con sesión iniciada y desde el
> colegio al que pertenece el socio.

### 5.5 Pestaña Fiscal (perfiles fiscales)

Un socio puede tener **varios perfiles fiscales** (por ejemplo "Personal" y
"Despacho"). Se usan para facturar (CFDI 4.0). Capture los datos **tal como
aparecen en la Constancia de Situación Fiscal**.

| Campo | Notas |
| --- | --- |
| Alias \* | Nombre corto para distinguir el perfil. |
| RFC \* | Formato válido de 12 o 13 caracteres. |
| Razón social / nombre \* | Sin régimen societario (sin "S.A. de C.V."). Se guarda en mayúsculas. |
| Régimen fiscal \* | Catálogo del SAT (601, 612, 626…). |
| Uso de CFDI | Catálogo del SAT; por omisión *G03 Gastos en general*. |
| C.P. fiscal \* | 5 dígitos. |
| Correo para facturas | Opcional, formato válido. |
| Predeterminado | El perfil que se usará por omisión. Solo puede haber uno. |
| Activo | Un perfil inactivo se conserva pero no se usa. |

- El **primer perfil** que se registra queda como predeterminado
  automáticamente.
- Acciones: **Editar**, ⭐ **Hacer predeterminado** y **Eliminar**. Si elimina
  el predeterminado, otro perfil toma su lugar.

### 5.6 Baja de un socio

La baja **no borra** al socio ni su historial de cuentas: solo cambia su
estatus a *baja*. Para reactivarlo, edítelo y cambie **Estatus** a *Activo*.

---

## 6. Eventos

Catálogo de cursos, diplomados, congresos, etc. del colegio activo.

### 6.1 Listado

- Búsqueda por **nombre, expositor o sede**. Se muestran hasta 200 eventos,
  del más reciente al más antiguo.
- Columnas: evento (con miniatura de su imagen principal), fecha, modalidad,
  puntos DPC totales, rango de precios, registrados y estatus.
- Acciones: 🎥 **Abrir sesión en línea** (si tiene enlace), 📋 **Registro de
  asistentes** y ✏️ **Editar**.
- Botones superiores: **Disciplinas** (catálogo DPC, ver [sección 7](#7-disciplinas-dpc))
  y **Nuevo evento**.

### 6.2 Crear un evento

1. Pulse **Nuevo evento** y llene la pestaña **Generales**.
2. Pulse **Guardar**. El sistema crea automáticamente los renglones de precio
   en cero y lo lleva a la pestaña **Precios**.
3. Las pestañas **Precios**, **Módulos y DPC** e **Imágenes** se habilitan
   después de guardar el evento por primera vez.

**Pestaña Generales**

| Campo | Notas |
| --- | --- |
| Nombre del evento \* | |
| Tipo | Texto libre: curso, diplomado, taller… |
| Modalidad \* | **Presencial**, **En línea** o **Híbrido** (presencial y en línea). Define qué precios y qué opciones de asistencia existen. |
| Fecha inicio \* / Fecha fin | La final no puede ser anterior a la inicial. La fecha fin es necesaria para generar módulos automáticamente. |
| Hora inicio / Hora fin | |
| Sede | Para eventos presenciales o híbridos. |
| Expositores | |
| Enlace de la sesión en línea | URL completa (Webex, Zoom, Teams…). **Obligatorio** si el evento es en línea o híbrido y está *publicado*. |
| Clave de acceso | Contraseña de la sesión en línea, si la hay. |
| Cupo | Máximo de confirmaciones. Vacío = sin límite. |
| Estatus | **Borrador** (en preparación), **Publicado** (visible en Registro), **Cerrado** o **Cancelado**. |
| Puntos DPC | **Por evento** (un total para todo) o **Por módulo** (cada módulo tiene los suyos). |
| Descripción / objetivo | |

> **Consejo:** si aún no tiene el enlace de la sesión en línea, guarde el
> evento como **Borrador** y publíquelo cuando lo tenga.

**Puntos DPC del evento** (esquema *Por evento*): debajo del formulario de
Generales aparece la tabla **Puntos DPC por disciplina**. Capture los puntos de
cada disciplina que aplique (deje vacío o 0 las que no) y pulse **Guardar puntos
DPC**.

### 6.3 Pestaña Precios

Tabla de precios **por categoría de asistente** y, en eventos híbridos, **por
modalidad** (columna Presencial y columna En línea).

Categorías: **Socio**, **Socio inicial**, **Socio cuenta**, **Estudiante** y
**No socio (público en general)**.

- Capture el precio de cada categoría y pulse **Guardar precios**.
- **0** = sin costo para esa categoría.
- **Vacío** = esa categoría no tiene precio definido (al inscribir con ella se
  cobra $0).

### 6.4 Pestaña Módulos y DPC

Un evento puede tener módulos (sesiones, días de un diplomado) o ninguno.

- **Agregar módulo**: orden, fecha, **nombre** \*, hora inicio/fin,
  expositores y sede. Pulse **Guardar**.
- **Generar un módulo por día**: crea automáticamente un módulo por cada día
  entre la fecha de inicio y la de fin del evento (máximo 60 días), copiando
  horario, expositores y sede. No duplica días que ya tienen módulo.
- **Editar** (✏️) o **Eliminar** un módulo. Eliminar un módulo **también borra
  sus puntos DPC**.
- Con el esquema **Por módulo**, cada módulo muestra su propia tabla de puntos
  por disciplina; capture y pulse **Guardar puntos del módulo**. El total del
  evento es la suma de todos sus módulos.

### 6.5 Pestaña Imágenes

1. En **Agregar imagen** seleccione el archivo (JPG, PNG o WEBP; máx. 10 MB),
   un **Título** opcional y, si lo desea, marque que sea la **principal**.
2. Pulse **Subir imagen**.
3. La **primera imagen** que suba se vuelve la principal automáticamente. La
   principal aparece como miniatura en el listado y en el encabezado del evento.
4. En la **Galería** puede ⭐ **Hacer principal** o 🗑 **Eliminar** cada imagen.

### 6.6 Desde el evento al registro

En el encabezado del evento, el botón **Registro** lleva directamente a sus
confirmaciones (ver [sección 8](#8-registro-de-eventos)).

---

## 7. Disciplinas DPC

Catálogo de disciplinas (Fiscal, Auditoría, Contabilidad, Finanzas, Ética
profesional, etc.) con el que se asignan los puntos DPC. **Cada colegio tiene
su propio catálogo**. Se entra desde **Eventos → Disciplinas** o desde el
enlace **Catálogo de disciplinas** dentro de un evento. Requiere permiso del
módulo Eventos.

- **Agregar**: escriba el nombre en **Nueva disciplina** y pulse **Agregar**.
  No se permiten nombres repetidos.
- **Renombrar**: corrija el nombre en la tabla y pulse el botón de guardar del
  renglón. Los puntos ya otorgados no cambian, solo el nombre que se muestra.
- **Desactivar / Reactivar**: una disciplina desactivada deja de ofrecerse en
  eventos nuevos, pero sigue visible en los eventos donde ya tiene puntos.
- La columna **Usos** indica en cuántos registros de puntos se usa.
- **Las disciplinas nunca se eliminan**, para no perder los puntos DPC de
  eventos anteriores.

---

## 8. Registro de eventos

Registro de asistentes en **dos fases**:

1. **Confirmaciones** — quién se inscribe; se genera su cargo y se registran
   sus pagos. **Aún no recibe puntos DPC.**
2. **Asistencia** — el día del evento se pasa lista; **solo al marcar
   "Asistió" se otorgan los puntos DPC**.

### 8.1 Listado de eventos para registro

Muestra solo los eventos con estatus **Publicado** (hasta 100), con
modalidad, puntos DPC, confirmados, asistieron y cupo. Acciones:
**Confirmaciones** y **Asistencia**.

> Si un evento no aparece aquí, revise que su estatus sea *Publicado*.

### 8.2 Fase 1: Confirmaciones

Encabezado: datos del evento, puntos DPC totales y pestañas **1. Confirmaciones**
y **2. Asistencia**.

**Confirmar a un socio**

1. **Asiste**: elija *Presencial* o *En línea* (solo en eventos híbridos; en
   los demás la modalidad se toma del evento).
2. **Categoría (precio)**: deje *"Según el tipo de socio"* para que el sistema
   elija la categoría automáticamente, o elija una específica. Cada opción
   muestra el precio correspondiente.

   | Tipo de socio | Categoría automática |
   | --- | --- |
   | Normal, Vitalicio, Honorario | Socio |
   | Estudiante | Estudiante |
   | No socio | No socio |

3. **Socio activo**: elija al socio de la lista (solo aparecen socios activos).
4. Pulse **Confirmar socio**. Si el precio es mayor a cero **se genera
   automáticamente un cargo** *"Inscripción: [evento]"* en su estado de cuenta.

Un socio solo puede confirmarse una vez por evento.

**Confirmar a una persona del público en general**

1. Capture **Nombre completo** y, opcionalmente, su **Correo** (útil para
   enviarle el enlace si asiste en línea).
2. Elija modalidad y categoría (por omisión *No socio*).
3. Pulse **Confirmar público**. El sistema indica el importe a cobrar
   **directamente en caja**: al no ser socio, **no se genera cargo en Cuentas**.

**Tabla de confirmados**

| Columna | Contenido |
| --- | --- |
| Asistente | ID y nombre del socio, o nombre del público; correo y modalidad. |
| Categoría | Categoría de precio aplicada. |
| Cargo / cobrado | Para socios: importe del cargo / importe pagado para **este** evento, con etiqueta **Pagado** o **Pendiente**. Para público: *Cobro directo (caja)*. Sin costo: *Sin costo*. |
| Asistió | Sí / No (se marca en la fase 2). |

- **Registrar pago** (solo socios con pago pendiente): despliegue la opción,
  capture **Importe** y **forma de pago** (efectivo, transferencia, tarjeta,
  cheque u otro) y pulse **Guardar**. El pago queda ligado al evento y aparece
  en el estado de cuenta del socio.
- **Quitar** (✖): elimina la confirmación y **cancela el cargo y los pagos de
  ese evento** del socio. Pide confirmación. Úselo con cuidado: si la persona ya
  tenía asistencia marcada, también se pierde.
- Si se alcanzó el **cupo**, el sistema avisa *"El evento ya alcanzó su cupo."*

### 8.3 Fase 2: Asistencia (pasar lista)

Lista de todos los confirmados en orden alfabético.

- **Dar asistencia**: marca que la persona asistió y le otorga **los puntos DPC
  totales del evento** vigentes en ese momento.
- **Asistió** (botón verde): pulse para **retirar** una asistencia marcada por
  error; los puntos se retiran.
- **Lugar/mesa** y **Comentarios**: capture y pulse el botón de guardar del
  renglón.

> **Importante:** los puntos se calculan al momento de marcar la asistencia.
> Defina los puntos DPC del evento **antes** de pasar lista. Si los cambia
> después, retire y vuelva a dar asistencia para actualizar los puntos de cada
> persona.

---

## 9. Cuentas

Estados de cuenta de los socios: cargos (lo que debe) y pagos (abonos).

### 9.1 Listado

- Sin búsqueda, muestra **solo socios con saldo distinto de cero**, del mayor
  al menor saldo.
- Con búsqueda (nombre o ID) muestra a los socios encontrados aunque su saldo
  sea cero.
- Pulse el socio para abrir su estado de cuenta.

### 9.2 Estado de cuenta de un socio

Muestra el **saldo** actual y todos sus movimientos (hasta 500), con el
evento relacionado cuando aplica. Los movimientos cancelados se muestran
marcados y no cuentan en el saldo.

**Registrar un movimiento**

| Campo | Notas |
| --- | --- |
| Tipo | **Cargo** (aumenta el saldo) o **Pago (abono)** (lo disminuye). |
| Concepto \* | Cuota anual, inscripción, etc. |
| Importe \* | Mayor a cero. |
| Fecha | Por omisión, hoy. |
| Vencimiento (cargos) | Solo para cargos. **Necesario para que aparezca en el reporte de Morosidad.** |
| Forma de pago | Solo para pagos. |
| Referencia / folio | Número de recibo, folio de transferencia, etc. |

Pulse **Registrar**.

**Cancelar un movimiento**: pulse el botón **Cancelar** del renglón y confirme.
El movimiento **no se borra**: queda como *cancelado* para auditoría y deja de
contar en el saldo. Un movimiento cancelado no puede reactivarse; si se canceló
por error, regístrelo de nuevo.

---

## 10. Reportes

Reportes del colegio activo. Todos tienen botón **Imprimir** (use *Guardar
como PDF* en el diálogo de impresión del navegador para obtener un archivo).

| Reporte | Contenido |
| --- | --- |
| **Saldos de socios** | Todos los socios con sus cargos, pagos y saldo, del mayor al menor saldo. |
| **Morosidad** | Cargos vigentes **con fecha de vencimiento ya pasada**, con días de atraso. Muestra los cargos vencidos; revise el saldo del socio para saber si ya fueron cubiertos con pagos posteriores. |
| **Cobranza** | Pagos recibidos entre las fechas **Desde** y **Hasta** (por omisión, del día 1 del mes a hoy), con forma de pago, referencia y total. Pulse **Consultar** tras cambiar las fechas. |
| **Eventos** | Por evento: estatus, socios y público confirmados, cuántos asistieron, importe **facturado** (cargos) y **cobrado** (pagos). Solo incluye lo registrado en Cuentas (no el cobro en caja al público). |

---

## 11. Usuarios

Alta y mantenimiento del personal que usa DIANA.

### 11.1 Roles predefinidos

| Rol | Módulos permitidos |
| --- | --- |
| **Administrador** | Todos. |
| **Operador** | Inicio, Socios, Eventos (incluye Disciplinas), Registro y Cuentas. |
| **Consulta** | Inicio y Reportes (solo lectura). |

### 11.2 Listado

Usuario, nombre, rol, colegio, último acceso y si está activo. Un
administrador de colegio solo ve a los usuarios de su colegio; el
superadministrador ve a todos.

### 11.3 Crear o editar un usuario

| Campo | Notas |
| --- | --- |
| Usuario (login) \* | Único en todo el sistema. |
| Nombre completo \* | |
| Correo | |
| Rol \* | |
| Colegio | Solo lo ve el superadministrador. **Todos (superadmin)** da acceso a todos los colegios; elíjalo solo para administradores generales. |
| Contraseña | Obligatoria al crear; **mínimo 10 caracteres**. Al editar, déjela vacía para conservar la actual. |
| Cuenta activa | Desmarque para impedir el acceso. |

Pulse **Guardar**.

### 11.4 Desactivar

El botón **Desactivar** impide que el usuario entre, sin borrarlo (se conserva
el historial). Nadie puede desactivar su propia cuenta. Para reactivarlo,
edítelo y marque **Cuenta activa**.

---

## 12. Colegios

Solo para el **superadministrador** (usuario sin colegio asignado).

- **Listado**: cada colegio con su número de socios y usuarios.
- **Nuevo colegio / Editar**:

  | Campo | Notas |
  | --- | --- |
  | Clave \* | Corta y única (p. ej. *MOR*). Se guarda en mayúsculas y se muestra en el menú. |
  | Nombre corto \* | Aparece en el menú y en el selector de colegio. |
  | Nombre / razón social \* | |
  | Ciudad, correo de contacto, teléfono | |
  | Color del tema | Color principal del menú para ese colegio. |
  | URL del logotipo | Dirección web de la imagen del logo. |
  | Colegio activo | Un colegio inactivo desaparece del selector y sus usuarios ya no pueden trabajar con él (entran sin colegio activo). |

- Al dar de alta un colegio se crea automáticamente su **catálogo inicial de
  12 disciplinas DPC**, que luego puede ajustar en [Disciplinas](#7-disciplinas-dpc).

---

## 13. Flujos de trabajo frecuentes

### Organizar un evento de principio a fin

1. **Eventos → Nuevo evento**: datos generales, modalidad y esquema de puntos.
   Guarde como *Borrador* si falta información.
2. **Precios**: capture el precio por categoría (y por modalidad si es híbrido).
3. **Módulos y DPC**: genere o capture los módulos y asigne los puntos DPC
   (por evento o por módulo).
4. **Imágenes**: suba la imagen principal y, si quiere, una galería.
5. Cambie el estatus a **Publicado** (con enlace si es en línea/híbrido).
6. **Registro → Confirmaciones**: inscriba socios y público; registre pagos.
7. El día del evento, **Registro → Asistencia**: dé asistencia a quienes se
   presentaron (otorga los puntos DPC) y anote lugar o comentarios.
8. Al terminar, cambie el estatus a **Cerrado** y consulte **Reportes →
   Eventos**.

### Cobrar la cuota anual

1. **Cuentas** → busque al socio → registre un **Cargo** "Cuota anual 2026"
   con su **fecha de vencimiento**.
2. Cuando pague, registre un **Pago (abono)** con forma de pago y referencia.
3. Dé seguimiento en **Reportes → Morosidad** y **Reportes → Cobranza**.

### Integrar el expediente de un socio nuevo

1. **Socios → Nuevo**: generales y adicionales → **Guardar**.
2. **Documentos**: suba cédula, título, INE, constancia fiscal…
3. **Fiscal**: capture su(s) perfil(es) fiscal(es) para facturación.

---

## 14. Mensajes frecuentes y solución de problemas

| Mensaje / situación | Causa y solución |
| --- | --- |
| *Usuario o contraseña incorrectos.* | Verifique mayúsculas/minúsculas. Tras 5 intentos fallidos la cuenta se bloquea 15 minutos. |
| *Sesión expirada o petición no válida.* | Pasó mucho tiempo sin actividad o la página quedó abierta desde antes. Regrese, recargue e intente de nuevo. |
| *Sin permiso* | Su rol no incluye ese módulo. Pida acceso a su administrador. |
| *El número de socio X ya existe en este colegio.* | Use otro ID de socio o busque al socio existente. |
| *El RFC no tiene un formato válido…* | Revise que tenga 12 o 13 caracteres, sin espacios ni guiones. |
| *Capture día y mes de cumpleaños, o deje ambos vacíos.* | Llene ambos campos o ninguno. |
| *Un evento en línea publicado necesita el enlace de la sesión…* | Capture el enlace o guarde el evento como *Borrador*. |
| *Capture la fecha final del evento para generar un módulo por día.* | Agregue la fecha fin en Generales. |
| *El evento ya alcanzó su cupo.* | Aumente el cupo del evento o déjelo vacío (sin límite). |
| *El socio ya está registrado en este evento.* | Ya tiene confirmación; búsquelo en la tabla. |
| *Seleccione un socio activo.* | Solo pueden inscribirse socios con estatus *activo*. |
| *El archivo excede el tamaño permitido…* | Reduzca el archivo (máx. 10 MB por omisión) o pida al administrador ampliar el límite. |
| *El contenido del archivo no corresponde a su extensión.* | El archivo está dañado o fue renombrado (p. ej. un .docx renombrado a .pdf). Expórtelo de nuevo en el formato correcto. |
| El evento no aparece en Registro | Su estatus no es *Publicado*. |
| A un asistente le salieron 0 puntos | Se le dio asistencia antes de capturar los puntos DPC. Retire y vuelva a dar asistencia. |

---

## 15. Glosario

- **CFDI**: Comprobante Fiscal Digital por Internet (factura electrónica del SAT).
- **CSF**: Constancia de Situación Fiscal emitida por el SAT.
- **DPC**: Desarrollo Profesional Continuo; puntos que acreditan la
  actualización del profesionista.
- **Disciplina**: área de conocimiento en la que se acreditan los puntos DPC.
- **Módulo**: sesión o día de un evento.
- **Modalidad**: presencial, en línea o híbrida.
- **Superadministrador**: usuario sin colegio asignado; administra todos los
  colegios y puede cambiar el colegio activo.
- **Vigente / Cancelado**: estado de un movimiento de cuenta; solo los vigentes
  cuentan en saldos y reportes.
