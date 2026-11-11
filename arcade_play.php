<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
if (!arcade_ready()) redirect('arcade.php');

$game = arcade_game_for($user, (string) ($_GET['g'] ?? ''));
if (!$game) {
    flash('error', 'Ese juego no existe.');
    redirect('arcade.php');
}
if (!$game['unlocked']) {
    flash('info', 'Completa la misión ' . (int) $game['unlock_mission'] . ' para desbloquear este juego.');
    redirect('arcade.php');
}

$meta = [
    'slug' => $game['slug'], 'type' => $game['type'], 'title' => $game['title'], 'pass' => (int) $game['pass_percent'],
    'perPlay' => (int) $game['items_per_play'], 'backUrl' => url('arcade.php'),
    'mascot' => url('assets/img/mascot/chispa-'),
];

$intro = [
    'memory'  => ['<i class="bx bxs-copy-alt"></i> Voltea 2 cartas por turno', '<i class="bx bxs-brain"></i> Recuerda dónde estaba cada una', '<i class="bx bxs-bulb"></i> Cada pareja trae un dato para aprender'],
    'match'   => ['<i class="bx bx-pointer"></i> Toca un elemento de la izquierda', '<i class="bx bx-pointer"></i> Luego toca su pareja de la derecha', '<i class="bx bxs-check-circle"></i> Pulsa «Comprobar» cuando unas todo'],
    'builder' => ['<i class="bx bxs-extension"></i> Toca o arrastra cartas a la barra de la clave', '<i class="bx bx-block"></i> Evita las cartas con datos personales o claves débiles', '<i class="bx bxs-lock"></i> Meta: 3 palabras + número + signo + 14 caracteres'],
    'office'  => ['<i class="bx bxs-file"></i> Completa los documentos con la nota de tu jefa', '<i class="bx bxs-inbox"></i> Decide qué hacer con cada correo, chat o llamada', '<i class="bx bxs-trophy"></i> 85 % o más sin caer en engaños = GRAN PREMIO'],
][$game['type']];

$tips = [
    'memory'  => 'Tip: empieza volteando cartas de las esquinas y ve memorizando por zonas.',
    'match'   => 'Tip: si dudas, empieza por las parejas que tengas más claras.',
    'builder' => '¡Ojo! Algunas cartas parecen inocentes, pero son datos que cualquiera puede encontrar en tus redes.',
    'office'  => 'En una oficina real los engaños llegan mientras estás ocupado(a). Tómate tu tiempo: aquí no hay reloj.',
][$game['type']];

page_header($game['title'], 'arcade', ['body_class' => 'play-body']);
?>
<div class="play arcade-play <?= $game['type'] === 'office' ? 'play-wide' : '' ?>" id="play" style="--mc: <?= e($game['color']) ?>">
    <div class="play-top">
        <a class="back" href="<?= e(url('arcade.php')) ?>"><i class="bx bx-left-arrow-alt"></i> Zona de refuerzo</a>
        <div class="play-progress">
            <div class="bar bar-game"><span id="pp-bar" style="width:0%"></span></div>
            <span id="pp-text"></span>
        </div>
        <div class="play-score" id="pp-extra"></div>
    </div>

    <section class="play-intro card" id="intro">
        <div class="intro-with-mascot">
            <?= mascot_img($game['type'] === 'office' ? 'alerta' : 'hola', 'intro-mascot') ?>
            <div>
                <div class="pi-icon"><?= icon($game['icon']) ?></div>
                <span class="eyebrow"><?= e(ARCADE_TYPES[$game['type']]) ?> · Zona de refuerzo</span>
                <h1><?= e($game['title']) ?></h1>
                <p class="lead"><?= e($game['instructions']) ?></p>
            </div>
        </div>
        <ul class="pi-rules">
            <?php foreach ($intro as $li): ?><li><?= $li /* HTML propio con iconos */ ?></li><?php endforeach; ?>
            <li><i class="bx bxs-check-circle"></i> Apruebas con <?= (int) $game['pass_percent'] ?>% · <i class="bx bxs-star"></i><i class="bx bxs-star"></i> desde 75% · <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i> desde 90%</li>
        </ul>
        <p class="tip"><i class="bx bxs-bulb"></i> <?= e($tips) ?></p>
        <button class="btn btn-primary btn-lg" id="start-btn" type="button">¡Empezar!</button>
    </section>

    <section id="stage" class="stage arcade-stage" hidden></section>
    <section id="result" class="result card" hidden></section>
</div>
<script>window.PH_ARCADE = <?= json_encode($meta, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php page_footer(['scripts' => ['js/arcade.js']]);
