<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$uid  = (int) $user['id'];
if (!arcade_ready()) redirect('arcade.php');
if (!can_play($user)) {
    flash('info', 'Tu grupo aún no tiene acceso a los juegos. Primero completa tus evaluaciones.');
    redirect('dashboard.php');
}

$videos = videos_for($uid);
$missions = db_all('SELECT slug, order_num, title, icon FROM missions ORDER BY order_num');
$byMission = [];
foreach ($videos as $v) $byMission[$v['mission_slug'] ?? ''][] = $v;
$watched = count(array_filter($videos, function ($v) { return $v['watched_at']; }));
$solved  = count(array_filter($videos, function ($v) { return (int) $v['quiz_correct'] === 1; }));

page_header('Videoteca', 'arcade');
?>
<a class="back" href="<?= e(url('arcade.php')) ?>"><i class="bx bx-left-arrow-alt"></i> Zona de refuerzo</a>
<section class="arcade-hero video-hero">
    <?= mascot_img('feliz', 'ah-mascot') ?>
    <div>
        <span class="eyebrow">Videoteca · Cine seguro</span>
        <h1><i class="bx bxs-movie-play"></i> Mira, aprende y gana XP</h1>
        <p>Videos cortos de expertos en ciberseguridad. Al verlos ganas <b>+10 XP</b>; si respondes bien la pregunta de repaso a la primera, <b>+15 XP</b> más. ¡Completa todos para la insignia «Cinéfilo seguro»!</p>
        <div class="ah-stats">
            <span class="chip"><i class="bx bxs-show"></i> <b><?= $watched ?>/<?= count($videos) ?></b> vistos</span>
            <span class="chip"><i class="bx bxs-brain"></i> <b><?= $solved ?>/<?= count($videos) ?></b> preguntas acertadas</span>
        </div>
        <p class="muted small">Los videos se cargan desde YouTube: necesitas conexión a internet.</p>
    </div>
</section>

<?php foreach ($missions as $m): if (empty($byMission[$m['slug']])) continue; ?>
<section class="video-section">
    <h2><?= icon($m['icon']) ?> Misión <?= (int) $m['order_num'] ?>: <?= e($m['title']) ?></h2>
    <div class="video-grid">
        <?php foreach ($byMission[$m['slug']] as $v) echo video_card_html($v); ?>
    </div>
</section>
<?php endforeach; ?>
<?php if (!empty($byMission[''])): ?>
<section class="video-section">
    <h2><i class="bx bxs-star"></i> Extra</h2>
    <div class="video-grid"><?php foreach ($byMission[''] as $v) echo video_card_html($v); ?></div>
</section>
<?php endif; ?>
<?php page_footer(['scripts' => ['js/videos.js']]);
