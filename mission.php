<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$uid  = (int) $user['id'];

$mission = db_one('SELECT * FROM missions WHERE slug = ?', [$_GET['m'] ?? '']);
if (!$mission) {
    flash('error', 'Esa misión no existe.');
    redirect('dashboard.php');
}
if (!mission_is_unlocked($user, (int) $mission['id'])) {
    flash('info', has_assessment($uid, 'pretest') ? 'Completa la misión anterior para desbloquear esta.' : 'Primero completa tu evaluación diagnóstica.');
    redirect(has_assessment($uid, 'pretest') ? 'dashboard.php' : 'evaluation.php?type=pretest');
}

$objectives = json_decode($mission['objectives'], true);
$lesson     = json_decode($mission['lesson'], true);
$games = db_all('SELECT g.*, COALESCE(p.best_percent,0) AS best, COALESCE(p.best_stars,0) AS stars, COALESCE(p.plays,0) AS plays, p.passed_at,
                        (SELECT COUNT(*) FROM game_items gi WHERE gi.game_id=g.id) AS pool
                   FROM games g LEFT JOIN user_game_progress p ON p.game_id=g.id AND p.user_id=?
                  WHERE g.mission_id=? ORDER BY g.order_num', [$uid, $mission['id']]);
$practicePassed = true;
foreach ($games as $g) {
    if (!$g['is_quiz'] && !$g['passed_at']) $practicePassed = false;
}
$completed = (bool) db_val('SELECT 1 FROM user_missions WHERE user_id=? AND mission_id=?', [$uid, $mission['id']]);
$videos    = videos_for($uid, $mission['slug']);
$unlockedGame = null;
if (arcade_ready()) {
    $unlockedGame = db_one('SELECT slug, title, icon FROM arcade_games WHERE unlock_mission = ? ORDER BY order_num LIMIT 1', [$mission['order_num']]);
}

page_header($mission['title'], 'dashboard');
?>
<a class="back" href="<?= e(url('dashboard.php')) ?>"><i class="bx bx-left-arrow-alt"></i> Volver a la ruta</a>

<section class="mission-hero" style="--mc: <?= e($mission['color']) ?>">
    <div class="mh-icon"><?= icon($mission['icon']) ?></div>
    <div class="mh-text">
        <span class="eyebrow">Misión <?= (int) $mission['order_num'] ?> · <?= e(dimension_label($mission['dimension'])) ?></span>
        <h1><?= e($mission['title']) ?></h1>
        <p><?= e($mission['description']) ?></p>
        <div class="mh-reward">Recompensa al completar: <b>+<?= (int) $mission['xp_reward'] ?> XP</b> · <b>+<?= (int) $mission['coin_reward'] ?> <i class="bx bxs-coin"></i></b> · insignia de misión<?= $completed ? ' <span class="tag-good"><i class="bx bx-check"></i> Completada</span>' : '' ?></div>
    </div>
</section>

<div class="mission-grid">
    <section class="card lesson" id="lesson">
        <div class="card-head">
            <h2><i class="bx bxs-book-open"></i> Aprende</h2>
            <span class="muted small"><span id="lesson-pos">1</span>/<?= count($lesson) ?></span>
        </div>
        <div class="lesson-track">
            <?php foreach ($lesson as $i => $card): ?>
                <article class="lesson-card <?= $i === 0 ? 'active' : '' ?>">
                    <div class="lc-icon"><?= icon($card['icon']) ?></div>
                    <h3><?= e($card['title']) ?></h3>
                    <p><?= e($card['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="lesson-nav">
            <button class="btn btn-soft" type="button" data-lesson="-1"><i class="bx bx-left-arrow-alt"></i> Anterior</button>
            <div class="dots"><?php foreach ($lesson as $i => $c): ?><i class="<?= $i === 0 ? 'on' : '' ?>"></i><?php endforeach; ?></div>
            <button class="btn btn-primary" type="button" data-lesson="1">Siguiente <i class="bx bx-right-arrow-alt"></i></button>
        </div>

        <h3 class="mt"><i class="bx bxs-bullseye"></i> Al terminar podrás…</h3>
        <ul class="checklist">
            <?php foreach ($objectives as $o): ?><li><?= e($o) ?></li><?php endforeach; ?>
        </ul>
    </section>

    <section class="games-col">
        <?= mascot_say($completed ? '¡Misión completada! Puedes volver a jugar para conseguir las 3 estrellas.' . ($unlockedGame ? ' Además desbloqueaste <b>' . e($unlockedGame['title']) . '</b> en la Zona de refuerzo.' : '') : ($practicePassed ? '¡Ya aprobaste la práctica! Ahora toca la mini-prueba. Tú puedes. <i class="bx bxs-zap"></i>' : 'Primero mira las tarjetas y los videos de «Aprende». Luego juega: no hay reloj, analiza con calma.'), $completed ? 'fiesta' : 'hola', 'mission-chispa') ?>
        <h2><i class="bx bxs-joystick"></i> Minijuegos</h2>
        <?php foreach ($games as $g):
            $locked = $g['is_quiz'] && !$practicePassed && !is_staff($user);
        ?>
            <div class="game-card <?= $locked ? 'locked' : '' ?> <?= $g['passed_at'] ? 'passed' : '' ?>">
                <div class="gc-icon"><?= icon($g['icon']) ?></div>
                <div class="gc-body">
                    <span class="gc-type"><?= $g['is_quiz'] ? '<i class="bx bxs-edit"></i> Mini-prueba de aprendizaje' : e(GAME_TYPES[$g['type']]) ?></span>
                    <h3><?= e($g['title']) ?></h3>
                    <p><?= e($g['description']) ?></p>
                    <div class="gc-meta">
                        <?= stars_html((int) $g['stars']) ?>
                        <?php if ($g['plays']): ?><span class="muted small">Mejor: <?= (int) $g['best'] ?>% · <?= (int) $g['plays'] ?> <?= (int) $g['plays'] === 1 ? 'partida' : 'partidas' ?></span><?php endif; ?>
                        <span class="muted small"><?= (int) $g['items_per_play'] ?> casos · aprueba con <?= (int) $g['pass_percent'] ?>%</span>
                    </div>
                </div>
                <?php if ($locked): ?>
                    <span class="btn btn-disabled" title="Aprueba los otros minijuegos de la misión"><i class="bx bxs-lock-alt"></i> Bloqueado</span>
                <?php else: ?>
                    <a class="btn <?= $g['passed_at'] ? 'btn-soft' : 'btn-primary' ?>" href="<?= e(url('play.php?g=' . urlencode($g['slug']))) ?>"><?= $g['plays'] ? 'Jugar otra vez' : '¡Jugar!' ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <p class="tip"><i class="bx bxs-bulb"></i> Los puntos premian el análisis correcto, no la rapidez. Tómate tu tiempo: en la vida real, detenerse a revisar es lo que te protege.</p>
    </section>
</div>
<?php if ($videos): ?>
<section class="card mission-videos" id="videos">
    <div class="card-head">
        <h2><i class="bx bxs-movie-play"></i> Mira y aprende</h2>
        <a href="<?= e(url('videos.php')) ?>">Toda la videoteca <i class="bx bx-right-arrow-alt"></i></a>
    </div>
    <p class="muted small">Videos cortos para reforzar esta misión. Al verlos ganas +10 XP y, si respondes bien la pregunta de repaso, +15 XP más.</p>
    <div class="video-grid">
        <?php foreach ($videos as $v) echo video_card_html($v); ?>
    </div>
</section>
<?php endif; ?>
<?php if ($completed && $unlockedGame): ?>
<a class="unlock-strip" href="<?= e(url('arcade_play.php?g=' . urlencode($unlockedGame['slug']))) ?>">
    <span><?= icon($unlockedGame['icon']) ?></span><div><b>¡Juego de refuerzo desbloqueado!</b><small><?= e($unlockedGame['title']) ?> te espera en la Zona de refuerzo.</small></div><span class="btn btn-primary btn-sm">Jugar <i class="bx bx-right-arrow-alt"></i></span>
</a>
<?php endif; ?>
<?php page_footer(['scripts' => ['js/videos.js']]);
