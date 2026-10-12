<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CLI para gestionar actividades de Vídeo (mod_videoembed) desde la consola.
 *
 * Ejecutar siempre como el usuario del servidor web (www-data), nunca como root:
 *   docker exec -u www-data moodle-cursos-app php public/mod/videoembed/cli/videoembed.php --help
 *
 * @package    mod_videoembed
 * @copyright  2026 Cursos Profes Online
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Comprobación de root previa a la inicialización de Moodle.
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
    fwrite(STDERR, "No lo ejecutes como root: los archivos de caché que crea Moodle quedarían de root y el sitio fallaría.\n" .
        "Usa el usuario del servidor web, por ejemplo:\n" .
        "  docker exec -u www-data moodle-cursos-app php public/mod/videoembed/cli/videoembed.php --help\n");
    exit(1);
}

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once(__DIR__ . '/../classes/local/cli.php');

use mod_videoembed\local\cli;

[$options, $unrecognised] = cli_get_params(cli::OPTIONS, ['h' => 'help']);

$help = <<<EOT
Gestiona las actividades de Vídeo (mod_videoembed) desde la línea de comandos.

Uso:
  php public/mod/videoembed/cli/videoembed.php <orden> [opciones]

Ejecútalo siempre como www-data (nunca como root):
  docker exec -u www-data moodle-cursos-app php public/mod/videoembed/cli/videoembed.php list

ÓRDENES DISPONIBLES:
  list       [--course=ID] [--search=TEXTO] [--provider=TIPO] [--json]
             Lista las actividades de vídeo registradas en el sitio o en un curso.
             Proveedores admitidos para filtrar: drive, youtube, vimeo, bunny.

  show       --cmid=N (o --id=N) [--json]
             Muestra todos los detalles de un vídeo: URL original, ID de vídeo,
             URL del reproductor embebido, sección, visibilidad y descripción.

  create     --course=ID --name=TEXTO --videourl=URL [--section=N] [--intro=HTML] [--visible=0|1] [--json]
             Crea una nueva actividad de Vídeo tras validar que la URL sea compatible.

  update     --cmid=N [--name=TEXTO] [--videourl=URL] [--intro=HTML] [--section=N] [--visible=0|1] [--json]
             Modifica los datos o la URL de una actividad existente.

  delete     --cmid=N --yes [--json]
             Elimina la actividad del curso de forma permanente.

  validate   --videourl=URL [--json]
             Prueba una URL sin guardar nada. Informa el proveedor detectado,
             el ID extraído y la URL embebida del reproductor canónico.

  audit      [--course=ID] [--json]
             Audita todas las actividades del sitio para detectar enlaces rotos
             o formatos no admitidos. Muestra resumen estadístico por proveedor.

PROVEEDORES SOPORTADOS:
  - Google Drive  (enlaces /file/d/... o id=...)
  - YouTube       (youtube.com/watch?v=..., youtu.be/..., shorts)
  - Vimeo         (vimeo.com/..., player.vimeo.com/video/...)
  - Bunny Stream  (player.mediadelivery.net/embed/..., video.bunnycdn.com)

EOT;

if (!empty($options['help']) || empty($unrecognised)) {
    echo $help;
    exit(0);
}

$command = array_shift($unrecognised);

$runner = new cli($options);
$runner->run($command);
