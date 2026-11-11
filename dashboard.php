<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$uid  = (int) $user['id'];
$isAdmin = is_staff($user);
$lv   = level_info((int) $user['xp']);
$missions = missions_with_progress($uid, $isAdmin);
$hasPre   = has_assessment($uid, 'pretest');
$hasPost  = has_assessment($uid, 'postest');
$allDone  = all_missions_completed($uid);
$rank     = user_rank($uid);
$totalStudents = (int) db_val("SELECT COUNT(*) FROM users WHERE role='student' AND teacher_id <=> ?", [$user['teacher_id'] ?? null]);
$badges   = db_all('SELECT b.* FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.earned_at DESC LIMIT 6', [$uid]);
$badgeCount = (int) db_val('SELECT COUNT(*) FROM user_badges WHERE user_id=?', [$uid]);
$badgeTotal = (int) db_val('SELECT COUNT(*) FROM badges');
$nextAch  = array_slice(array_values(array_filter(achievements_with_progress($uid), function ($a) { return $a['unlocked_at'] === null; })), 0, 50);
usort($nextAch, function ($a, $b) { return $b['progress'] <=> $a['progress']; });
$nextAch  = array_slice($nextAch, 0, 3);
$activity = db_all('SELECT * FROM xp_log WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 6', [$uid]);
$doneCount = count(array_filter($missions, function ($m) { return $m['completed']; }));

// Grupo control: solo responde las evaluaciones (sin intervención) hasta terminar el postest.
if (!$isAdmin && !can_play($user)) {
    $group = student_group($user);
    page_header('Mis evaluaciones', 'dashboard');
    ?>
    <section class="control-hero card">
        <?= mascot_say(!$hasPre
            ? 'Tu grupo participa en un estudio sobre seguridad en internet. Primero responde la <b>evaluación diagnóstica</b>: son 24 preguntas y no hay respuestas buenas ni malas.'
            : ($group && $group['postest_open'] ? '¡Ya puedes responder la <b>evaluación final</b>! Es el mismo cuestionario del inicio.' : '¡Gracias! Ya registraste tu evaluación diagnóstica. Tu docente te avisará cuando puedas responder la evaluación final.'), $hasPre && !($group && $group['postest_open']) ? 'feliz' : 'hola') ?>
        <h1><?= icon('bx-clipboard') ?> Mis evaluaciones</h1>
        <p class="muted">Grupo: <b><?= e($group['name'] ?? '—') ?></b>. Cuando termines la evaluación final se desbloquearán las misiones y los juegos para que también aprendas jugando.</p>
        <div class="control-steps">
            <div class="cs-step <?= $hasPre ? 'done' : 'current' ?>">
                <span><?= icon($hasPre ? 'bxs-check-circle' : 'bxs-flag-alt') ?></span>
                <div><b>1. Evaluación diagnóstica</b><small><?= $hasPre ? 'Completada' : 'Pendiente' ?></small></div>
                <?php if (!$hasPre): ?><a class="btn btn-primary" href="<?= e(url('evaluation.php?type=pretest')) ?>">Comenzar</a><?php endif; ?>
            </div>
            <div class="cs-step <?= $hasPost ? 'done' : ($hasPre && $group && $group['postest_open'] ? 'current' : 'locked') ?>">
                <span><?= icon($hasPost ? 'bxs-check-circle' : ($group && $group['postest_open'] ? 'bxs-graduation' : 'bxs-lock-alt')) ?></span>
                <div><b>2. Evaluación final</b><small><?= $group && $group['postest_open'] ? 'Disponible' : 'Tu docente la habilitará' ?></small></div>
                <?php if ($hasPre && $group && $group['postest_open']): ?><a class="btn btn-primary" href="<?= e(url('evaluation.php?type=postest')) ?>">Comenzar</a><?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    page_footer();
    return;
}

page_header('Misiones', 'dashboard');
?>
<div class="dash">
    <aside class="dash-side">
        <div class="card player-card">
            <?= avatar_html($user, 'xl') ?>
            <h2><?= e($user['full_name']) ?></h2>
            <div class="player-title"><?= e(user_title($user)) ?></div>
            <div class="level-row">
                <span class="level-pill"><?= icon($lv['icon']) ?> Nivel <?= $lv['level'] ?></span>
                <span class="muted small"><?= e($lv['name']) ?></span>
            </div>
            <?= progress_bar($lv['progress'], 'bar-xp') ?>
            <div class="xp-caption">
                <b><?= number_format((int) $user['xp']) ?> XP</b>
                <?php if ($lv['next']): ?><span class="muted">· faltan <?= number_format($lv['next'] - (int) $user['xp']) ?> para «<?= e($lv['next_name']) ?>»</span><?php else: ?><span class="muted">· ¡Nivel máximo!</span><?php endif; ?>
            </div>
            <div class="player-stats">
                <div><span><i class="bx bxs-coin"></i></span><b><?= (int) $user['coins'] ?></b><small>monedas</small></div>
                <div><span><i class="bx bxs-hot"></i></span><b><?= (int) $user['streak'] ?></b><small>días racha</small></div>
                <div><span><i class="bx bxs-medal"></i></span><b><?= $badgeCount ?>/<?= $badgeTotal ?></b><small>insignias</small></div>
                <div><span><i class="bx bxs-trophy"></i></span><b><?= $rank ? '#' . $rank : '—' ?></b><small>de <?= $totalStudents ?></small></div>
            </div>
        </div>

        <div class="card">
            <h3><i class="bx bxs-bullseye"></i> Próximos logros</h3>
            <?php foreach ($nextAch as $a): ?>
                <div class="ach-mini">
                    <span class="ach-icon"><?= icon($a['icon']) ?></span>
                    <div>
                        <b><?= e($a['name']) ?></b>
                        <small class="muted"><?= e($a['description']) ?></small>
                        <?= progress_bar($a['progress']) ?>
                        <small class="muted"><?= (int) $a['current'] ?> / <?= (int) $a['goal'] ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$nextAch): ?><p class="muted">¡Desbloqueaste todos los logros! <i class="bx bxs-crown"></i></p><?php endif; ?>
        </div>
    </aside>

    <section class="dash-main">
        <?php if (!$isAdmin && !$hasPre): ?>
            <div class="banner banner-start">
                <div class="banner-icon"><i class="bx bxs-flag-alt"></i></div>
                <div>
                    <h2>Antes de empezar: tu evaluación diagnóstica</h2>
                    <p>Responde 24 preguntas rápidas sobre cómo actúas frente a mensajes y cuentas. No hay respuestas buenas ni malas: sirve para medir cuánto avanzas al final.</p>
                </div>
                <a class="btn btn-primary" href="<?= e(url('evaluation.php?type=pretest')) ?>">Comenzar evaluación</a>
            </div>
        <?php elseif (!$isAdmin && $allDone && !$hasPost): ?>
            <div class="banner banner-final">
                <div class="banner-icon"><i class="bx bxs-graduation"></i></div>
                <div>
                    <h2>¡Completaste todas las misiones!</h2>
                    <p>Responde la evaluación final para ver cuánto mejoraste desde el inicio y obtener la insignia «Cazador graduado».</p>
                </div>
                <a class="btn btn-primary" href="<?= e(url('evaluation.php?type=postest')) ?>">Evaluación final</a>
            </div>
        <?php elseif (!$isAdmin && $hasPost): ?>
            <div class="banner banner-done">
                <div class="banner-icon"><i class="bx bx-target-lock"></i></div>
                <div>
                    <h2>¡Eres un PhishHunter graduado!</h2>
                    <p>Sigue practicando para conseguir todas las estrellas, subir en la tabla y desbloquear recompensas.</p>
                </div>
                <a class="btn btn-soft" href="<?= e(url('profile.php#evaluacion')) ?>">Ver mi avance</a>
            </div>
        <?php endif; ?>

        <div class="section-head">
            <div>
                <h1>Ruta del cazador</h1>
                <p class="muted">Cada misión corresponde a una sesión. Completa todos sus minijuegos para desbloquear la siguiente.</p>
            </div>
            <div class="route-progress">
                <b><?= $doneCount ?>/<?= count($missions) ?></b> misiones
                <?= progress_bar((int) round($doneCount / max(1, count($missions)) * 100)) ?>
            </div>
        </div>

        <ol class="map">
            <?php $foundCurrent = false; foreach ($missions as $i => $m):
                $state = $m['completed'] ? 'done' : ($m['unlocked'] ? 'open' : 'locked');
                $isCurrent = !$foundCurrent && $state === 'open';
                if ($isCurrent) $foundCurrent = true;
            ?>
            <li class="map-node <?= $state ?> <?= $isCurrent ? 'current' : '' ?> <?= $i % 2 ? 'side-r' : 'side-l' ?>" style="--mc: <?= e($m['color']) ?>">
                <?php if ($state === 'locked'): ?>
                <div class="node-card">
                <?php else: ?>
                <a class="node-card" href="<?= e(url('mission.php?m=' . urlencode($m['slug']))) ?>">
                <?php endif; ?>
                    <span class="node-badge"><?= $state === 'locked' ? '<i class="bx bxs-lock-alt"></i>' : icon($m['icon']) ?></span>
                    <div class="node-body">
                        <span class="node-num">Misión <?= (int) $m['order_num'] ?> · <?= e(dimension_label($m['dimension'])) ?></span>
                        <h3><?= e($m['title']) ?></h3>
                        <p><?= e($m['subtitle']) ?></p>
                        <div class="node-meta">
                            <?= stars_html($m['stars'], $m['max_stars']) ?>
                            <span class="muted small"><?= $m['games_passed'] ?>/<?= $m['games_total'] ?> juegos</span>
                        </div>
                        <span class="node-cta">
                            <?php if ($state === 'done'): ?><i class="bx bx-check"></i> Completada<?php elseif ($state === 'open'): ?><?= $m['games_passed'] ? 'Continuar' : 'Jugar' ?> <i class="bx bx-right-arrow-alt"></i><?php else: ?><i class="bx bxs-lock-alt"></i> <?= $hasPre || $isAdmin ? 'Completa la misión anterior' : 'Haz tu evaluación diagnóstica' ?><?php endif; ?>
                        </span>
                    </div>
                <?= $state === 'locked' ? '</div>' : '</a>' ?>
            </li>
            <?php endforeach; ?>
            <li class="map-node finish <?= $hasPost ? 'done' : '' ?>">
                <div class="node-card">
                    <span class="node-badge"><i class="bx bxs-graduation"></i></span>
                    <div class="node-body">
                        <span class="node-num">Meta final</span>
                        <h3>Evaluación final</h3>
                        <p>Compara tu resultado con el diagnóstico inicial.</p>
                    </div>
                </div>
            </li>
        </ol>

        <?php if (arcade_ready()):
            $ag = arcade_games_with_progress($user);
            $agOpen = count(array_filter($ag, function ($g) { return $g['unlocked']; }));
        ?>
        <a class="arcade-teaser" href="<?= e(url('arcade.php')) ?>">
            <?= mascot_img('fiesta', 'at-mascot') ?>
            <div>
                <span class="eyebrow">Nuevo · Zona de refuerzo</span>
                <h3>Memoramas, puzles de claves, relacionar y el Simulador de oficina</h3>
                <p class="muted small"><?= $agOpen ?>/<?= count($ag) ?> juegos desbloqueados · videoteca con preguntas de repaso · <i class="bx bxs-trophy"></i> gran premio en la oficina</p>
            </div>
            <span class="btn btn-primary">Entrar <i class="bx bx-right-arrow-alt"></i></span>
        </a>
        <?php endif; ?>

        <div class="grid-2">
            <div class="card">
                <div class="card-head"><h3><i class="bx bxs-medal"></i> Últimas insignias</h3><a href="<?= e(url('profile.php#insignias')) ?>">Ver todas</a></div>
                <div class="badge-row">
                    <?php foreach ($badges as $b): ?>
                        <div class="badge-chip" style="--bc: <?= e($b['color']) ?>" title="<?= e($b['description']) ?>"><span><?= icon($b['icon']) ?></span><small><?= e($b['name']) ?></small></div>
                    <?php endforeach; ?>
                    <?php if (!$badges): ?><p class="muted">Aún no tienes insignias. ¡Completa tu primera misión!</p><?php endif; ?>
                </div>
            </div>
            <div class="card">
                <h3><i class="bx bx-history"></i> Actividad reciente</h3>
                <ul class="feed">
                    <?php foreach ($activity as $a): ?>
                        <li><span><?= e($a['reason']) ?></span><b><?= (int) $a['xp'] ? '+' . (int) $a['xp'] . ' XP' : '' ?> <?= (int) $a['coins'] ? '+' . (int) $a['coins'] . ' <i class="bx bxs-coin"></i>' : '' ?></b></li>
                    <?php endforeach; ?>
                    <?php if (!$activity): ?><li class="muted">Tu actividad aparecerá aquí.</li><?php endif; ?>
                </ul>
            </div>
        </div>
    </section>
</div>
<?php page_footer();
