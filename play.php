<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$game = db_one('SELECT g.*, m.slug AS mission_slug, m.title AS mission_title, m.color, m.order_num AS mission_order
                  FROM games g JOIN missions m ON m.id = g.mission_id WHERE g.slug = ?', [$_GET['g'] ?? '']);
if (!$game) {
    flash('error', 'Ese juego no existe.');
    redirect('dashboard.php');
}
if (!mission_is_unlocked($user, (int) $game['mission_id'])) {
    flash('info', 'Esta misión todavía está bloqueada.');
    redirect('dashboard.php');
}
if ($game['is_quiz'] && !is_staff($user)) {
    $pending = (int) db_val('SELECT COUNT(*) FROM games g LEFT JOIN user_game_progress p ON p.game_id=g.id AND p.user_id=?
                              WHERE g.mission_id=? AND g.is_quiz=0 AND p.passed_at IS NULL', [$user['id'], $game['mission_id']]);
    if ($pending > 0) {
        flash('info', 'Aprueba primero los minijuegos de práctica de esta misión.');
        redirect('mission.php?m=' . urlencode($game['mission_slug']));
    }
}

$meta = [
    'id' => (int) $game['id'], 'slug' => $game['slug'], 'type' => $game['type'], 'title' => $game['title'],
    'instructions' => $game['instructions'], 'icon' => $game['icon'], 'pass' => (int) $game['pass_percent'],
    'perPlay' => (int) $game['items_per_play'], 'isQuiz' => (bool) $game['is_quiz'],
    'missionUrl' => url('mission.php?m=' . urlencode($game['mission_slug'])),
];

page_header($game['title'], 'dashboard', ['body_class' => 'play-body']);
?>
<div class="play" id="play" style="--mc: <?= e($game['color']) ?>">
    <div class="play-top">
        <a class="back" href="<?= e($meta['missionUrl']) ?>"><i class="bx bx-left-arrow-alt"></i> <?= e($game['mission_title']) ?></a>
        <div class="play-progress">
            <div class="bar bar-game"><span id="pp-bar" style="width:0%"></span></div>
            <span id="pp-text">0/<?= (int) $game['items_per_play'] ?></span>
        </div>
        <div class="play-score"><i class="bx bxs-star"></i> <b id="pp-score">0</b> pts</div>
    </div>

    <section class="play-intro card" id="intro">
        <div class="pi-icon"><?= icon($game['icon']) ?></div>
        <span class="eyebrow"><?= $game['is_quiz'] ? 'Mini-prueba de aprendizaje' : e(GAME_TYPES[$game['type']]) ?></span>
        <h1><?= e($game['title']) ?></h1>
        <p class="lead"><?= e($game['instructions']) ?></p>
        <ul class="pi-rules">
            <li><i class="bx bxs-extension"></i> <?= (int) $game['items_per_play'] ?> casos en esta partida</li>
            <li><i class="bx bxs-check-circle"></i> Apruebas con <?= (int) $game['pass_percent'] ?>% (<i class="bx bxs-star"></i>) · <i class="bx bxs-star"></i><i class="bx bxs-star"></i> desde 75% · <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i> desde 90%</li>
            <li><i class="bx bxs-hourglass"></i> Sin límite de tiempo: revisa con calma</li>
            <li><i class="bx bxs-message-rounded-dots"></i> Después de cada respuesta verás por qué</li>
        </ul>
        <button class="btn btn-primary btn-lg" id="start-btn" type="button">¡Empezar!</button>
    </section>

    <section id="stage" class="stage" hidden></section>
    <section id="feedback" class="feedback" hidden></section>
    <section id="result" class="result card" hidden></section>
</div>
<script>window.PH_GAME = <?= json_encode($meta, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php page_footer(['scripts' => ['js/games.js']]);
