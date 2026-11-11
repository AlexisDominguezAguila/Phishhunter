<?php
require_once __DIR__ . '/includes/bootstrap.php';

$viewer = require_login();
// Un docente puede ver el perfil de cualquier estudiante: profile.php?id=5
$uid = (int) $viewer['id'];
if (is_staff($viewer) && !empty($_GET['id'])) {
    $uid = (int) $_GET['id'];
}
$user = db_one('SELECT * FROM users WHERE id = ?', [$uid]);
if (!$user || ($uid !== (int) $viewer['id'] && !can_manage_student($viewer, $user))) {
    flash('error', 'No puedes ver ese perfil.');
    redirect(is_staff($viewer) ? 'admin/students.php' : 'dashboard.php');
}
$own   = $uid === (int) $viewer['id'];

// Cambio de contraseña (solo el propio usuario)
$pwErrors = [];
if ($own && is_post() && ($_POST['action'] ?? '') === 'change_password') {
    csrf_check();
    $cur = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $rep = (string) ($_POST['repeat'] ?? '');
    if (!password_verify($cur, $user['password_hash'])) $pwErrors[] = 'La contraseña actual no es correcta.';
    if (mb_strlen($new) < 8) $pwErrors[] = 'La nueva contraseña debe tener al menos 8 caracteres (mejor una frase).';
    if ($new !== $rep) $pwErrors[] = 'Las contraseñas nuevas no coinciden.';
    if ($new === $cur) $pwErrors[] = 'La nueva contraseña debe ser distinta de la actual.';
    if (!$pwErrors) {
        db_exec('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $uid]);
        flash('success', 'Tu contraseña se cambió correctamente.');
        redirect('profile.php#cuenta');
    }
}
$lv    = level_info((int) $user['xp']);
$stats = user_stats($uid);
$rank  = user_rank($uid);

$badges = db_all('SELECT b.*, ub.earned_at FROM badges b LEFT JOIN user_badges ub ON ub.badge_id=b.id AND ub.user_id=?
                   ORDER BY FIELD(b.category,"evaluacion","mision","habilidad","especial"), b.id', [$uid]);
$achievements = achievements_with_progress($uid);
$pre   = get_assessment($uid, 'pretest');
$post  = get_assessment($uid, 'postest');
$val   = get_assessment($uid, 'valoracion');
$preB  = assessment_breakdown($pre);
$postB = assessment_breakdown($post);
$valB  = assessment_breakdown($val, 'gamification');
$learning = learning_by_dimension($uid);
$quizzes = db_all('SELECT g.title, g.icon, g.dimension, m.order_num, COALESCE(p.best_percent,0) best, COALESCE(p.best_stars,0) stars, COALESCE(p.plays,0) plays
                     FROM games g JOIN missions m ON m.id=g.mission_id
                LEFT JOIN user_game_progress p ON p.game_id=g.id AND p.user_id=?
                    WHERE g.is_quiz=1 ORDER BY m.order_num', [$uid]);
$history = db_all('SELECT a.*, g.title, g.icon FROM game_attempts a JOIN games g ON g.id=a.game_id
                    WHERE a.user_id=? AND a.status="finished" ORDER BY a.finished_at DESC LIMIT 10', [$uid]);

page_header($own ? 'Mi progreso' : 'Progreso de ' . $user['full_name'], $own ? 'profile' : 'admin');
?>
<?php if (!$own): ?><a class="back" href="<?= e(url('admin/students.php')) ?>"><i class="bx bx-left-arrow-alt"></i> Volver a estudiantes</a><?php endif; ?>

<section class="profile-hero card">
    <?= avatar_html($user, 'xl') ?>
    <div class="ph-main">
        <h1><?= e($user['full_name']) ?></h1>
        <div class="player-title">@<?= e($user['username']) ?> · <?= e(user_title($user)) ?><?= $user['grade'] ? ' · ' . (int) $user['grade'] . '.º ' . e($user['section']) : '' ?></div>
        <div class="level-row">
            <span class="level-pill"><?= icon($lv['icon']) ?> Nivel <?= $lv['level'] ?> · <?= e($lv['name']) ?></span>
            <span class="muted small"><?= number_format((int) $user['xp']) ?> XP<?= $lv['next'] ? ' / ' . number_format($lv['next']) : '' ?></span>
        </div>
        <?= progress_bar($lv['progress'], 'bar-xp') ?>
    </div>
    <div class="ph-stats">
        <div><b><?= $rank ? '#' . $rank : '—' ?></b><span>posición</span></div>
        <div><b><?= $stats['missions'] ?>/6</b><span>misiones</span></div>
        <div><b><?= $stats['stars'] ?></b><span>estrellas</span></div>
        <div><b><?= $stats['badges'] ?></b><span>insignias</span></div>
        <div><b><?= $stats['games_played'] ?></b><span>partidas</span></div>
        <div><b><?= $stats['correct_answers'] ?></b><span>aciertos</span></div>
        <div><b><?= (int) $user['coins'] ?></b><span>monedas</span></div>
        <div><b><?= (int) $user['best_streak'] ?></b><span>mejor racha</span></div>
    </div>
</section>

<nav class="tabs" id="profile-tabs">
    <a href="#evaluacion"><i class="bx bx-trending-up"></i> Aprendizaje</a>
    <a href="#insignias"><i class="bx bxs-medal"></i> Insignias</a>
    <a href="#logros"><i class="bx bxs-bullseye"></i> Logros</a>
    <a href="#historial"><i class="bx bx-history"></i> Historial</a>
</nav>

<section class="card" id="evaluacion">
    <h2><i class="bx bx-trending-up"></i> Mi nivel de prevención del phishing</h2>
    <p class="muted">Comparación entre la evaluación diagnóstica (antes de las misiones) y la evaluación final (después). Niveles según los intervalos de la investigación: Bajo, Medio y Alto.</p>

    <?php if (!$pre): ?>
        <div class="empty">
            <p>Todavía no hay evaluación diagnóstica.</p>
            <?php if ($own): ?><a class="btn btn-primary" href="<?= e(url('evaluation.php?type=pretest')) ?>">Hacer evaluación diagnóstica</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="compare">
            <div class="compare-total">
                <div class="ct-box">
                    <span>Antes</span>
                    <b><?= (int) $pre['total'] ?></b><small>/120</small>
                    <?= level_badge($pre['level']) ?>
                </div>
                <div class="ct-arrow"><?= $post ? '<i class="bx bx-right-arrow-alt"></i>' : '⋯' ?></div>
                <div class="ct-box <?= $post ? '' : 'pending' ?>">
                    <span>Después</span>
                    <?php if ($post): ?>
                        <b><?= (int) $post['total'] ?></b><small>/120</small>
                        <?= level_badge($post['level']) ?>
                    <?php else: ?>
                        <b>?</b><small>/120</small><span class="muted small">Completa las 6 misiones</span>
                    <?php endif; ?>
                </div>
                <?php if ($post): $diff = (int) $post['total'] - (int) $pre['total']; ?>
                    <div class="ct-diff <?= $diff >= 0 ? 'up' : 'down' ?>"><?= $diff >= 0 ? '+' : '' ?><?= $diff ?> puntos</div>
                <?php endif; ?>
            </div>

            <div class="dim-bars">
                <?php foreach ($preB as $key => $d): $p = $postB[$key] ?? null; ?>
                    <div class="dim-row">
                        <div class="dim-label"><b><?= e($d['label']) ?></b><small class="muted">promedio por ítem (1 a 5)</small></div>
                        <div class="dim-bar-pair">
                            <div class="dbar pre" title="Antes"><span style="width: <?= round($d['per_item'] / 5 * 100) ?>%"></span><em>Antes <?= number_format($d['per_item'], 2) ?> · <?= e($d['level']) ?></em></div>
                            <?php if ($p): ?>
                            <div class="dbar post" title="Después"><span style="width: <?= round($p['per_item'] / 5 * 100) ?>%"></span><em>Después <?= number_format($p['per_item'], 2) ?> · <?= e($p['level']) ?></em></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if ($own && !$post && all_missions_completed($uid)): ?>
            <a class="btn btn-primary" href="<?= e(url('evaluation.php?type=postest')) ?>">Hacer evaluación final</a>
        <?php endif; ?>
    <?php endif; ?>

    <h3 class="mt"><i class="bx bxs-brain"></i> Desempeño en los juegos y mini-pruebas</h3>
    <p class="muted">Porcentaje de respuestas correctas por dimensión (evidencia objetiva de aprendizaje).</p>
    <div class="learn-grid">
        <?php foreach ($learning as $key => $l): ?>
            <div class="learn-card">
                <b><?= e($l['label']) ?></b>
                <div class="lc-row"><span><i class="bx bxs-joystick"></i> Juegos</span><?= progress_bar((int) $l['games']) ?><em><?= $l['games'] === null ? '—' : $l['games'] . '%' ?></em></div>
                <div class="lc-row"><span><i class="bx bxs-edit"></i> Pruebas</span><?= progress_bar((int) $l['quiz'], 'bar-quiz') ?><em><?= $l['quiz'] === null ? '—' : $l['quiz'] . '%' ?></em></div>
            </div>
        <?php endforeach; ?>
    </div>

    <table class="table mt">
        <thead><tr><th>Mini-prueba</th><th>Misión</th><th>Mejor resultado</th><th>Estrellas</th><th>Intentos</th></tr></thead>
        <tbody>
        <?php foreach ($quizzes as $q): ?>
            <tr>
                <td><?= icon($q['icon']) ?> <?= e($q['title']) ?></td>
                <td><?= (int) $q['order_num'] ?></td>
                <td><?= $q['plays'] ? (int) $q['best'] . '%' : '<span class="muted">Pendiente</span>' ?></td>
                <td><?= stars_html((int) $q['stars']) ?></td>
                <td><?= (int) $q['plays'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($valB): ?>
        <h3 class="mt"><i class="bx bxs-star"></i> Mi valoración de la experiencia</h3>
        <div class="learn-grid">
            <?php foreach ($valB as $v): ?>
                <div class="learn-card"><b><?= e($v['label']) ?></b><div class="big"><?= $v['score'] ?>/15 <?= level_badge($v['level']) ?></div></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card" id="insignias">
    <h2><i class="bx bxs-medal"></i> Insignias <span class="muted small"><?= $stats['badges'] ?> de <?= count($badges) ?></span></h2>
    <div class="badge-grid">
        <?php foreach ($badges as $b): $got = $b['earned_at'] !== null; ?>
            <div class="badge-tile <?= $got ? 'got' : 'locked' ?>" style="--bc: <?= e($b['color']) ?>">
                <div class="bt-icon"><?= $got ? icon($b['icon']) : '<i class="bx bxs-lock-alt"></i>' ?></div>
                <b><?= e($b['name']) ?></b>
                <small><?= e($b['description']) ?></small>
                <?php if ($got): ?><em><?= date('d/m/Y', strtotime($b['earned_at'])) ?></em><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="card" id="logros">
    <h2><i class="bx bxs-bullseye"></i> Logros <span class="muted small"><?= count(array_filter($achievements, function ($a) { return $a['unlocked_at']; })) ?> de <?= count($achievements) ?></span></h2>
    <div class="ach-grid">
        <?php foreach ($achievements as $a): $got = $a['unlocked_at'] !== null; ?>
            <div class="ach-tile <?= $got ? 'got' : '' ?>">
                <span class="ach-icon"><?= icon($a['icon']) ?></span>
                <div>
                    <b><?= e($a['name']) ?></b>
                    <small class="muted"><?= e($a['description']) ?></small>
                    <?= progress_bar($a['progress']) ?>
                    <small><?= (int) $a['current'] ?>/<?= (int) $a['goal'] ?> · Premio: <?= $a['xp_reward'] ? '+' . (int) $a['xp_reward'] . ' XP ' : '' ?>+<?= (int) $a['coin_reward'] ?> <i class="bx bxs-coin"></i></small>
                </div>
                <?php if ($got): ?><span class="tag-good"><i class="bx bx-check"></i></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="card" id="historial">
    <h2><i class="bx bx-history"></i> Últimas partidas</h2>
    <table class="table">
        <thead><tr><th>Juego</th><th>Resultado</th><th>Estrellas</th><th>Recompensa</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach ($history as $h): ?>
            <tr>
                <td><?= icon($h['icon']) ?> <?= e($h['title']) ?></td>
                <td><?= (int) $h['correct'] ?>/<?= (int) $h['total'] ?> · <?= (int) $h['percent'] ?>%</td>
                <td><?= stars_html((int) $h['stars']) ?></td>
                <td>+<?= (int) $h['xp_earned'] ?> XP · +<?= (int) $h['coins_earned'] ?> <i class="bx bxs-coin"></i></td>
                <td class="muted"><?= date('d/m/Y H:i', strtotime($h['finished_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$history): ?><tr><td colspan="5" class="muted">Aún no hay partidas.</td></tr><?php endif; ?>
        </tbody>
    </table>
</section>
<?php if ($own): ?>
<section class="card" id="cuenta">
    <h2><?= icon('bxs-lock-alt') ?> Cambiar mi contraseña</h2>
    <?php if ($pwErrors): ?><div class="alert alert-error"><?php foreach ($pwErrors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>
    <form method="post" class="form pw-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="change_password">
        <label>Contraseña actual<input type="password" name="current" required autocomplete="current-password"></label>
        <label>Nueva contraseña
            <input type="password" name="new" id="pw-new" required minlength="8" autocomplete="new-password">
            <div class="pw-meter" id="pw-meter"><span></span></div>
            <small class="muted" id="pw-text">Tip: usa una frase de 3 o 4 palabras sin relación.</small>
        </label>
        <label>Repite la nueva contraseña<input type="password" name="repeat" required minlength="8" autocomplete="new-password"></label>
        <button class="btn btn-primary" type="submit"><?= icon('bx-check') ?> Guardar contraseña</button>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var pw = document.getElementById('pw-new');
        pw.addEventListener('input', function () { PHUI.meter(pw.value, document.getElementById('pw-meter'), document.getElementById('pw-text')); });
    });
    </script>
</section>
<?php endif; ?>
<?php page_footer();
