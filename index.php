<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$missions = db_all('SELECT * FROM missions ORDER BY order_num');
$top = leaderboard('global', null, 5);
$players = (int) db_val("SELECT COUNT(*) FROM users WHERE role='student'");
$gamesCount = (int) db_val('SELECT COUNT(*) FROM games');
$casesCount = (int) db_val('SELECT COUNT(*) FROM game_items');

page_header('Inicio');
?>
<section class="hero">
    <div class="hero-text">
        <span class="eyebrow">Juego educativo de ciberseguridad</span>
        <h1>Conviértete en un <span class="hl">cazador de phishing</span></h1>
        <p class="lead">Supera misiones, detecta anzuelos en correos, SMS y chats, gana insignias y demuestra que nadie te pesca. Todo es simulado: aquí puedes equivocarte sin riesgo.</p>
        <div class="hero-cta">
            <a class="btn btn-primary btn-lg" href="<?= e(url('login.php')) ?>"><i class="bx bxs-joystick"></i> Ingresar y jugar</a>
        </div>
        <div class="hero-stats">
            <div><b><?= count($missions) ?></b><span>misiones</span></div>
            <div><b><?= $gamesCount ?></b><span>minijuegos</span></div>
            <div><b><?= $casesCount ?>+</b><span>casos reales</span></div>
            <div><b><?= $players ?></b><span>jugadores</span></div>
        </div>
    </div>
    <div class="hero-art" aria-hidden="true">
        <img src="<?= e(url('assets/img/logo.png')) ?>" alt="">
        <div class="float-card fc-1"><i class="bx bxs-envelope"></i> <span>¿Ganaste un iPhone?</span> <b class="tag-bad">Phishing</b></div>
        <div class="float-card fc-2"><i class="bx bx-link"></i> <span>viabcp.com.pe-alerta.info</span> <b class="tag-bad">Sospechoso</b></div>
        <div class="float-card fc-3"><i class="bx bxs-lock"></i> <span>Verificación en 2 pasos</span> <b class="tag-good">Seguro</b></div>
    </div>
</section>

<section class="steps">
    <div class="step"><span class="step-n">1</span><h3><i class="bx bxs-book-open"></i> Aprende</h3><p>Cada misión empieza con tarjetas cortas que explican una habilidad concreta.</p></div>
    <div class="step"><span class="step-n">2</span><h3><i class="bx bxs-joystick"></i> Juega</h3><p>Minijuegos con casos simulados: clasifica mensajes, caza señales, inspecciona enlaces y responde chats trampa.</p></div>
    <div class="step"><span class="step-n">3</span><h3><i class="bx bxs-check-circle"></i> Demuestra</h3><p>Mini-pruebas al final de cada misión y una evaluación antes y después para medir cuánto avanzaste.</p></div>
</section>

<div class="grid-landing">
    <section class="card">
        <h2><i class="bx bxs-map-alt"></i> Tu ruta de misiones</h2>
        <ol class="mission-preview">
            <?php foreach ($missions as $m): ?>
                <li style="--mc: <?= e($m['color']) ?>">
                    <span class="mp-icon"><?= icon($m['icon']) ?></span>
                    <div><b><?= e($m['title']) ?></b><small><?= e($m['subtitle']) ?></small></div>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
    <section class="card">
        <h2><i class="bx bxs-trophy"></i> Mejores cazadores</h2>
        <ol class="mini-board">
            <?php foreach ($top as $i => $p): ?>
                <li>
                    <span class="rank rank-<?= $i + 1 ?>"><?= $i + 1 ?></span>
                    <?= avatar_html($p, 'sm') ?>
                    <span class="mb-name"><?= e($p['username']) ?><small><?= (int) $p['grade'] ?>.º <?= e($p['section']) ?></small></span>
                    <b><?= number_format((int) $p['score']) ?> XP</b>
                </li>
            <?php endforeach; ?>
            <?php if (!$top): ?><li class="muted">¡Sé el primero en la tabla!</li><?php endif; ?>
        </ol>
        <a class="btn btn-soft btn-block" href="<?= e(url('login.php')) ?>">Ingresar para aparecer aquí</a>
    </section>
</div>
<?php page_footer();
