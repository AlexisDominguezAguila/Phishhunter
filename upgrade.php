<?php
/**
 * PhishHunter · Actualizador web (no borra datos)
 * Abre http://localhost/game/upgrade.php como docente.
 *  - «Actualizar contenido»: videoteca y Zona de refuerzo (conserva estudiantes y progreso).
 *  - «Guardar iconos en el servidor»: descarga Boxicons para usarlos sin internet.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/database/upgrade.php';

$user = db_val("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'teacher_id'") ? require_super() : require_admin();
$result = null;
$icons = null;
$error = null;

if (is_post()) {
    csrf_check();
    try {
        if (($_POST['action'] ?? '') === 'icons') {
            $icons = boxicons_download(BOXICONS_VERSION);
        } else {
            $result = phishhunter_upgrade();
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
$iconsLocal = is_file(APP_ROOT . '/assets/vendor/boxicons/css/boxicons.min.css');

page_header('Actualizar', 'admin');
?>
<div class="card upgrade-card">
    <?= mascot_say($result ? '¡Listo! Ya puedes abrir la <b>Zona de refuerzo</b> y la <b>Videoteca</b>.' : ($icons ? '¡Iconos guardados! Ahora se ven aunque no haya internet.' : 'Aquí actualizas el contenido del juego. <b>No se borra</b> ningún estudiante, progreso ni evaluación.'), $result || $icons ? 'fiesta' : 'hola') ?>
    <h1><?= icon('bx-refresh') ?> Actualizar PhishHunter</h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><b>No se pudo completar:</b> <?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($result): ?>
        <div class="alert alert-success">
            <?= icon('bx-check') ?> Videoteca: <b><?= (int) $result['videos'] ?></b> videos<br>
            <?= icon('bx-check') ?> Zona de refuerzo: <b><?= (int) $result['games'] ?></b> juegos con <b><?= (int) $result['items'] ?></b> casos<br>
            <?= icon('bx-check') ?> Insignias y premios exclusivos del Simulador de oficina
        </div>
    <?php endif; ?>
    <?php if ($icons): ?>
        <div class="alert alert-success"><?php foreach ($icons as $f): ?><div><?= icon('bx-check') ?> <?= e($f) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <h2 class="mt"><?= icon('bxs-joystick-alt') ?> Contenido de la Zona de refuerzo</h2>
    <ul class="checklist">
        <li>Videoteca con videos de YouTube por misión y pregunta de repaso</li>
        <li>Memorama del cazador, Memorama de claves, Relaciona y protege</li>
        <li>Arma tu clave (puzle de cartas) y Simulador de oficina con gran premio</li>
    </ul>
    <p class="muted small">Repite esta actualización cuando edites <code>database/arcade_content.php</code>: el contenido se recarga y el progreso se conserva.</p>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="content">
        <button class="btn btn-primary" type="submit"><?= icon('bx-refresh') ?> Actualizar contenido</button>
    </form>

    <h2 class="mt"><?= icon('bxs-cloud-download') ?> Iconos sin internet</h2>
    <p>Los iconos animados usan la biblioteca libre <b>Boxicons</b>.
        <?= $iconsLocal ? '<span class="tag-good">' . icon('bx-check') . ' Ya están guardados en el servidor</span>' : 'Ahora se cargan desde internet. Guárdalos en el servidor para que funcionen aunque el aula no tenga conexión.' ?></p>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="icons">
        <button class="btn btn-soft" type="submit"><?= icon('bxs-download') ?> <?= $iconsLocal ? 'Volver a descargar iconos' : 'Guardar iconos en el servidor' ?></button>
    </form>

    <div class="res-actions">
        <a class="btn btn-soft" href="<?= e(url('arcade.php')) ?>"><?= icon('bxs-joystick-alt') ?> Zona de refuerzo</a>
        <a class="btn btn-soft" href="<?= e(url('admin/index.php')) ?>"><?= icon('bxs-bar-chart-alt-2') ?> Panel docente</a>
    </div>
</div>
<?php page_footer();
