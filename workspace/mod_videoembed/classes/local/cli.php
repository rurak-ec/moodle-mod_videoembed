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

namespace mod_videoembed\local;

use core_text;
use stdClass;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/videoembed/locallib.php');
require_once($CFG->dirroot . '/mod/videoembed/lib.php');

/**
 * Gestor de comandos CLI para actividades mod_videoembed (Vídeo).
 *
 * @package    mod_videoembed
 * @copyright  2026 Cursos Profes Online
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cli {

    /** @var string[] Comandos disponibles en el orden de ayuda. */
    public const COMMANDS = ['list', 'show', 'create', 'update', 'delete', 'validate', 'audit'];

    /** @var array Opciones admitidas por cli_get_params(). */
    public const OPTIONS = [
        'help' => false,
        'json' => false,
        'yes' => false,
        'cmid' => null,
        'id' => null,
        'course' => null,
        'section' => null,
        'name' => null,
        'videourl' => null,
        'intro' => null,
        'visible' => null,
        'display' => null,
        'search' => null,
        'provider' => null,
    ];

    /**
     * Constructor.
     *
     * @param array $options Opciones analizadas desde cli_get_params().
     */
    public function __construct(
        protected array $options,
    ) {
        \core\session\manager::set_user(get_admin());
    }

    /**
     * Ejecuta una orden.
     *
     * @param string $command Nombre del comando.
     */
    public function run(string $command): void {
        if (!in_array($command, self::COMMANDS, true)) {
            cli_error("Orden desconocida: «{$command}». Usa --help para ver las órdenes disponibles.");
        }
        $this->{'command_' . $command}();
    }

    /**
     * Obtiene una opción o su valor por defecto.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function option(string $name, mixed $default = null): mixed {
        return $this->options[$name] ?? $default;
    }

    /**
     * Emite datos en formato JSON si se pasó --json.
     *
     * @param mixed $data
     * @return bool True si emitió JSON y debe terminar la ejecución.
     */
    protected function json(mixed $data): bool {
        if (!empty($this->options['json'])) {
            cli_writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return true;
        }
        return false;
    }

    /**
     * list [--course=ID] [--search=TEXT] [--provider=PROV]: Listado de actividades de vídeo.
     */
    protected function command_list(): void {
        global $DB;

        $params = ['modname' => 'videoembed'];
        $where = [];

        if ($this->option('course') !== null) {
            $courseid = (int)$this->option('course');
            $where[] = 'cm.course = :courseid';
            $params['courseid'] = $courseid;
        }

        if ($this->option('search') !== null) {
            $search = trim((string)$this->option('search'));
            if ($search !== '') {
                $where[] = '(v.name ILIKE :search1 OR v.videourl ILIKE :search2)';
                $params['search1'] = "%{$search}%";
                $params['search2'] = "%{$search}%";
            }
        }

        $wherestr = $where ? 'AND ' . implode(' AND ', $where) : '';

        $sql = "SELECT cm.id as cmid, v.id as instanceid, c.id as courseid, c.shortname as courseshortname,
                       cs.section as sectionnum, cs.name as sectionname, v.name, v.videourl, cm.visible
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videoembed} v ON v.id = cm.instance
                  JOIN {course} c ON c.id = cm.course
                  JOIN {course_sections} cs ON cs.id = cm.section
                 WHERE cm.deletioninprogress = 0 {$wherestr}
              ORDER BY c.id, cs.section, cm.id";

        $records = $DB->get_records_sql($sql, $params);

        $providerfilter = strtolower(trim((string)$this->option('provider', '')));

        $items = [];
        foreach ($records as $r) {
            $parsed = videoembed_parse_video_url($r->videourl);
            $provider = !empty($parsed['valid']) ? ($parsed['provider'] ?? 'desconocido') : 'inválido';

            if ($providerfilter !== '' && strtolower($provider) !== $providerfilter) {
                continue;
            }

            $items[] = [
                'cmid' => (int)$r->cmid,
                'id' => (int)$r->instanceid,
                'courseid' => (int)$r->courseid,
                'course' => $r->courseshortname,
                'section' => (int)$r->sectionnum,
                'sectionname' => $r->sectionname ?: "Sección {$r->sectionnum}",
                'name' => $r->name,
                'provider' => $this->provider_label($provider),
                'videourl' => $r->videourl,
                'visible' => (bool)$r->visible,
            ];
        }

        if ($this->json($items)) {
            return;
        }

        if (!$items) {
            cli_writeln('No se encontraron actividades de Vídeo.');
            return;
        }

        cli_writeln('Total actividades de Vídeo: ' . count($items));
        $this->table(
            ['cmid', 'curso', 'sec', 'nombre', 'proveedor', 'url', 'visible'],
            array_map(fn($item) => [
                $item['cmid'],
                $item['course'],
                $item['section'],
                $item['name'],
                $item['provider'],
                $item['videourl'],
                $this->yesno($item['visible']),
            ], $items),
            40
        );
    }

    /**
     * show --cmid=N: Información detallada de una actividad de vídeo.
     */
    protected function command_show(): void {
        [$cm, $v, $course, $section] = $this->get_target_activity();

        $parsed = videoembed_parse_video_url($v->videourl);
        $provider = !empty($parsed['valid']) ? ($parsed['provider'] ?? 'desconocido') : 'inválido';

        $data = [
            'cmid' => (int)$cm->id,
            'id' => (int)$v->id,
            'courseid' => (int)$course->id,
            'course' => $course->fullname . " ({$course->shortname})",
            'section' => (int)$section->section,
            'sectionname' => $section->name ?: "Sección {$section->section}",
            'name' => $v->name,
            'videourl' => $v->videourl,
            'provider' => $this->provider_label($provider),
            'videoid' => $parsed['videoid'] ?? null,
            'embedurl' => $parsed['embedurl'] ?? null,
            'valid' => !empty($parsed['valid']),
            'visible' => (bool)$cm->visible,
            'display' => (int)$v->display,
            'intro' => $v->intro,
            'timemodified' => userdate($v->timemodified, '%Y-%m-%d %H:%M:%S'),
            'url' => (new \moodle_url('/mod/videoembed/view.php', ['id' => $cm->id]))->out(false),
        ];

        if ($this->json($data)) {
            return;
        }

        cli_writeln("Actividad Vídeo «{$data['name']}» (cmid {$data['cmid']}, id {$data['id']})");
        cli_writeln("  Curso: {$data['course']} · {$data['sectionname']} (sec {$data['section']})");
        cli_writeln("  Visible: " . $this->yesno($data['visible']) . " · Modo display: {$data['display']}");
        cli_writeln("  Proveedor detectado: {$data['provider']}" . ($data['valid'] ? ' (Válido)' : ' (INVÁLIDO)'));
        if ($data['videoid']) {
            cli_writeln("  ID de vídeo: {$data['videoid']}");
        }
        cli_writeln("  URL original: {$data['videourl']}");
        if ($data['embedurl']) {
            cli_writeln("  URL embebida (reproductor): {$data['embedurl']}");
        }
        if (!empty($data['intro'])) {
            cli_writeln("  Descripción: " . $this->plain($data['intro']));
        }
        cli_writeln("  Última modificación: {$data['timemodified']}");
        cli_writeln("  Enlace web: {$data['url']}");
    }

    /**
     * create --course=ID --name=TEXT --videourl=URL [--section=N] [--intro=HTML] [--visible=0|1]
     */
    protected function command_create(): void {
        global $DB;

        $courseid = $this->option('course');
        if ($courseid === null) {
            cli_error('Falta el parámetro obligatorio --course=ID.');
        }
        $course = $DB->get_record('course', ['id' => (int)$courseid], '*', MUST_EXIST);

        $name = trim((string)$this->option('name', ''));
        if ($name === '') {
            cli_error('Falta el parámetro obligatorio --name="Nombre del vídeo".');
        }

        $videourl = trim((string)$this->option('videourl', ''));
        if ($videourl === '') {
            cli_error('Falta el parámetro obligatorio --videourl="https://...".');
        }

        $parsed = videoembed_parse_video_url($videourl);
        if (empty($parsed['valid'])) {
            cli_error("URL de vídeo inválida: «{$videourl}». Debe ser de Google Drive, YouTube, Vimeo o Bunny Stream.");
        }

        $sectionnum = (int)$this->option('section', 0);
        $visible = $this->option('visible') !== null ? (int)$this->option('visible') : 1;
        $intro = (string)$this->option('intro', '');
        $display = (int)$this->option('display', 0);

        $mod = $DB->get_record('modules', ['name' => 'videoembed'], '*', MUST_EXIST);

        $minfo = new stdClass();
        $minfo->modulename = 'videoembed';
        $minfo->module = $mod->id;
        $minfo->course = $course->id;
        $minfo->section = $sectionnum;
        $minfo->visible = $visible;
        $minfo->name = $name;
        $minfo->videourl = $videourl;
        $minfo->introeditor = [
            'text' => $intro,
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];
        $minfo->display = $display;
        $minfo->printintro = !empty($intro) ? 1 : 0;
        $minfo->printlastmodified = 0;
        $minfo->popupwidth = 620;
        $minfo->popupheight = 450;
        $minfo->beforemod = 0;

        $created = add_moduleinfo($minfo, $course);

        $result = [
            'created' => true,
            'cmid' => (int)$created->coursemodule,
            'id' => (int)$created->instance,
            'courseid' => (int)$course->id,
            'section' => $sectionnum,
            'name' => $name,
            'videourl' => $videourl,
            'provider' => $this->provider_label($parsed['provider'] ?? ''),
        ];

        if ($this->json($result)) {
            return;
        }

        cli_writeln("✓ Actividad Vídeo creada con éxito:");
        cli_writeln("  CMID: {$result['cmid']} | ID instancia: {$result['id']}");
        cli_writeln("  Curso: [{$course->id}] {$course->shortname} | Sección: {$sectionnum}");
        cli_writeln("  Nombre: «{$name}»");
        cli_writeln("  Proveedor: {$result['provider']} | URL: {$videourl}");
    }

    /**
     * update --cmid=N [--name=TEXT] [--videourl=URL] [--intro=HTML] [--visible=0|1] [--section=N]
     */
    protected function command_update(): void {
        global $DB;

        [$cm, $v, $course, $section] = $this->get_target_activity();

        $changes = [];

        if ($this->option('name') !== null) {
            $newname = trim((string)$this->option('name'));
            if ($newname !== '') {
                $v->name = $newname;
                $changes['name'] = $newname;
            }
        }

        if ($this->option('videourl') !== null) {
            $newurl = trim((string)$this->option('videourl'));
            $parsed = videoembed_parse_video_url($newurl);
            if (empty($parsed['valid'])) {
                cli_error("URL de vídeo inválida: «{$newurl}». Debe ser de Google Drive, YouTube, Vimeo o Bunny Stream.");
            }
            $v->videourl = $newurl;
            $changes['videourl'] = $newurl;
            $changes['provider'] = $this->provider_label($parsed['provider'] ?? '');
        }

        if ($this->option('intro') !== null) {
            $v->intro = (string)$this->option('intro');
            $v->introformat = FORMAT_HTML;
            $changes['intro'] = $v->intro;
        }

        if ($this->option('display') !== null) {
            $v->display = (int)$this->option('display');
            $changes['display'] = $v->display;
        }

        if ($changes) {
            $v->timemodified = time();
            $DB->update_record('videoembed', $v);
        }

        // CM changes
        $cmchanged = false;
        if ($this->option('visible') !== null) {
            $newvisible = (int)$this->option('visible') ? 1 : 0;
            if ((int)$cm->visible !== $newvisible) {
                set_coursemodule_visible($cm->id, $newvisible);
                $changes['visible'] = (bool)$newvisible;
                $cmchanged = true;
            }
        }

        if ($this->option('section') !== null) {
            $newsecnum = (int)$this->option('section');
            $newsec = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $newsecnum]);
            if (!$newsec) {
                cli_error("La sección {$newsecnum} no existe en el curso {$course->id}.");
            }
            if ($cm->section != $newsec->id) {
                moveto_module($cm, $newsec);
                $changes['section'] = $newsecnum;
                $cmchanged = true;
            }
        }

        if ($changes || $cmchanged) {
            rebuild_course_cache($course->id, true);
        }

        $result = [
            'updated' => true,
            'cmid' => (int)$cm->id,
            'id' => (int)$v->id,
            'changes' => $changes,
        ];

        if ($this->json($result)) {
            return;
        }

        if (!$changes) {
            cli_writeln("No se indicaron cambios para la actividad CMID {$cm->id}.");
            return;
        }

        cli_writeln("✓ Actividad Vídeo (CMID {$cm->id}) actualizada:");
        foreach ($changes as $key => $val) {
            cli_writeln("  - {$key}: " . (is_bool($val) ? $this->yesno($val) : $val));
        }
    }

    /**
     * delete --cmid=N --yes: Elimina una actividad de vídeo.
     */
    protected function command_delete(): void {
        [$cm, $v, $course, $section] = $this->get_target_activity();

        if (empty($this->options['yes'])) {
            cli_error("Para confirmar la eliminación permanente de «{$v->name}» (cmid {$cm->id}), debes añadir --yes.");
        }

        course_delete_module($cm->id);

        $result = [
            'deleted' => true,
            'cmid' => (int)$cm->id,
            'name' => $v->name,
            'courseid' => (int)$course->id,
        ];

        if ($this->json($result)) {
            return;
        }

        cli_writeln("✓ Actividad «{$v->name}» (cmid {$cm->id}) eliminada correctamente.");
    }

    /**
     * validate --videourl=URL: Comprueba si una URL es soportada y devuelve su información de inserción.
     */
    protected function command_validate(): void {
        $videourl = trim((string)$this->option('videourl', ''));
        if ($videourl === '') {
            cli_error('Falta el parámetro obligatorio --videourl="https://...".');
        }

        $parsed = videoembed_parse_video_url($videourl);
        $valid = !empty($parsed['valid']);

        $result = [
            'url' => $videourl,
            'valid' => $valid,
            'provider' => $valid ? ($parsed['provider'] ?? 'desconocido') : null,
            'provider_label' => $valid ? $this->provider_label($parsed['provider'] ?? '') : null,
            'videoid' => $parsed['videoid'] ?? null,
            'embedurl' => $parsed['embedurl'] ?? null,
            'reason' => $parsed['reason'] ?? ($valid ? null : 'invalid'),
        ];

        if ($this->json($result)) {
            return;
        }

        cli_writeln("Validación de URL de Vídeo:");
        cli_writeln("  URL evaluada: {$videourl}");
        if ($valid) {
            cli_writeln("  ✓ Estado: VÁLIDO");
            cli_writeln("  Proveedor: {$result['provider_label']}");
            cli_writeln("  ID de vídeo: {$result['videoid']}");
            cli_writeln("  URL embebida: {$result['embedurl']}");
        } else {
            cli_writeln("  ✗ Estado: INVÁLIDO");
            cli_writeln("  Motivo: " . ($result['reason'] === 'empty' ? 'URL vacía' : 'Formato no reconocido o proveedor no admitido.'));
            cli_writeln("  Proveedores compatibles: Google Drive, YouTube, Vimeo, Bunny Stream.");
        }
    }

    /**
     * audit [--course=ID]: Analiza todas las actividades de vídeo para comprobar URLs rotas o inválidas.
     */
    protected function command_audit(): void {
        global $DB;

        $params = ['modname' => 'videoembed'];
        $where = '';
        if ($this->option('course') !== null) {
            $params['courseid'] = (int)$this->option('course');
            $where = 'AND cm.course = :courseid';
        }

        $sql = "SELECT cm.id as cmid, v.id as instanceid, c.id as courseid, c.shortname as courseshortname,
                       cs.section as sectionnum, v.name, v.videourl, cm.visible
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videoembed} v ON v.id = cm.instance
                  JOIN {course} c ON c.id = cm.course
                  JOIN {course_sections} cs ON cs.id = cm.section
                 WHERE cm.deletioninprogress = 0 {$where}
              ORDER BY c.id, cs.section, cm.id";

        $records = $DB->get_records_sql($sql, $params);

        $counts = [
            'total' => count($records),
            'valid' => 0,
            'invalid' => 0,
            'by_provider' => [
                'youtube' => 0,
                'drive' => 0,
                'vimeo' => 0,
                'bunny' => 0,
                'otro' => 0,
            ],
        ];

        $invaliditems = [];

        foreach ($records as $r) {
            $parsed = videoembed_parse_video_url($r->videourl);
            if (!empty($parsed['valid'])) {
                $counts['valid']++;
                $p = $parsed['provider'] ?? 'otro';
                $counts['by_provider'][$p] = ($counts['by_provider'][$p] ?? 0) + 1;
            } else {
                $counts['invalid']++;
                $counts['by_provider']['otro']++;
                $invaliditems[] = [
                    'cmid' => (int)$r->cmid,
                    'course' => $r->courseshortname,
                    'name' => $r->name,
                    'videourl' => $r->videourl,
                ];
            }
        }

        $data = [
            'summary' => $counts,
            'invalid_activities' => $invaliditems,
        ];

        if ($this->json($data)) {
            return;
        }

        cli_writeln("=== AUDITORÍA DE ACTIVIDADES DE VÍDEO (mod_videoembed) ===");
        cli_writeln("Total vídeos analizados: {$counts['total']}");
        cli_writeln("  ✓ Válidos: {$counts['valid']}");
        cli_writeln("  ✗ Inválidos: {$counts['invalid']}");
        cli_writeln("\nDesglose por proveedor:");
        cli_writeln("  - YouTube:       {$counts['by_provider']['youtube']}");
        cli_writeln("  - Google Drive:  {$counts['by_provider']['drive']}");
        cli_writeln("  - Vimeo:         {$counts['by_provider']['vimeo']}");
        cli_writeln("  - Bunny Stream:  {$counts['by_provider']['bunny']}");

        if ($invaliditems) {
            cli_writeln("\n⚠️  ACTIVIDADES CON URL INVÁLIDA (" . count($invaliditems) . "):");
            $this->table(
                ['cmid', 'curso', 'nombre', 'url'],
                array_map(fn($it) => [$it['cmid'], $it['course'], $it['name'], $it['videourl']], $invaliditems),
                50
            );
        } else {
            cli_writeln("\n✓ ¡Excelente! Todas las actividades tienen URLs compatibles.");
        }
    }

    // Helpers

    /**
     * Obtiene el curso, sección y módulo objetivo mediante --cmid o --id.
     *
     * @return array [$cm, $videoembed, $course, $section]
     */
    protected function get_target_activity(): array {
        global $DB;

        $cmid = $this->option('cmid');
        $id = $this->option('id');

        if ($cmid === null && $id === null) {
            cli_error('Debes especificar --cmid=N (ID del módulo de curso) o --id=N (ID de la instancia).');
        }

        if ($cmid !== null) {
            $cm = get_coursemodule_from_id('videoembed', (int)$cmid, 0, false, MUST_EXIST);
            $v = $DB->get_record('videoembed', ['id' => $cm->instance], '*', MUST_EXIST);
        } else {
            $v = $DB->get_record('videoembed', ['id' => (int)$id], '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('videoembed', $v->id, $v->course, false, MUST_EXIST);
        }

        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $section = $DB->get_record('course_sections', ['id' => $cm->section], '*', MUST_EXIST);

        return [$cm, $v, $course, $section];
    }

    /**
     * Nombre legible del proveedor.
     */
    protected function provider_label(string $provider): string {
        return match (strtolower($provider)) {
            'youtube' => 'YouTube',
            'drive' => 'Google Drive',
            'vimeo' => 'Vimeo',
            'bunny' => 'Bunny Stream',
            default => 'Otro / Desconocido',
        };
    }

    /**
     * Convierte booleano a "sí" o "no".
     */
    protected function yesno(bool $value): string {
        return $value ? 'sí' : 'no';
    }

    /**
     * Limpia etiquetas HTML de un texto.
     */
    protected function plain(?string $text): string {
        return html_entity_decode(strip_tags((string)$text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Imprime una tabla alineada en consola.
     */
    protected function table(array $headers, array $rows, int $maxwidth = 50): void {
        $cell = function ($value) use ($maxwidth): string {
            $value = preg_replace('/(?<=\S)\s+/u', ' ', rtrim((string)$value));
            return core_text::strlen($value) > $maxwidth ? core_text::substr($value, 0, $maxwidth - 1) . '…' : $value;
        };

        $rows = array_map(fn($row) => array_map($cell, array_values($row)), $rows);
        $widths = array_map(fn($header) => core_text::strlen($header), $headers);

        foreach ($rows as $row) {
            foreach ($row as $i => $value) {
                $widths[$i] = max($widths[$i], core_text::strlen($value));
            }
        }

        $line = fn(array $cells) => rtrim(implode('  ', array_map(
            fn($value, $i) => $value . str_repeat(' ', $widths[$i] - core_text::strlen($value)), $cells, array_keys($cells))));

        cli_writeln($line($headers));
        cli_writeln($line(array_map(fn($width) => str_repeat('-', $width), $widths)));
        foreach ($rows as $row) {
            cli_writeln($line($row));
        }
    }
}
