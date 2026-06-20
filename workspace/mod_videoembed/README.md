# mod_videoembed (Video / Vídeo)

Moodle activity module that embeds **one video per activity** from Google Drive, YouTube, Vimeo or
Bunny Stream by pasting a single URL. The video renders as a responsive 16:9 iframe (with an
accessible title), followed by an optional description.

| | |
|---|---|
| Component | `mod_videoembed` |
| Display name | Video (EN) / Vídeo (ES) |
| Version | `2026062002` |
| Release | `1.2.2` |
| Maturity | `MATURITY_STABLE` |
| Moodle | 5.0 – 5.2 (`$plugin->supported = [500, 502]`) |
| PHP | 8.2 – 8.4 (Moodle 5.2 requires PHP 8.3+) |
| License | GNU GPL v3 or later |

> 🇬🇧 **English** below · 🇪🇸 **Español** más abajo.
> The plugin ships **English only** (canonical). The Spanish translation is kept in
> [`/translations`](../../translations/) to be contributed to lang.moodle.org (AMOS) after approval.

---

## English

### How it works (full flow)

#### 1. The teacher creates the activity (`mod_form.php`)
- **Name** (required, max 1333 chars) and the standard activity **intro**.
- **Video URL** (required, `PARAM_URL`) — with contextual help listing the accepted formats.
- **Video description** (rich-text editor with file support), shown below the video.
- **Appearance:** display mode (*Open* / *Pop-up*, depending on what the admin enabled), pop-up
  width/height, *Display activity description* and *Display last modified date*.
- **Server-side validation:** on save, the URL must parse to a valid provider
  (`videoembed_parse_video_url`); otherwise it is rejected. Client validation is not trusted alone.

#### 2. On save (`lib.php`: `videoembed_add_instance` / `videoembed_update_instance`)
- Trims the URL and rejects empty (`videourlrequired`) or invalid (`invalidvideourl`) — *fail-closed*.
- Serialises the display options (pop-up size, `printintro`, `printlastmodified`).
- Saves the description and its files (`file_save_draft_area_files`) and updates the completion event.
  On edit it bumps `revision` (file cache invalidation).

#### 3. The student views the activity (`view.php`)
- `require_course_login()` + `require_capability('mod/videoembed:view')`.
- `videoembed_view()` fires the `course_module_viewed` event and marks **completion-on-view**.
- Parses the URL and builds the `\mod_videoembed\output\embed` renderable, rendered through the
  `templates/embed.mustache` template:
  - Responsive 16:9 wrapper via a CSS class (`.mod_videoembed_responsive`, no inline styles) and an
    `<iframe>` with an **accessible `title` attribute** (= activity name) plus provider-specific
    `allow`/`referrerpolicy`/`loading` attributes.
  - **YouTube:** adds `enablejsapi=1` + `origin`, loads the *IFrame API*, and the AMD module
    `mod_videoembed/player` shows a localised warning (errors 100/101/150/153) in a hidden
    `role="alert"` box.
  - **Google Drive:** best-effort probe (cURL with timeout) to detect sharing restrictions and warn
    when the file is not public.
  - **Invalid URL:** a warning is shown (`$OUTPUT->notification`).
- Then it renders the rich-text description and, optionally, the last-modified date.
- **Pop-up mode:** from the course page, `videoembed_get_coursemodule_info` opens the view in a
  `window.open` of the configured size.

### Providers and URL normalisation (`locallib.php`)
The parser validates the host with an allow-list and the ID with per-provider regexes, and
normalises to the canonical embed URL, preserving safe parameters. Anything else is rejected.

| Provider | Accepted input | Embed output |
|---|---|---|
| Google Drive | `https://drive.google.com/file/d/FILE_ID/view` · `/open?id=FILE_ID` | `https://drive.google.com/file/d/FILE_ID/preview` (keeps `resourcekey`) |
| YouTube | `watch?v=` · `youtu.be/` · `embed/` · `shorts/` | `https://www.youtube.com/embed/VIDEO_ID` (converts `t=1m30s` → `start=90`) |
| Vimeo | `https://vimeo.com/VIDEO_ID` · `player.vimeo.com/video/ID` | `https://player.vimeo.com/video/VIDEO_ID` (keeps `h`, `autoplay`, …) |
| Bunny Stream | `player/iframe.mediadelivery.net/play\|embed/LIB/VIDEO` · `video.bunnycdn.com` · `video.bunny.net` | `https://player.mediadelivery.net/embed/LIB/VIDEO` |

**Bunny — default flags** (overridable from the URL): `autoplay=false`, `loop=false`, `muted=false`,
`preload=true`, `responsive=true`; keeps extra params like `token`/`expires` for protected streams.

### Rendering architecture (since 1.2.0)
- `classes/output/embed.php` — `renderable` + `templatable` class that computes the context.
- `templates/embed.mustache` — presentation (iframe + hidden warning box).
- `amd/src/player.js` (→ `amd/build/player.min.js`) — YouTube error handling.
- `styles.css` — responsive classes with `aspect-ratio` and a fallback.

> `view.php` stays minimal: access control + build the renderable + `render_from_template`.

### Moodle integrations
- **Completion:** completion-on-view (`FEATURE_COMPLETION_TRACKS_VIEWS`).
- **Events:** `\mod_videoembed\event\course_module_viewed`.
- **Backup/restore:** complete, including the `videourl` field and intro/content files.
- **Calendar:** `mod_videoembed_core_calendar_provide_event_action` (a "View" action in *Timeline*).
- **Global search:** indexes name, intro, content and files (`classes/search/activity.php`).
- **Analytics:** cognitive-depth and social-breadth indicators.
- **Web services / mobile app:** `mod_videoembed_view_page`, `mod_videoembed_get_pages_by_courses`,
  and `videoembed_export_contents`.
- **Files:** rich content area served by `videoembed_pluginfile` (with legacy-file migration).
  Drag-and-drop is intentionally disabled (the URL is required).
- **Privacy:** `null_provider` — the activity **stores no personal data** (the `videoembed` table has
  no `userid`).

### Capabilities (`db/access.php`)
| Capability | Type | Context | Default |
|---|---|---|---|
| `mod/videoembed:view` | read | module | guest, user |
| `mod/videoembed:addinstance` | write (RISK_XSS) | course | editingteacher, manager |

### Security
- Strict URL validation (host allow-list + ID regex); URLs built with
  `http_build_query(PHP_QUERY_RFC3986)`.
- `require_course_login` + `require_capability` on the view and when serving files.
- Output escaped by Mustache; no inline styles/JS.
- The Google Drive probe only contacts `drive.google.com` (already validated), with a timeout and a
  redirect limit.

### Installation
1. Copy the plugin to `mod/videoembed` (on Moodle 5.x: `public/mod/videoembed`).
2. `php admin/cli/upgrade.php --non-interactive`
3. `php admin/cli/purge_caches.php`
4. Verify under *Site administration → Plugins → Activity modules*.

### Quality & directory compliance
- `phpcs --standard=moodle`: **0 errors / 0 warnings**.
- Parser unit tests (`tests/locallib_test.php`): valid providers + malicious/malformed inputs.
- `moodle-plugin-ci` (`.github/workflows/`): Moodle 5.0/5.1/5.2 × PHP 8.2–8.4 (5.2 on 8.3+),
  PostgreSQL 16 and MariaDB 10.11.
- Privacy API, backup/restore, events, capabilities, GPL headers.
- **Language:** ships **English only** (canonical); the Spanish translation is kept in
  [`/translations`](../../translations/) for lang.moodle.org (AMOS) after approval.

> After editing `amd/src/*.js`, run `grunt amd` to regenerate `amd/build/` (do not hand-edit the
> build) so CI's *grunt* step stays green.

### Key files
| Purpose | File |
|---|---|
| URL parsing/normalisation | `locallib.php` |
| Renderable | `classes/output/embed.php` |
| Template | `templates/embed.mustache` |
| YouTube error handling | `amd/src/player.js` |
| Responsive styles | `styles.css` |
| View | `view.php` |
| Form | `mod_form.php` |
| Lifecycle / callbacks | `lib.php` |
| Strings (canonical) | `lang/en/videoembed.php` |
| Changes / upgrade | `CHANGELOG.md`, `UPGRADING.md` |

---

## Español

Módulo de actividad para Moodle que incrusta **un vídeo por actividad** desde Google Drive, YouTube,
Vimeo o Bunny Stream pegando una sola URL. El vídeo se muestra como un iframe responsivo 16:9 (con
título accesible), seguido de una descripción opcional.

### ¿Cómo funciona? (flujo completo)

#### 1. El docente crea la actividad (`mod_form.php`)
- **Nombre** (obligatorio, máx. 1333 caracteres) e **intro** estándar de la actividad.
- **URL del vídeo** (obligatorio, `PARAM_URL`) — con ayuda contextual de formatos admitidos.
- **Descripción del vídeo** (editor de texto enriquecido con soporte de archivos), se muestra debajo
  del vídeo.
- **Apariencia:** modo de visualización (*Abrir* / *Ventana emergente*, según lo habilitado por el
  administrador), ancho/alto de la ventana emergente, *Mostrar descripción de la actividad* y
  *Mostrar fecha de última modificación*.
- **Validación servidor:** al guardar, la URL debe parsear a un proveedor válido
  (`videoembed_parse_video_url`); si no, se rechaza con un error. No se confía solo en el cliente.

#### 2. Al guardar (`lib.php`: `videoembed_add_instance` / `videoembed_update_instance`)
- Recorta la URL y rechaza vacío (`videourlrequired`) o inválido (`invalidvideourl`) — *fail-closed*.
- Serializa las opciones de visualización (tamaño de popup, `printintro`, `printlastmodified`).
- Guarda la descripción y sus archivos (`file_save_draft_area_files`) y actualiza el evento de
  finalización. En la edición incrementa `revision` (invalidación de caché de archivos).

#### 3. El estudiante ve la actividad (`view.php`)
- `require_course_login()` + `require_capability('mod/videoembed:view')`.
- `videoembed_view()` dispara el evento `course_module_viewed` y marca **finalización por vista**.
- Parsea la URL y construye el renderable `\mod_videoembed\output\embed`, que se renderiza con la
  plantilla `templates/embed.mustache`:
  - Contenedor responsivo 16:9 vía clase CSS (`.mod_videoembed_responsive`, sin estilos inline) y un
    `<iframe>` con **atributo `title` accesible** (= nombre de la actividad) y atributos
    `allow`/`referrerpolicy`/`loading` específicos por proveedor.
  - **YouTube:** añade `enablejsapi=1` + `origin`, carga la *IFrame API* y el módulo AMD
    `mod_videoembed/player` muestra un aviso localizado (errores 100/101/150/153) en una caja
    `role="alert"` oculta.
  - **Google Drive:** sonda *best-effort* (cURL con timeout) para detectar restricciones de permisos
    y avisar si el archivo no es público.
  - **URL inválida:** se muestra un aviso (`$OUTPUT->notification`).
- Luego renderiza la descripción enriquecida y, opcionalmente, la fecha de última modificación.
- **Modo ventana emergente:** desde la página del curso, `videoembed_get_coursemodule_info` abre la
  vista en un `window.open` con el tamaño configurado.

### Proveedores y normalización de URL (`locallib.php`)
El parser valida el host con una *allow-list* y el ID con expresiones regulares por proveedor, y
normaliza a la URL de inserción canónica preservando parámetros seguros. Cualquier otra cosa se
rechaza.

| Proveedor | Entrada admitida | Inserción (embed) |
|---|---|---|
| Google Drive | `https://drive.google.com/file/d/FILE_ID/view` · `/open?id=FILE_ID` | `https://drive.google.com/file/d/FILE_ID/preview` (conserva `resourcekey`) |
| YouTube | `watch?v=` · `youtu.be/` · `embed/` · `shorts/` | `https://www.youtube.com/embed/VIDEO_ID` (convierte `t=1m30s` → `start=90`) |
| Vimeo | `https://vimeo.com/VIDEO_ID` · `player.vimeo.com/video/ID` | `https://player.vimeo.com/video/VIDEO_ID` (conserva `h`, `autoplay`, …) |
| Bunny Stream | `player/iframe.mediadelivery.net/play\|embed/LIB/VIDEO` · `video.bunnycdn.com` · `video.bunny.net` | `https://player.mediadelivery.net/embed/LIB/VIDEO` |

**Bunny — flags por defecto** (sobreescribibles por la URL): `autoplay=false`, `loop=false`,
`muted=false`, `preload=true`, `responsive=true`; conserva parámetros extra como `token`/`expires`
para streams protegidos.

#### Ejemplo de salida (Bunny)
Entrada:
```text
https://player.mediadelivery.net/play/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c
```
Marcado renderizado (estilos vía `styles.css`, no inline):
```html
<div class="box generalbox center clearfix videoembed-video-embed">
  <div class="mod_videoembed_responsive mod_videoembed_responsive_bunny">
    <iframe class="mod_videoembed_frame"
            title="Embedded video: <activity name>"
            src="https://player.mediadelivery.net/embed/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c?autoplay=false&loop=false&muted=false&preload=true&responsive=true"
            allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
            loading="lazy"
            allowfullscreen>
    </iframe>
  </div>
</div>
```
```css
.mod_videoembed_responsive { position: relative; width: 100%; aspect-ratio: 16 / 9; }
```

### Arquitectura de renderizado (desde 1.2.0)
- `classes/output/embed.php` — clase `renderable` + `templatable` que computa el contexto.
- `templates/embed.mustache` — presentación (iframe + caja de aviso oculta).
- `amd/src/player.js` (→ `amd/build/player.min.js`) — manejo de errores de YouTube.
- `styles.css` — clases responsivas con `aspect-ratio` y *fallback*.

> `view.php` queda mínimo: control de acceso + construir el renderable + `render_from_template`.

### Integraciones de Moodle
- **Finalización:** finalización por vista (`FEATURE_COMPLETION_TRACKS_VIEWS`).
- **Eventos:** `\mod_videoembed\event\course_module_viewed`.
- **Backup/restore:** completo, incluyendo el campo `videourl` y los archivos de intro/contenido.
- **Calendario:** `mod_videoembed_core_calendar_provide_event_action` (acción «Ver» en *Vista general*).
- **Búsqueda global:** indexa nombre, intro, contenido y archivos (`classes/search/activity.php`).
- **Analítica:** indicadores de profundidad cognitiva y amplitud social.
- **Servicios web / app móvil:** `mod_videoembed_view_page`, `mod_videoembed_get_pages_by_courses`
  y `videoembed_export_contents`.
- **Archivos:** área de contenido enriquecido servida por `videoembed_pluginfile` (con migración de
  archivos heredados). *Arrastrar y soltar* está deshabilitado a propósito (la URL es obligatoria).
- **Privacidad:** `null_provider` — la actividad **no almacena datos personales** (la tabla
  `videoembed` no guarda `userid`).

### Capacidades (`db/access.php`)
| Capacidad | Tipo | Contexto | Por defecto |
|---|---|---|---|
| `mod/videoembed:view` | read | módulo | guest, user |
| `mod/videoembed:addinstance` | write (RISK_XSS) | curso | editingteacher, manager |

### Ajustes del administrador (`settings.php`)
Modos de visualización disponibles, modo por defecto, tamaño por defecto de la ventana emergente, y
valores por defecto de *mostrar descripción* / *mostrar última modificación*.

### Seguridad
- Validación estricta de URL (allow-list de host + regex de ID), construcción de URLs con
  `http_build_query(PHP_QUERY_RFC3986)`.
- `require_course_login` + `require_capability` en la vista y al servir archivos.
- Salida escapada por Mustache; sin estilos/JS inline.
- La sonda de Google Drive solo contacta `drive.google.com` (ya validado), con *timeout* y límite de
  redirecciones.

### Instalación
1. Copiar el plugin en `mod/videoembed` (en Moodle 5.x: `public/mod/videoembed`).
2. `php admin/cli/upgrade.php --non-interactive`
3. `php admin/cli/purge_caches.php`
4. Verificar en *Administración del sitio → Plugins → Módulos de actividad*.

### Idioma
El plugin se publica **solo en inglés** (canónico). La traducción al **español** se conserva en
[`/translations`](../../translations/) y se subirá a **lang.moodle.org (AMOS)** tras la aprobación,
para que llegue a todos los sitios en español por el mecanismo estándar de paquetes de idioma.

## Licencia / License
GNU GPL v3 o posterior. Partes derivan de Moodle core (© Moodle/Petr Skoda y otros); se conservan los
avisos de copyright originales en cada archivo. — GNU GPL v3 or later; portions derive from Moodle
core, original copyright notices retained.
