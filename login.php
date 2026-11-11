<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = null;
$username = '';

if (is_post()) {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = db_one('SELECT * FROM users WHERE username = ? OR (email IS NOT NULL AND email = ?)', [$username, $username]);
    if ($user && password_verify($password, $user['password_hash']) && isset($user['active']) && !(int) $user['active']) {
        $error = 'Tu cuenta está deshabilitada. Comunícate con el administrador de PhishHunter.';
    } elseif ($user && password_verify($password, $user['password_hash'])) {
        login_user($user);
        if ($user['role'] === 'student') {
            touch_streak((int) $user['id']);
        }
        flash('success', '¡Hola de nuevo, ' . explode(' ', $user['full_name'])[0] . '!');
        redirect(in_array($user['role'], ['admin', 'superadmin'], true) ? 'admin/index.php' : 'dashboard.php');
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}

page_header('Ingresar', '', ['bare' => true, 'body_class' => 'auth-body']);
?>
<div class="auth-card">
    <a href="<?= e(url('index.php')) ?>"><img class="auth-logo" src="<?= e(url('assets/img/logo.png')) ?>" alt="PhishHunter"></a>
    <h1>¡Bienvenido, cazador!</h1>
    <p class="muted">Ingresa para continuar tus misiones.</p>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Usuario o correo
            <input type="text" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username">
        </label>
        <label>Contraseña
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn btn-primary btn-block" type="submit">Ingresar</button>
    </form>
    <p class="center muted small">¿Eres estudiante y no tienes cuenta? Tu docente te dará tu usuario y contraseña.</p>
    <a class="teacher-cta" href="<?= e(url('teacher_request.php')) ?>">
        <?= icon('bxs-user-badge', 'bx-tada-hover') ?>
        <span><b>Soy docente nuevo</b><small>Solicita tu cuenta para aplicar el juego con tus estudiantes</small></span>
        <?= icon('bx-right-arrow-alt') ?>
    </a>
</div>
<?php page_footer(['bare' => true]);
