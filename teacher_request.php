<?php
/**
 * «Soy docente nuevo»: solicitud pública de cuenta de docente.
 * La recibe el súper admin en su bandeja y responde con credenciales temporales.
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$sent = false;
$f = ['name' => '', 'email' => '', 'phone' => '', 'institution' => '', 'place' => '', 'body' => ''];
$grades = [];

if (is_post()) {
    csrf_check();
    foreach ($f as $k => $v) $f[$k] = trim((string) ($_POST[$k] ?? ''));
    $grades = array_values(array_intersect(ALL_GRADES, array_map('intval', (array) ($_POST['grades'] ?? []))));
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

    if (($_POST['website'] ?? '') !== '') {
        $sent = true; // trampa para robots: se ignora en silencio
    } else {
        if (mb_strlen($f['name']) < 5) $errors[] = 'Escribe tus nombres y apellidos.';
        if ($f['email'] === '' && $f['phone'] === '') $errors[] = 'Deja al menos un correo o un celular para responderte.';
        if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo no es válido.';
        if ($f['phone'] !== '' && !preg_match('/^[0-9 +()-]{6,20}$/', $f['phone'])) $errors[] = 'El celular solo puede tener números, espacios o +.';
        if (mb_strlen($f['institution']) < 3) $errors[] = 'Escribe el nombre de tu institución educativa.';
        if (!$grades) $errors[] = 'Marca los grados con los que trabajarías.';
        $recent = (int) db_val('SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)', [$ip]);
        if ($recent >= 3) $errors[] = 'Ya recibimos varias solicitudes desde este equipo. Intenta más tarde.';

        if (!$errors) {
            $inst = mb_substr($f['institution'] . ($f['place'] !== '' ? ' — ' . $f['place'] : ''), 0, 150);
            db_exec('INSERT INTO messages (kind, name, email, phone, institution, grades, subject, body, ip) VALUES (?,?,?,?,?,?,?,?,?)', [
                'solicitud', mb_substr($f['name'], 0, 120), $f['email'] ?: null, $f['phone'] ?: null, $inst,
                grades_to_csv($grades), 'Solicitud de cuenta de docente', mb_substr($f['body'], 0, 2000) ?: '(sin mensaje)', $ip,
            ]);
            $sent = true;
        }
    }
}

page_header('Soy docente nuevo', '', ['bare' => true, 'body_class' => 'auth-body']);
?>
<div class="auth-card wide">
    <a href="<?= e(url('index.php')) ?>"><img class="auth-logo" src="<?= e(url('assets/img/logo.png')) ?>" alt="PhishHunter"></a>
    <?php if ($sent): ?>
        <?= mascot_say('¡Solicitud enviada! El administrador revisará tus datos y te enviará tu usuario y una contraseña temporal por correo o WhatsApp.', 'fiesta') ?>
        <a class="btn btn-primary btn-block" href="<?= e(url('login.php')) ?>"><?= icon('bx-left-arrow-alt') ?> Volver al inicio de sesión</a>
    <?php else: ?>
        <h1><?= icon('bxs-user-badge') ?> Soy docente nuevo</h1>
        <p class="muted">Déjanos tus datos y te crearemos una cuenta de docente para que apliques PhishHunter con tus estudiantes (grupos experimental o control, de 1.º a 5.º de secundaria).</p>

        <?php if ($errors): ?>
            <div class="alert alert-error"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
        <?php endif; ?>

        <form method="post" class="form" novalidate>
            <?= csrf_field() ?>
            <div class="hp-field" aria-hidden="true"><label>Sitio web<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <label>Nombres y apellidos
                <input type="text" name="name" value="<?= e($f['name']) ?>" required maxlength="120" autocomplete="name">
            </label>
            <div class="grid-2">
                <label>Correo
                    <input type="email" name="email" value="<?= e($f['email']) ?>" maxlength="150" autocomplete="email" placeholder="nombre@correo.com">
                </label>
                <label>Celular / WhatsApp
                    <input type="tel" name="phone" value="<?= e($f['phone']) ?>" maxlength="20" autocomplete="tel" placeholder="9XX XXX XXX">
                </label>
            </div>
            <div class="grid-2">
                <label>Institución educativa
                    <input type="text" name="institution" value="<?= e($f['institution']) ?>" required maxlength="120" placeholder="I.E. …">
                </label>
                <label>Distrito / provincia <span class="muted">(opcional)</span>
                    <input type="text" name="place" value="<?= e($f['place']) ?>" maxlength="60">
                </label>
            </div>
            <div class="field-label">Grados con los que trabajarías</div>
            <div class="grade-checks">
                <?php foreach (ALL_GRADES as $g): ?>
                    <label class="grade-check"><input type="checkbox" name="grades[]" value="<?= $g ?>" <?= in_array($g, $grades, true) ? 'checked' : '' ?>><span><?= $g ?>.º</span></label>
                <?php endforeach; ?>
            </div>
            <label>Mensaje <span class="muted">(opcional: cuéntanos cómo usarás el juego)</span>
                <textarea name="body" rows="4" maxlength="2000"><?= e($f['body']) ?></textarea>
            </label>
            <p class="muted small"><?= icon('bxs-lock-alt') ?> Nunca te pediremos contraseñas por correo ni por WhatsApp. Tus datos solo se usan para responder esta solicitud.</p>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('bxs-send') ?> Enviar solicitud</button>
        </form>
        <p class="center"><a href="<?= e(url('login.php')) ?>"><?= icon('bx-left-arrow-alt') ?> Ya tengo cuenta</a></p>
    <?php endif; ?>
</div>
<?php page_footer(['bare' => true]);
