# mod_videoembed (Vídeo)

Módulo de actividad para Moodle que permite incrustar vídeos desde múltiples proveedores con una sola URL.

## Estado actual

- Componente: `mod_videoembed`
- Nombre visible (ES): `Vídeo`
- Versión: `2026031905`
- Release: `1.1.0`
- Madurez: `MATURITY_STABLE`

## Funcionalidad principal

- Campo **URL del vídeo** obligatorio.
- Campo **Descripción del vídeo** opcional.
- Renderiza primero el vídeo y luego la descripción.
- Mantiene compatibilidad de visualización para registros antiguos.

## Proveedores soportados

### Google Drive
- Entrada: `https://drive.google.com/file/d/FILE_ID/view`
- Salida embed: `https://drive.google.com/file/d/FILE_ID/preview`

### YouTube
- Entrada: `watch`, `youtu.be`, `embed`, `shorts`
- Salida embed: `https://www.youtube.com/embed/VIDEO_ID`
- Diagnóstico de errores IFrame API (100/101/150/153)

### Vimeo
- Entrada: `https://vimeo.com/393011038`
- Salida embed: `https://player.vimeo.com/video/393011038`

### Bunny Stream
- Entrada recomendada:
  `https://player.mediadelivery.net/play/LIBRARY_ID/VIDEO_ID`
- Salida embed canónica:
  `https://player.mediadelivery.net/embed/LIBRARY_ID/VIDEO_ID`
- Parámetros por defecto:
  - `autoplay=false`
  - `loop=false`
  - `muted=false`
  - `preload=true`
  - `responsive=true`

Ejemplo solicitado:

```text
Input:
https://player.mediadelivery.net/play/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c
```

```html
<div style="position:relative;padding-top:56.25%;">
  <iframe
    src="https://player.mediadelivery.net/embed/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c?autoplay=false&loop=false&muted=false&preload=true&responsive=true"
    loading="lazy"
    style="border:0;position:absolute;top:0;height:100%;width:100%;"
    allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;"
    allowfullscreen="true">
  </iframe>
</div>
```

## Instalación

1. Copiar el plugin en:
   - `mod/videoembed`
2. Ejecutar upgrade:
   - `php admin/cli/upgrade.php --non-interactive`
3. Purgar caché:
   - `php admin/cli/purge_caches.php`

## Calidad y cumplimiento (Moodle Plugins directory)

- `version.php` con `version`, `release`, `maturity` correctos.
- `readme.txt` y `README.md` actualizados.
- `UPGRADING.md` con cambios relevantes.
- Soporte de idioma `en` y `es`.
- API de privacidad implementada (`null_provider`).
- Pruebas unitarias para parseo de URL (incluye casos Bunny `play -> embed`).

## Archivos clave

- Parseo de URLs: `locallib.php`
- Render de iframe: `view.php`
- Formulario del módulo: `mod_form.php`
- Strings:
  - `lang/en/videoembed.php`
  - `lang/es/videoembed.php`
- Notas de actualización: `UPGRADING.md`
