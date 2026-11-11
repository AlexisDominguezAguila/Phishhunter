<?php
/**
 * Exportación CSV para el análisis estadístico (Excel / SPSS / JASP).
 * Separador «;» y BOM UTF-8 para que Excel en español lo abra correctamente.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$me = require_admin();
// Mismos filtros que el panel: el docente solo exporta a sus estudiantes.
$teacherF = is_super($me) ? (int) ($_GET['docente'] ?? 0) : 0;
$grade    = in_array((int) ($_GET['grado'] ?? 0), ALL_GRADES, true) ? (int) $_GET['grado'] : null;
$groupF   = (int) ($_GET['grupo'] ?? 0);
$typeF    = in_array($_GET['tipo'] ?? '', ['experimental', 'control'], true) ? $_GET['tipo'] : null;
$scope    = student_scope_sql($me, $teacherF ?: null, $groupF ?: null, $grade, $typeF);

$type = $_GET['t'] ?? 'evaluaciones';

function csv_out(string $name, array $header, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header, ';', '"', '');
    foreach ($rows as $r) {
        fputcsv($out, array_map(function ($v) { return is_float($v) ? str_replace('.', ',', (string) $v) : $v; }, array_values($r)), ';', '"', '');
    }
    fclose($out);
    exit;
}

switch ($type) {
    case 'items':
        // Una fila por estudiante y medición, una columna por ítem (P1..P24, G1..G9)
        $items = db_all("SELECT id, instrument, number FROM survey_items ORDER BY instrument='gamification', number");
        $header = ['id_estudiante', 'usuario', 'docente', 'grupo', 'tipo_grupo', 'grado', 'seccion', 'medicion'];
        foreach ($items as $it) $header[] = ($it['instrument'] === 'prevention' ? 'P' : 'G') . $it['number'];
        $assess = db_all("SELECT a.id, a.type, u.id uid, u.username, t.username teacher, g.name grp, g.type gtype, u.grade, u.section
                            FROM assessments a JOIN users u ON u.id=a.user_id
                       LEFT JOIN users t ON t.id=u.teacher_id LEFT JOIN class_groups g ON g.id=u.group_id
                           WHERE 1=1 $scope ORDER BY u.id, FIELD(a.type,'pretest','postest','valoracion')");
        $answers = [];
        foreach (db_all('SELECT assessment_id, item_id, value FROM assessment_answers') as $a) {
            $answers[$a['assessment_id']][$a['item_id']] = (int) $a['value'];
        }
        $rows = [];
        foreach ($assess as $a) {
            $row = [$a['uid'], $a['username'], $a['teacher'], $a['grp'], $a['gtype'], $a['grade'], $a['section'], $a['type']];
            foreach ($items as $it) $row[] = $answers[$a['id']][$it['id']] ?? '';
            $rows[] = $row;
        }
        csv_out('phishhunter_items', $header, $rows);

    case 'juegos':
        $rows = db_all("SELECT u.id, u.username, t.username teacher, cg.name grp, cg.type gtype, u.grade, u.section, m.order_num, g.slug, g.type, g.is_quiz, g.dimension,
                               a.correct, a.total, a.percent, a.stars, a.xp_earned, a.finished_at
                          FROM game_attempts a JOIN users u ON u.id=a.user_id JOIN games g ON g.id=a.game_id JOIN missions m ON m.id=g.mission_id
                     LEFT JOIN users t ON t.id=u.teacher_id LEFT JOIN class_groups cg ON cg.id=u.group_id
                         WHERE a.status='finished' $scope ORDER BY u.id, a.finished_at");
        csv_out('phishhunter_juegos', ['id_estudiante', 'usuario', 'docente', 'grupo', 'tipo_grupo', 'grado', 'seccion', 'mision', 'juego', 'tipo', 'es_prueba', 'dimension',
            'aciertos', 'total', 'porcentaje', 'estrellas', 'xp', 'fecha'], $rows);

    default:
        $rows = db_all("SELECT u.id, u.username, u.full_name, t.username teacher, cg.name grp, cg.type gtype, u.grade, u.section,
                               pre.total pre_total, pre.dim1 pre_reconocimiento, pre.dim2 pre_decisiones, pre.dim3 pre_proteccion, pre.level pre_nivel,
                               post.total post_total, post.dim1 post_reconocimiento, post.dim2 post_decisiones, post.dim3 post_proteccion, post.level post_nivel,
                               val.total gam_total, val.dim1 gam_motivacion, val.dim2 gam_diseno, val.dim3 gam_aprendizaje, val.level gam_nivel,
                               u.xp, (SELECT COUNT(*) FROM user_missions um WHERE um.user_id=u.id) misiones,
                               (SELECT COUNT(*) FROM user_badges b WHERE b.user_id=u.id) insignias
                          FROM users u
                     LEFT JOIN assessments pre  ON pre.user_id=u.id AND pre.type='pretest'
                     LEFT JOIN assessments post ON post.user_id=u.id AND post.type='postest'
                     LEFT JOIN assessments val  ON val.user_id=u.id AND val.type='valoracion'
                     LEFT JOIN users t ON t.id=u.teacher_id LEFT JOIN class_groups cg ON cg.id=u.group_id
                         WHERE 1=1 $scope ORDER BY u.grade, u.section, u.full_name");
        csv_out('phishhunter_evaluaciones', ['id_estudiante', 'usuario', 'nombre', 'docente', 'grupo', 'tipo_grupo', 'grado', 'seccion',
            'pre_total', 'pre_reconocimiento', 'pre_decisiones', 'pre_proteccion', 'pre_nivel',
            'post_total', 'post_reconocimiento', 'post_decisiones', 'post_proteccion', 'post_nivel',
            'gam_total', 'gam_motivacion', 'gam_diseno', 'gam_aprendizaje', 'gam_nivel', 'xp', 'misiones', 'insignias'], $rows);
}
