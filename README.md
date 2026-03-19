# moodle-video_embed

Repositorio de desarrollo del módulo Moodle **`mod_videoembed`** (nombre visible: **Vídeo**).

## Estado del proyecto

- Componente técnico: `mod_videoembed`
- Carpeta fuente principal: `workspace/mod_videoembed`
- Soporte de proveedores: Google Drive, YouTube, Vimeo y Bunny Stream
- Entrada Bunny recomendada: `https://player.mediadelivery.net/play/{library}/{video}`
- Conversión automática Bunny: `.../play/...` -> `.../embed/...`

## Documentación principal del plugin

- README del plugin: [`workspace/mod_videoembed/README.md`](workspace/mod_videoembed/README.md)
- README compatible Moodle Plugins: [`workspace/mod_videoembed/readme.txt`](workspace/mod_videoembed/readme.txt)
- Notas de cambios: [`workspace/mod_videoembed/UPGRADING.md`](workspace/mod_videoembed/UPGRADING.md)

## Estructura del repositorio

- `workspace/mod_videoembed`: código del plugin
- `scripts/`: utilidades de verificación/empaquetado
- `docs/`: documentación técnica del flujo
- `build/`: artefactos ZIP generados (no fuente del plugin)

## Flujo rápido

```bash
cd video-gdrive
./scripts/verify_isolation.sh

# Editar plugin:
# workspace/mod_videoembed

# Empaquetar ZIP instalable:
./scripts/package_workspace.sh
```

## Instalación en Moodle destino

1. Copiar `workspace/mod_videoembed` a `mod/videoembed`.
2. Ejecutar:

```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

3. Verificar en Administración del sitio > Plugins > Módulos de actividad.
