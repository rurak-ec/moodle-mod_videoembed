# moodle-video_gdrive

Repositorio de desarrollo del módulo Moodle `mod_videogdrive`.

## Qué se versiona

- `workspace/mod_videogdrive`: fuente del plugin.
- `scripts/`: utilidades de verificación y empaquetado.
- `docs/`: notas de flujo técnico.

## Qué no se versiona

- `build/` (ZIPs generados).
- `reference/` (copias base de laboratorio).
- `workspace/_archive/` y backups `*.bak.*`.

## Flujo rápido

```bash
cd video-gdrive
./scripts/verify_isolation.sh

# Editar plugin:
# workspace/mod_videogdrive

# Empaquetar ZIP instalable en Moodle:
./scripts/package_workspace.sh
```

El ZIP generado contiene la carpeta correcta `videogdrive/` para instalar en `mod/` del Moodle destino.

## Instalación en otro Moodle

1. Copiar carpeta del plugin a `mod/videogdrive` en el código de Moodle.
2. Ejecutar actualización:
   ```bash
   php admin/cli/upgrade.php --non-interactive
   ```
3. Verificar en administración de plugins.
