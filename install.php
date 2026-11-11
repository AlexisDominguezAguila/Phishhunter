<?php
/**
 * PhishHunter · Instalador web
 * Abre http://localhost/game/install.php para crear la base de datos
 * db_phishhunter, sus tablas y cargar los seeders.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/database/seed.php';

$lock = __DIR__ . '/config/installed.lock';
$installed = is_file($lock);
$log = [];
$error = null;

// Si ya está instalado, solo un docente puede reinstalar (borra todos los datos).
$canInstall = !$installed;
if ($installed) {
    try {
        $u = current_user();
        // Solo el súper admin (o el docente único de una instalación antigua) puede reinstalar.
        $canInstall = $u && (is_super($u) || ($u['role'] === 'admin' && !db_val("SELECT 1 FROM users WHERE role = 'superadmin'")));
    } catch (Throwable $e) {
        $canInstall = false; // instalado pero sin conexión: revisar config/config.php (nunca reinstalar a ciegas)
    }
}

if (is_post() && $canInstall) {
    csrf_check();
    try {
        phishhunter_install(true, function ($m) use (&$log) { $log[] = $m; });
        file_put_contents($lock, date('c'));
        session_unset();
        $installed = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

page_header('Instalación', '', ['bare' => true, 'body_class' => 'auth-body']);
?>
<div class="auth-card wide">
    <img class="auth-logo" src="<?= e(url('assets/img/logo.png')) ?>" alt="PhishHunter">
    <h1>Instalación de PhishHunter</h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><b>No se pudo instalar:</b> <?= e($error) ?><br>
            Verifica que WAMP esté encendido (ícono verde) y que MySQL use el usuario <code><?= e(DB_USER) ?></code> sin contraseña.
            Puedes cambiar estos datos en <code>config/config.php</code>.</div>
    <?php endif; ?>

    <?php if ($log): ?>
        <div class="alert alert-success">
            <?php foreach ($log as $l): ?><div><?= e($l) ?></div><?php endforeach; ?>
        </div>
        <p>¡Listo! Cuentas disponibles:</p>
        <ul class="plain">
            <li><?= icon('bxs-user-badge') ?> Docente: <b>admin</b> / <b>admin123</b> (cámbiala en «Mi progreso»)</li>
            <li><?= icon('bxs-group') ?> Registra a tus estudiantes en <b>Panel docente  Estudiantes</b>.</li>
        </ul>
        <a class="btn btn-primary btn-block" href="<?= e(url('login.php')) ?>">Ir a iniciar sesión</a>
    <?php elseif ($canInstall): ?>
        <p>Se creará la base de datos <b><?= e(DB_NAME) ?></b> en <b><?= e(DB_HOST) ?></b> con el usuario <b><?= e(DB_USER) ?></b>, sus tablas y el contenido del juego: 6 misiones, 14 minijuegos, más de 120 casos, insignias, logros, recompensas y los cuestionarios de la tesis.</p>
        <?php if ($installed): ?>
            <div class="alert alert-warning"><i class="bx bxs-error"></i> El juego ya estaba instalado. Reinstalar <b>borrará a todos los estudiantes y su progreso</b>; solo quedará la cuenta del docente (admin / admin123).</div>
        <?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <button class="btn btn-primary btn-block" type="submit">Instalar ahora</button>
        </form>
        <p class="muted small">También puedes instalar por consola: <code>php database/seed.php</code></p>
    <?php else: ?>
        <div class="alert alert-info">El juego ya está instalado. Para reinstalar, inicia sesión como docente y vuelve a esta página.</div>
        <a class="btn btn-primary btn-block" href="<?= e(url('login.php')) ?>">Iniciar sesión</a>
    <?php endif; ?>
</div>
<?php page_footer(['bare' => true]);
