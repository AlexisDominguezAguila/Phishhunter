<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$uid  = (int) $user['id'];
if (!can_play($user)) {
    flash('info', 'Tu grupo aún no tiene acceso a los juegos. Primero completa tus evaluaciones.');
    redirect('dashboard.php');
}

page_header('Zona de refuerzo', 'arcade');

if (!arcade_ready()): ?>
    <div class="card center">
        <?= mascot_say('La Zona de refuerzo todavía no está instalada en este equipo.' . (is_super($user) ? ' Pulsa el botón para instalarla sin borrar datos.' : ' Pídele a tu docente que la active.'), 'piensa') ?>
        <?php if (is_super($user)): ?><a class="btn btn-primary" href="<?= e(url('upgrade.php')) ?>">Instalar ahora</a><?php endif; ?>
    </div>
<?php page_footer(); return; endif;

$games  = arcade_games_with_progress($user);
$videos = videos_for($uid);
$watched = count(array_filter($videos, function ($v) { return $v['watched_at']; }));
$missionNames = array_column(db_all('SELECT order_num, title FROM missions'), 'title', 'order_num');
$office = null;
foreach ($games as $g) if ($g['type'] === 'office') $office = $g;
?>
<section class="arcade-hero">
    <?= mascot_img('fiesta', 'ah-mascot') ?>
    <div>
        <span class="eyebrow">Zona de refuerzo</span>
        <h1>¡Entrena tu instinto anti-phishing!</h1>
        <p>Minijuegos extra y videos para repasar lo que aprendiste en las misiones. Ganas XP, monedas e insignias, y no cambian tu ruta de misiones. Cada juego se desbloquea al completar una misión.</p>
        <div class="ah-stats">
            <span class="chip"><i class="bx bxs-joystick-alt"></i> <b><?= count(array_filter($games, function ($g) { return $g['passed_at']; })) ?>/<?= count($games) ?></b> juegos superados</span>
            <span class="chip"><i class="bx bxs-movie-play"></i> <b><?= $watched ?>/<?= count($videos) ?></b> videos vistos</span>
            <?php if ($office && $office['grand_prize_at']): ?><span class="chip chip-gold"><i class="bx bxs-trophy"></i> Gran premio conseguido</span><?php endif; ?>
        </div>
    </div>
</section>

<div class="arcade-grid">
    <?php foreach ($games as $g): $locked = !$g['unlocked']; ?>
        <article class="arcade-card <?= $locked ? 'locked' : '' ?> <?= $g['passed_at'] ? 'passed' : '' ?> <?= $g['type'] === 'office' ? 'is-office' : '' ?>" style="--gc: <?= e($g['color']) ?>">
            <div class="ac-top">
                <span class="ac-icon"><?= $locked ? '<i class="bx bxs-lock-alt"></i>' : icon($g['icon']) ?></span>
                <span class="gc-type"><?= e(ARCADE_TYPES[$g['type']]) ?></span>
            </div>
            <h3><?= e($g['title']) ?></h3>
            <p><?= e($g['description']) ?></p>
            <?php if ($g['type'] === 'office'): ?>
                <div class="grand-box"><i class="bx bxs-trophy"></i> <b>Gran premio:</b> +300 XP · +150 <i class="bx bxs-coin"></i> · avatar, marco y título exclusivos · insignia «Empleado del año»</div>
            <?php endif; ?>
            <div class="gc-meta">
                <?= stars_html((int) $g['stars']) ?>
                <?php if ($g['plays']): ?><span class="muted small">Mejor: <?= (int) $g['best'] ?>%</span><?php endif; ?>
            </div>
            <?php if ($locked): ?>
                <span class="btn btn-disabled"><i class="bx bxs-lock-alt"></i> Completa la misión <?= (int) $g['unlock_mission'] ?><?= isset($missionNames[$g['unlock_mission']]) ? ': ' . e($missionNames[$g['unlock_mission']]) : '' ?></span>
            <?php else: ?>
                <a class="btn <?= $g['passed_at'] ? 'btn-soft' : 'btn-primary' ?>" href="<?= e(url('arcade_play.php?g=' . urlencode($g['slug']))) ?>"><?= $g['plays'] ? 'Jugar otra vez' : '¡Jugar!' ?></a>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>

    <a class="arcade-card video-card" href="<?= e(url('videos.php')) ?>" style="--gc: #E0457B">
        <div class="ac-top"><span class="ac-icon"><i class="bx bxs-movie-play"></i></span><span class="gc-type">Videoteca</span></div>
        <h3>Cine seguro</h3>
        <p>Videos cortos de INCIBE, OSI y otros expertos. Míralos, responde una pregunta y gana XP.</p>
        <?= progress_bar((int) round($watched / max(1, count($videos)) * 100)) ?>
        <span class="btn btn-soft">Abrir videoteca <i class="bx bx-right-arrow-alt"></i></span>
    </a>
</div>
<?php page_footer();
