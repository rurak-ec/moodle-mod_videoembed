# Workflow de desarrollo

## 1) Validar estructura local

```bash
cd video-gdrive
./scripts/verify_isolation.sh
```

## 2) Editar plugin

Trabaja en:
- `workspace/mod_videogdrive`

Archivos más usados:
- `workspace/mod_videogdrive/mod_form.php`
- `workspace/mod_videogdrive/view.php`
- `workspace/mod_videogdrive/lib.php`
- `workspace/mod_videogdrive/version.php`

## 3) Empaquetar ZIP instalable

```bash
./scripts/package_workspace.sh
```

Salida:
- `build/videogdrive_YYYYMMDD_HHMMSS.zip`

## 4) Instalar/probar en Moodle sandbox

- Descomprimir en `mod/videogdrive`
- Ejecutar:
  ```bash
  php admin/cli/upgrade.php --non-interactive
  ```

## 5) Publicar cambios en GitHub

```bash
git add .
git commit -m "feat: cambios en mod_videogdrive"
git push
```
