# Workflow de desarrollo

## 1) Validar estructura local

```bash
cd video-gdrive
./scripts/verify_isolation.sh
```

## 2) Editar plugin

Trabaja en:
- `workspace/mod_videoembed`

Archivos más usados:
- `workspace/mod_videoembed/mod_form.php`
- `workspace/mod_videoembed/view.php`
- `workspace/mod_videoembed/lib.php`
- `workspace/mod_videoembed/version.php`

## 3) Empaquetar ZIP instalable

```bash
./scripts/package_workspace.sh
```

Salida:
- `build/videoembed_YYYYMMDD_HHMMSS.zip`

## 4) Instalar/probar en Moodle sandbox

- Descomprimir en `mod/videoembed`
- Ejecutar:
  ```bash
  php admin/cli/upgrade.php --non-interactive
  ```

## 5) Publicar cambios en GitHub

```bash
git add .
git commit -m "feat: cambios en mod_videoembed"
git push
```
