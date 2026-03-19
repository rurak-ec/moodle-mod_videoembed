# Mapa técnico del módulo derivado

Ruta objetivo:
- `workspace/mod_videogdrive`

Puntos clave:

- `version.php`: versión/componente del plugin.
- `db/install.xml`: tabla principal del módulo.
- `db/access.php`: capacidades/permisos.
- `lib.php`: callbacks del módulo (`videogdrive_*`).
- `mod_form.php`: formulario de creación/edición.
- `view.php`: salida principal al visualizar actividad.
- `classes/`: eventos, privacidad, búsqueda, servicios externos.
- `lang/en/videogdrive.php`: textos mostrados en UI.

Regla de diseño:
- No modificar `reference/mod_page`; úsalo como baseline.
- Implementar cambios solo en `workspace/mod_videogdrive`.
