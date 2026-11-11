<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user   = require_login();
$period = ($_GET['p'] ?? 'global') === 'semana' ? 'semana' : 'global';
$grade  = in_array((int) ($_GET['grado'] ?? 0), ALL_GRADES, true) ? (int) $_GET['grado'] : null;
// Cada estudiante compite con los compañeros de su docente; el docente ve a los suyos y el súper admin, a todos.
$scopeTeacher = is_super($user) ? null : (is_staff($user) ? (int) $user['id'] : (int) $user['teacher_id']);
$rows   = leaderboard($period, $grade, 50, $scopeTeacher);
$gradeOptions = is_staff($user) ? teacher_grades($user) : ALL_GRADES;
if (is_super($user)) $gradeOptions = ALL_GRADES;
if (!is_staff($user)) {
    $gradeOptions = array_map('intval', array_column(db_all("SELECT DISTINCT grade FROM users WHERE role='student' AND teacher_id <=> ? AND grade IS NOT NULL ORDER BY grade", [$user['teacher_id']]), 'grade'));
}
$podium = array_slice($rows, 0, 3);
$myPos  = null;
foreach ($rows as $i => $r) {
    if ((int) $r['id'] === (int) $user['id']) $myPos = $i + 1;
}

function lb_link(string $p, ?int $g): string
{
    return url('leaderboard.php?p=' . $p . ($g ? '&grado=' . $g : ''));
}

page_header('Clasificación', 'leaderboard');
?>
<div class="section-head">
    <div>
        <h1><i class="bx bxs-trophy"></i> Tabla de cazadores</h1>
        <p class="muted">Gana XP completando misiones, consiguiendo estrellas e insignias. La tabla semanal cuenta la XP de los últimos 7 días.</p>
    </div>
    <?php if ($myPos): ?><div class="my-pos">Tu posición: <b>#<?= $myPos ?></b></div><?php endif; ?>
</div>

<div class="filters">
    <div class="seg-control">
        <a class="<?= $period === 'global' ? 'on' : '' ?>" href="<?= e(lb_link('global', $grade)) ?>">General</a>
        <a class="<?= $period === 'semana' ? 'on' : '' ?>" href="<?= e(lb_link('semana', $grade)) ?>">Esta semana</a>
    </div>
    <div class="seg-control">
        <a class="<?= !$grade ? 'on' : '' ?>" href="<?= e(lb_link($period, null)) ?>">Todos</a>
        <?php foreach ($gradeOptions as $g): ?>
            <a class="<?= $grade === $g ? 'on' : '' ?>" href="<?= e(lb_link($period, $g)) ?>"><?= $g ?>.º</a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($podium): ?>
<div class="podium">
    <?php foreach ([1, 0, 2] as $pos): if (!isset($podium[$pos])) continue; $p = $podium[$pos]; ?>
        <div class="pod pod-<?= $pos + 1 ?> <?= (int) $p['id'] === (int) $user['id'] ? 'me' : '' ?>">
            <div class="pod-medal"><?= ['<i class="bx bxs-medal"></i>', '<i class="bx bxs-medal"></i>', '<i class="bx bxs-medal"></i>'][$pos] ?></div>
            <?= avatar_html($p, 'lg') ?>
            <b><?= e($p['username']) ?></b>
            <small><?= e(level_info((int) $p['xp'])['name']) ?></small>
            <div class="pod-score"><?= number_format((int) $p['score']) ?> XP</div>
            <div class="pod-base"><?= $pos + 1 ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
    <table class="table board">
        <thead><tr><th>#</th><th>Cazador</th><th>Grado</th><th>Nivel</th><th>Insignias</th><th class="right"><?= $period === 'semana' ? 'XP semana' : 'XP total' ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r): $l = level_info((int) $r['xp']); ?>
            <tr class="<?= (int) $r['id'] === (int) $user['id'] ? 'me' : '' ?>">
                <td><span class="rank rank-<?= $i + 1 ?>"><?= $i + 1 ?></span></td>
                <td class="who"><?= avatar_html($r, 'sm') ?> <span><b><?= e($r['username']) ?></b><small><?= e(user_title($r)) ?></small></span></td>
                <td><?= $r['grade'] ? (int) $r['grade'] . '.º ' . e($r['section']) : '—' ?></td>
                <td><?= icon($l['icon']) ?> <?= $l['level'] ?></td>
                <td><i class="bx bxs-medal"></i> <?= (int) $r['badges'] ?></td>
                <td class="right"><b><?= number_format((int) $r['score']) ?></b></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="muted">Todavía no hay cazadores en esta tabla.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<p class="tip"><i class="bx bxs-group"></i> La tabla es para motivarte, no para compararte. Cada persona aprende a su ritmo: lo importante es que nadie te pesque.</p>
<?php page_footer();
