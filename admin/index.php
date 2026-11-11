<?php
/**
 * Panel docente: resultados de la intervención gamificada
 * (pretest vs. postest, dimensiones, ítems, valoración y mini-pruebas).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/stats.php';

$me = require_admin();

// Filtros: el docente solo ve a sus estudiantes; el súper admin puede elegir docente.
$teacherF = is_super($me) ? (int) ($_GET['docente'] ?? 0) : 0;
$grade    = in_array((int) ($_GET['grado'] ?? 0), ALL_GRADES, true) ? (int) $_GET['grado'] : null;
$groupF   = (int) ($_GET['grupo'] ?? 0);
$typeF    = in_array($_GET['tipo'] ?? '', ['experimental', 'control'], true) ? $_GET['tipo'] : null;
if ($groupF && !is_super($me) && !db_val('SELECT 1 FROM class_groups WHERE id = ? AND teacher_id = ?', [$groupF, $me['id']])) $groupF = 0;
$gWhere = student_scope_sql($me, $teacherF ?: null, $groupF ?: null, $grade, $typeF);
$filterGrades = is_super($me) && !$teacherF ? ALL_GRADES : teacher_grades($teacherF ? (db_one('SELECT * FROM users WHERE id = ?', [$teacherF]) ?? []) : $me);
$filterGroups = is_super($me)
    ? db_all('SELECT g.*, t.full_name AS teacher_name FROM class_groups g JOIN users t ON t.id = g.teacher_id' . ($teacherF ? ' WHERE g.teacher_id = ' . $teacherF : '') . ' ORDER BY t.full_name, g.id')
    : teacher_groups((int) $me['id']);
$qs = function (array $over) use ($teacherF, $grade, $groupF, $typeF) {
    $p = array_filter(array_merge(['docente' => $teacherF, 'grado' => $grade, 'grupo' => $groupF, 'tipo' => $typeF], $over));
    return '?' . http_build_query($p);
};

$students  = (int) db_val("SELECT COUNT(*) FROM users u WHERE u.role='student' $gWhere");
$withPre   = (int) db_val("SELECT COUNT(*) FROM assessments a JOIN users u ON u.id=a.user_id WHERE a.type='pretest' $gWhere");
$withPost  = (int) db_val("SELECT COUNT(*) FROM assessments a JOIN users u ON u.id=a.user_id WHERE a.type='postest' $gWhere");
$missionsAvg = (float) db_val("SELECT AVG(c) FROM (SELECT COUNT(um.mission_id) c FROM users u LEFT JOIN user_missions um ON um.user_id=u.id WHERE u.role='student' $gWhere GROUP BY u.id) t");
$gamesPlayed = (int) db_val("SELECT COUNT(*) FROM game_attempts a JOIN users u ON u.id=a.user_id WHERE a.status='finished' $gWhere");

// Pares pretest-postest (mismos estudiantes)
$pairs = db_all("SELECT pre.total pre_t, pre.dim1 pre_1, pre.dim2 pre_2, pre.dim3 pre_3, pre.level pre_l,
                        post.total post_t, post.dim1 post_1, post.dim2 post_2, post.dim3 post_3, post.level post_l
                   FROM assessments pre
                   JOIN assessments post ON post.user_id = pre.user_id AND post.type='postest'
                   JOIN users u ON u.id = pre.user_id
                  WHERE pre.type='pretest' $gWhere");

$dimKeys = ['t' => ['Prevención global', 'items' => 24, 'ranges' => PREVENTION_GLOBAL]];
$i = 1;
foreach (PREVENTION_DIMENSIONS as $k => $d) {
    $dimKeys[(string) $i++] = [$d['label'], 'items' => $d['items'], 'ranges' => $d['ranges']];
}
$tests = [];
foreach ($dimKeys as $k => $d) {
    $pre  = array_map('intval', array_column($pairs, 'pre_' . $k));
    $post = array_map('intval', array_column($pairs, 'post_' . $k));
    $tests[$k] = ['label' => $d[0], 'items' => $d['items'], 'ranges' => $d['ranges'], 'r' => paired_t($pre, $post), 'pre' => $pre, 'post' => $post];
}

// Distribución de niveles (todos los que tienen cada medición)
$levelDist = function (string $type, string $col, array $ranges) use ($gWhere) {
    $rows = db_all("SELECT a.$col v FROM assessments a JOIN users u ON u.id=a.user_id WHERE a.type=? $gWhere", [$type]);
    $out = ['Bajo' => 0, 'Medio' => 0, 'Alto' => 0];
    foreach ($rows as $r) $out[level_from_ranges((int) $r['v'], $ranges)]++;
    return ['n' => count($rows), 'd' => $out];
};

// Promedio por ítem
$itemMeans = db_all("SELECT si.number, si.short_label, si.dimension,
                            AVG(CASE WHEN a.type='pretest' THEN aa.value END) pre,
                            AVG(CASE WHEN a.type='postest' THEN aa.value END) post
                       FROM survey_items si
                  LEFT JOIN assessment_answers aa ON aa.item_id = si.id
                  LEFT JOIN assessments a ON a.id = aa.assessment_id
                  LEFT JOIN users u ON u.id = a.user_id
                      WHERE si.instrument='prevention' AND (u.id IS NULL OR (1=1 $gWhere))
                   GROUP BY si.id ORDER BY si.number");

// Valoración de la gamificación
$val = db_one("SELECT COUNT(*) n, AVG(total) t, AVG(dim1) d1, AVG(dim2) d2, AVG(dim3) d3
                 FROM assessments a JOIN users u ON u.id=a.user_id WHERE a.type='valoracion' $gWhere");

// Mini-pruebas y juegos por misión
$byGame = db_all("SELECT m.order_num, m.title mission, g.title, g.icon, g.is_quiz,
                         COUNT(DISTINCT a.user_id) players, AVG(a.percent) avg_pct,
                         SUM(a.percent >= g.pass_percent) passed, COUNT(a.id) plays
                    FROM games g JOIN missions m ON m.id=g.mission_id
               LEFT JOIN game_attempts a ON a.game_id=g.id AND a.status='finished'
                         AND a.user_id IN (SELECT u.id FROM users u WHERE u.role='student' $gWhere)
                GROUP BY g.id ORDER BY m.order_num, g.order_num");

$funnel = db_all("SELECT m.order_num, m.title, m.icon, COUNT(um.user_id) done
                    FROM missions m LEFT JOIN user_missions um ON um.mission_id=m.id
                         AND um.user_id IN (SELECT u.id FROM users u WHERE u.role='student' $gWhere)
                GROUP BY m.id ORDER BY m.order_num");


// Experimental vs. control: ganancia (post − pre) por tipo de grupo, con los mismos filtros salvo el tipo.
$gainWhere = student_scope_sql($me, $teacherF ?: null, $groupF ?: null, $grade);
$gainRows = db_all("SELECT g.type, pre.total pre_t, post.total post_t
                      FROM users u JOIN class_groups g ON g.id = u.group_id
                      JOIN assessments pre ON pre.user_id = u.id AND pre.type = 'pretest'
                      JOIN assessments post ON post.user_id = u.id AND post.type = 'postest'
                     WHERE 1=1 $gainWhere");
$gains = ['experimental' => [], 'control' => []];
$preBy = ['experimental' => [], 'control' => []];
$postBy = ['experimental' => [], 'control' => []];
foreach ($gainRows as $r) {
    $gains[$r['type']][]  = (int) $r['post_t'] - (int) $r['pre_t'];
    $preBy[$r['type']][]  = (int) $r['pre_t'];
    $postBy[$r['type']][] = (int) $r['post_t'];
}
$ecTest = welch_t($gains['experimental'], $gains['control']);

page_header('Panel docente', 'admin');
?>
<div class="section-head">
    <div>
        <h1><i class="bx bxs-bar-chart-alt-2"></i> Panel docente</h1>
        <p class="muted">Resultados de la estrategia gamificada para la prevención del phishing. Instrumento de 24 ítems (1–5); niveles según la Tabla 5.1 de la tesis.</p>
    </div>
    <div class="admin-actions">
        <div class="seg-control">
            <a class="<?= !$grade ? 'on' : '' ?>" href="<?= e($qs(['grado' => null])) ?>">Todos</a>
            <?php foreach ($filterGrades as $g): ?><a class="<?= $grade === $g ? 'on' : '' ?>" href="<?= e($qs(['grado' => $g])) ?>"><?= $g ?>.º</a><?php endforeach; ?>
        </div>
        <form method="get" class="filter-selects">
            <?php if ($grade): ?><input type="hidden" name="grado" value="<?= $grade ?>"><?php endif; ?>
            <?php if (is_super($me)): ?>
            <select name="docente" onchange="this.form.submit()">
                <option value="">Todos los docentes</option>
                <?php foreach (all_teachers() as $t): ?><option value="<?= (int) $t['id'] ?>" <?= $teacherF === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option><?php endforeach; ?>
            </select>
            <?php endif; ?>
            <select name="grupo" onchange="this.form.submit()">
                <option value="">Todos los grupos</option>
                <?php foreach ($filterGroups as $g): ?><option value="<?= (int) $g['id'] ?>" <?= $groupF === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?><?= isset($g['teacher_name']) ? ' · ' . e($g['teacher_name']) : '' ?></option><?php endforeach; ?>
            </select>
            <select name="tipo" onchange="this.form.submit()">
                <option value="">Experimental y control</option>
                <option value="experimental" <?= $typeF === 'experimental' ? 'selected' : '' ?>>Solo experimental</option>
                <option value="control" <?= $typeF === 'control' ? 'selected' : '' ?>>Solo control</option>
            </select>
        </form>
        <details class="dropdown">
            <summary class="btn btn-primary"><i class="bx bxs-download"></i> Exportar</summary>
            <div class="dropdown-panel">
                <a href="<?= e(url('admin/export.php' . $qs(['t' => 'evaluaciones']))) ?>">Puntajes pretest / postest / valoración (CSV)</a>
                <a href="<?= e(url('admin/export.php' . $qs(['t' => 'items']))) ?>">Respuestas por ítem, formato ancho (CSV)</a>
                <a href="<?= e(url('admin/export.php' . $qs(['t' => 'juegos']))) ?>">Resultados de juegos y mini-pruebas (CSV)</a>
            </div>
        </details>
    </div>
</div>

<div class="kpis">
    <div class="kpi"><span><i class="bx bxs-group"></i></span><b><?= $students ?></b><small>estudiantes</small></div>
    <div class="kpi"><span><i class="bx bxs-flag-alt"></i></span><b><?= $withPre ?></b><small>con pretest</small></div>
    <div class="kpi"><span><i class="bx bxs-graduation"></i></span><b><?= $withPost ?></b><small>con postest</small></div>
    <div class="kpi"><span><i class="bx bxs-map-alt"></i></span><b><?= fmt_num($missionsAvg, 1) ?></b><small>misiones promedio</small></div>
    <div class="kpi"><span><i class="bx bxs-joystick"></i></span><b><?= $gamesPlayed ?></b><small>partidas jugadas</small></div>
</div>

<section class="card">
    <h2><i class="bx bx-trending-up"></i> Comparación pretest – postest (muestras relacionadas)</h2>
    <p class="muted">Solo estudiantes con ambas mediciones (n = <?= count($pairs) ?>). Prueba t de Student para muestras relacionadas y tamaño del efecto d<sub>z</sub> de Cohen.</p>
    <?php if (count($pairs) < 2): ?>
        <div class="empty">Se necesitan al menos 2 estudiantes con pretest y postest para calcular la comparación.</div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Variable / dimensión</th><th>Rango</th><th>Media pretest</th><th>Media postest</th><th>Diferencia</th><th>Por ítem (pre <i class="bx bx-right-arrow-alt"></i> post)</th><th>t (gl)</th><th>p</th><th>d<sub>z</sub></th></tr></thead>
        <tbody>
        <?php foreach ($tests as $k => $t): $r = $t['r']; ?>
            <tr class="<?= $k === 't' ? 'strong' : '' ?>">
                <td><?= e($t['label']) ?></td>
                <td class="muted"><?= $t['items'] ?>–<?= $t['items'] * 5 ?></td>
                <td><?= fmt_num($r['mean_pre']) ?></td>
                <td><?= fmt_num($r['mean_post']) ?></td>
                <td><b class="<?= $r['diff'] >= 0 ? 'up' : 'down' ?>"><?= $r['diff'] >= 0 ? '+' : '' ?><?= fmt_num($r['diff']) ?></b></td>
                <td><?= fmt_num($r['mean_pre'] / $t['items']) ?> <i class="bx bx-right-arrow-alt"></i> <?= fmt_num($r['mean_post'] / $t['items']) ?></td>
                <td><?= fmt_num($r['t']) ?> (<?= $r['df'] ?>)</td>
                <td><?= fmt_p($r['p']) ?></td>
                <td><?= fmt_num($r['dz']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="muted small">Interpretación orientativa: p &lt; 0,05 indica una diferencia estadísticamente significativa. d<sub>z</sub> ≥ 0,8 se considera un efecto grande. Verifica la normalidad de las diferencias en tu software estadístico (Shapiro-Wilk) y, si no se cumple, usa Wilcoxon con el CSV exportado.</p>
    <?php endif; ?>

    <h3 class="mt">Niveles de prevención por medición</h3>
    <table class="table">
        <thead><tr><th>Dimensión o variable</th><th>Medición</th><th>n</th><th>Bajo n (%)</th><th>Medio n (%)</th><th>Alto n (%)</th></tr></thead>
        <tbody>
        <?php
        $cols = ['1' => 'dim1', '2' => 'dim2', '3' => 'dim3', 't' => 'total'];
        foreach (['1', '2', '3', 't'] as $k):
            foreach (['pretest' => 'Pretest', 'postest' => 'Postest'] as $type => $lbl):
                $ld = $levelDist($type, $cols[$k], $tests[$k]['ranges']);
        ?>
            <tr class="<?= $k === 't' ? 'strong' : '' ?>">
                <td><?= e($tests[$k]['label']) ?></td><td><?= $lbl ?></td><td><?= $ld['n'] ?></td>
                <?php foreach ($ld['d'] as $lvName => $cnt): ?>
                    <td><?= $cnt ?> (<?= $ld['n'] ? fmt_num($cnt / $ld['n'] * 100, 1) : '0' ?> %)</td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; endforeach; ?>
        </tbody>
    </table>
</section>

<div class="grid-2">
    <section class="card">
        <h2><i class="bx bxs-receipt"></i> Promedio por ítem</h2>
        <table class="table compact">
            <thead><tr><th>Ítem</th><th>Pre</th><th>Post</th><th>Cambio</th></tr></thead>
            <tbody>
            <?php $lastDim = null; foreach ($itemMeans as $it):
                if ($it['dimension'] !== $lastDim): $lastDim = $it['dimension']; ?>
                <tr class="group"><td colspan="4"><?= e(PREVENTION_DIMENSIONS[$lastDim]['label']) ?></td></tr>
            <?php endif; ?>
                <tr>
                    <td><?= (int) $it['number'] ?>. <?= e($it['short_label']) ?></td>
                    <td><?= fmt_num($it['pre']) ?></td>
                    <td><?= fmt_num($it['post']) ?></td>
                    <td><?= $it['pre'] !== null && $it['post'] !== null ? '<b class="up">+' . fmt_num($it['post'] - $it['pre']) . '</b>' : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <div>
        <section class="card">
            <h2><i class="bx bxs-star"></i> Valoración de la gamificación</h2>
            <p class="muted">Solo medición posterior (n = <?= (int) $val['n'] ?>). Escala 1–5; dimensiones de 3 a 15 puntos.</p>
            <?php if ((int) $val['n'] > 0): ?>
            <table class="table">
                <thead><tr><th>Dimensión</th><th>Media</th><th>Por ítem</th><th>Nivel</th></tr></thead>
                <tbody>
                <?php $j = 1; foreach (GAMIFICATION_DIMENSIONS as $gd): $mv = (float) $val['d' . $j++]; ?>
                    <tr><td><?= e($gd['label']) ?></td><td><?= fmt_num($mv) ?></td><td><?= fmt_num($mv / 3) ?></td><td><?= level_badge(level_from_ranges((int) round($mv), $gd['ranges'])) ?></td></tr>
                <?php endforeach; ?>
                    <tr class="strong"><td>Gamificación global</td><td><?= fmt_num($val['t']) ?></td><td><?= fmt_num($val['t'] / 9) ?></td><td><?= level_badge(level_from_ranges((int) round($val['t']), GAMIFICATION_GLOBAL)) ?></td></tr>
                </tbody>
            </table>
            <?php else: ?><div class="empty">Aún no hay valoraciones.</div><?php endif; ?>
        </section>

        <section class="card">
            <h2><i class="bx bxs-map-alt"></i> Avance por misión</h2>
            <?php foreach ($funnel as $f): $pct = $students ? (int) round($f['done'] / $students * 100) : 0; ?>
                <div class="funnel-row">
                    <span><?= icon($f['icon']) ?> <?= (int) $f['order_num'] ?>. <?= e($f['title']) ?></span>
                    <?= progress_bar($pct) ?>
                    <b><?= (int) $f['done'] ?></b>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
</div>

<section class="card">
    <h2><i class="bx bxs-joystick"></i> Desempeño en minijuegos y mini-pruebas</h2>
    <p class="muted">Evidencia objetiva del aprendizaje: porcentaje promedio de aciertos por partida.</p>
    <table class="table">
        <thead><tr><th>Misión</th><th>Juego</th><th>Tipo</th><th>Jugadores</th><th>Partidas</th><th>Promedio</th><th>Partidas aprobadas</th></tr></thead>
        <tbody>
        <?php foreach ($byGame as $g): ?>
            <tr>
                <td><?= (int) $g['order_num'] ?></td>
                <td><?= icon($g['icon']) ?> <?= e($g['title']) ?></td>
                <td><?= $g['is_quiz'] ? '<span class="tag-quiz">Mini-prueba</span>' : 'Práctica' ?></td>
                <td><?= (int) $g['players'] ?></td>
                <td><?= (int) $g['plays'] ?></td>
                <td><?= $g['plays'] ? progress_bar((int) round($g['avg_pct'])) . ' ' . (int) round($g['avg_pct']) . '%' : '—' ?></td>
                <td><?= $g['plays'] ? (int) $g['passed'] . ' (' . (int) round($g['passed'] / $g['plays'] * 100) . '%)' : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<section class="card" id="control">
    <h2><i class="bx bx-git-compare"></i> Grupo experimental vs. grupo control</h2>
    <p class="muted">Compara la <b>ganancia</b> (postest − pretest) de los estudiantes de grupos experimentales con la de los grupos control. Prueba t de Welch para muestras independientes y d de Cohen.</p>
    <table class="table">
        <thead><tr><th>Grupo</th><th>n con pre y post</th><th>Media pretest</th><th>Media postest</th><th>Ganancia media</th></tr></thead>
        <tbody>
        <?php foreach (['experimental' => 'Experimental', 'control' => 'Control'] as $k => $label): ?>
            <tr>
                <td><span class="group-tag gt-<?= $k ?>"><?= $label ?></span></td>
                <td><?= count($gains[$k]) ?></td>
                <td><?= fmt_num($preBy[$k] ? stat_mean($preBy[$k]) : null) ?></td>
                <td><?= fmt_num($postBy[$k] ? stat_mean($postBy[$k]) : null) ?></td>
                <td><b><?= fmt_num($gains[$k] ? stat_mean($gains[$k]) : null) ?></b></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($ecTest): ?>
        <p>Diferencia de ganancias: <b><?= fmt_num($ecTest['diff']) ?></b> · t(<?= fmt_num($ecTest['df'], 1) ?>) = <?= fmt_num($ecTest['t']) ?> · p = <?= fmt_p($ecTest['p']) ?> · d = <?= fmt_num($ecTest['d']) ?>
            <?= $ecTest['p'] !== null && $ecTest['p'] < 0.05 ? '<span class="tag-good">Diferencia significativa (p &lt; 0,05)</span>' : '<span class="tag-quiz">Sin diferencia significativa</span>' ?></p>
    <?php else: ?>
        <div class="empty">Se necesitan al menos 2 estudiantes con pretest y postest en cada tipo de grupo. Si tu diseño es solo pre–post con un grupo (como el de la tesis), esta sección es opcional.</div>
    <?php endif; ?>
</section>

<?php if (arcade_ready()):
    $arcade = db_all("SELECT g.title, g.icon, g.type, COUNT(DISTINCT a.user_id) players, COUNT(a.id) plays, AVG(a.percent) avg_pct,
                             SUM(a.percent >= g.pass_percent) passed, SUM(a.grand_prize) grand
                        FROM arcade_games g
                   LEFT JOIN arcade_attempts a ON a.game_id = g.id AND a.status = 'finished'
                         AND a.user_id IN (SELECT u.id FROM users u WHERE u.role = 'student' $gWhere)
                    GROUP BY g.id ORDER BY g.order_num");
    $vids = db_all("SELECT v.title, v.channel, SUM(uv.watched_at IS NOT NULL) watched, SUM(uv.quiz_correct = 1) solved,
                           SUM(uv.quiz_correct = 1 AND uv.quiz_tries = 1) first_try
                      FROM videos v
                 LEFT JOIN user_videos uv ON uv.video_id = v.id
                       AND uv.user_id IN (SELECT u.id FROM users u WHERE u.role = 'student' $gWhere)
                  GROUP BY v.id ORDER BY v.order_num");
?>
<section class="card">
    <h2><i class="bx bxs-joystick-alt"></i> Zona de refuerzo (complementaria)</h2>
    <p class="muted">Actividades opcionales: no forman parte de las 6 sesiones ni del acceso al postest.</p>
    <table class="table">
        <thead><tr><th>Juego</th><th>Tipo</th><th>Jugadores</th><th>Partidas</th><th>Promedio</th><th>Aprobadas</th><th>Gran premio</th></tr></thead>
        <tbody>
        <?php foreach ($arcade as $g): ?>
            <tr>
                <td><?= icon($g['icon']) ?> <?= e($g['title']) ?></td>
                <td><?= e(ARCADE_TYPES[$g['type']]) ?></td>
                <td><?= (int) $g['players'] ?></td>
                <td><?= (int) $g['plays'] ?></td>
                <td><?= $g['plays'] ? progress_bar((int) round($g['avg_pct'])) . ' ' . (int) round($g['avg_pct']) . '%' : '—' ?></td>
                <td><?= $g['plays'] ? (int) $g['passed'] : '—' ?></td>
                <td><?= $g['type'] === 'office' ? (int) $g['grand'] . ' <i class="bx bxs-trophy"></i>' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <h3 class="mt"><i class="bx bxs-movie-play"></i> Videoteca</h3>
    <table class="table">
        <thead><tr><th>Video</th><th>Canal</th><th>Lo vieron</th><th>Pregunta acertada</th><th>A la primera</th></tr></thead>
        <tbody>
        <?php foreach ($vids as $v): ?>
            <tr><td><?= e($v['title']) ?></td><td><?= e($v['channel']) ?></td><td><?= (int) $v['watched'] ?></td><td><?= (int) $v['solved'] ?></td><td><?= (int) $v['first_try'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>
<?php page_footer();
