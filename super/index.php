<?php
/**
 * Súper admin · Docentes
 * Crear, editar, habilitar/deshabilitar y restablecer la contraseña de los docentes.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$me = require_super();
$errors = [];
$f = ['full_name' => '', 'email' => '', 'phone' => '', 'institution' => '', 'username' => ''];
$fGrades = [3, 4, 5];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        foreach ($f as $k => $v) $f[$k] = trim((string) ($_POST[$k] ?? ''));
        $fGrades = array_values(array_intersect(ALL_GRADES, array_map('intval', (array) ($_POST['grades'] ?? []))));
        if (mb_strlen($f['full_name']) < 5) $errors[] = 'Escribe los nombres y apellidos del docente.';
        if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo no es válido.';
        if ($f['username'] !== '' && !preg_match('/^[a-zA-Z0-9._]{3,30}$/', $f['username'])) $errors[] = 'Usuario no válido (3 a 30: letras, números, punto o guion bajo).';
        if ($f['username'] !== '' && db_val('SELECT 1 FROM users WHERE username = ?', [$f['username']])) $errors[] = 'Ese usuario ya existe.';
        if (!$fGrades) $errors[] = 'Elige al menos un grado.';
        if (!$errors) {
            $cred = create_teacher($f + ['grades' => grades_to_csv($fGrades)]);
            $_SESSION['teacher_cred'] = $cred + ['email' => $f['email'], 'phone' => $f['phone']];
            flash('success', 'Cuenta de docente creada.');
            redirect('super/index.php#credenciales');
        }
    } else {
        $tid = (int) ($_POST['teacher_id'] ?? 0);
        $t = db_one("SELECT * FROM users WHERE id = ? AND role = 'admin'", [$tid]);
        if (!$t) {
            flash('error', 'Docente no encontrado.');
            redirect('super/index.php');
        }
        if ($action === 'toggle') {
            db_exec('UPDATE users SET active = ? WHERE id = ?', [(int) !$t['active'], $tid]);
            flash('success', $t['active'] ? 'Se deshabilitó la cuenta de ' . $t['full_name'] . '. Ya no podrá ingresar.' : 'Se habilitó la cuenta de ' . $t['full_name'] . '.');
        } elseif ($action === 'reset') {
            $temp = temp_password();
            db_exec('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?', [password_hash($temp, PASSWORD_DEFAULT), $tid]);
            $_SESSION['teacher_cred'] = ['id' => $tid, 'full_name' => $t['full_name'], 'username' => $t['username'], 'password' => $temp, 'email' => $t['email'], 'phone' => $t['phone']];
            flash('success', 'Nueva contraseña temporal generada.');
            redirect('super/index.php#credenciales');
        } elseif ($action === 'update') {
            $fn = trim((string) ($_POST['full_name'] ?? ''));
            $em = trim((string) ($_POST['email'] ?? ''));
            $ph = trim((string) ($_POST['phone'] ?? ''));
            $in = trim((string) ($_POST['institution'] ?? ''));
            $gr = grades_to_csv((array) ($_POST['grades'] ?? []));
            if (mb_strlen($fn) >= 5 && $gr && ($em === '' || filter_var($em, FILTER_VALIDATE_EMAIL))) {
                db_exec('UPDATE users SET full_name = ?, email = ?, phone = ?, institution = ?, grades = ? WHERE id = ?', [$fn, $em ?: null, $ph ?: null, $in ?: null, $gr, $tid]);
                flash('success', 'Datos de ' . $fn . ' actualizados.');
            } else {
                flash('error', 'Revisa el nombre, el correo y los grados.');
            }
        }
        redirect('super/index.php');
    }
}

$cred = $_SESSION['teacher_cred'] ?? null;
unset($_SESSION['teacher_cred']);

$teachers = db_all("SELECT t.*,
        (SELECT COUNT(*) FROM users s WHERE s.teacher_id = t.id AND s.role = 'student') AS students,
        (SELECT COUNT(*) FROM class_groups g WHERE g.teacher_id = t.id) AS groups_n,
        (SELECT COUNT(*) FROM class_groups g WHERE g.teacher_id = t.id AND g.type = 'control') AS control_n,
        (SELECT COUNT(*) FROM users s JOIN assessments a ON a.user_id = s.id AND a.type = 'postest' WHERE s.teacher_id = t.id) AS posts,
        (SELECT MAX(l.created_at) FROM xp_log l JOIN users s ON s.id = l.user_id WHERE s.teacher_id = t.id) AS last_activity
      FROM users t WHERE t.role IN ('admin','superadmin') ORDER BY t.role = 'superadmin' DESC, t.active DESC, t.full_name");
$kpi = [
    'teachers' => (int) db_val("SELECT COUNT(*) FROM users WHERE role = 'admin'"),
    'active'   => (int) db_val("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1"),
    'students' => (int) db_val("SELECT COUNT(*) FROM users WHERE role = 'student'"),
    'new'      => new_messages_count(),
];

page_header('Docentes', 'super');
?>
<div class="section-head">
    <div><h1><?= icon('bxs-user-badge') ?> Docentes</h1><p class="muted">Como súper admin creas y habilitas las cuentas de docentes. Cada docente gestiona a sus propios estudiantes y grupos.</p></div>
    <div class="head-actions">
        <a class="btn btn-soft" href="<?= e(url('super/inbox.php')) ?>"><?= icon('bxs-inbox') ?> Bandeja<?= $kpi['new'] ? ' <em class="nav-badge">' . $kpi['new'] . '</em>' : '' ?></a>
        <a class="btn btn-soft" href="<?= e(url('super/backup.php')) ?>"><?= icon('bxs-data') ?> Respaldo</a>
        <a class="btn btn-primary" href="#nuevo-docente"><?= icon('bxs-user-plus', 'bx-tada-hover') ?> Nuevo docente</a>
    </div>
</div>

<div class="kpis">
    <div class="kpi"><span><?= icon('bxs-user-badge') ?></span><b><?= $kpi['teachers'] ?></b><small>docentes</small></div>
    <div class="kpi"><span><?= icon('bxs-check-circle') ?></span><b><?= $kpi['active'] ?></b><small>habilitados</small></div>
    <div class="kpi"><span><?= icon('bxs-group') ?></span><b><?= $kpi['students'] ?></b><small>estudiantes</small></div>
    <div class="kpi"><span><?= icon('bxs-inbox') ?></span><b><?= $kpi['new'] ?></b><small>mensajes nuevos</small></div>
</div>

<?php if ($cred): $text = teacher_welcome_text($cred['full_name'], $cred['username'], $cred['password']); ?>
<section class="card credentials" id="credenciales">
    <div class="card-head"><h2><?= icon('bxs-key', 'bx-tada') ?> Credenciales de <?= e($cred['full_name']) ?></h2></div>
    <div class="cred-grid">
        <div class="cred-card">
            <b><?= e($cred['full_name']) ?></b><small>Docente · <?= e(APP_NAME) ?></small>
            <div><?= icon('bxs-user') ?> Usuario: <code><?= e($cred['username']) ?></code></div>
            <div><?= icon('bxs-lock-alt') ?> Contraseña temporal: <code><?= e($cred['password']) ?></code></div>
        </div>
    </div>
    <?= share_box($text, $cred['email'] ?? '', $cred['phone'] ?? '', 'Tu cuenta de docente en ' . APP_NAME) ?>
</section>
<?php endif; ?>

<?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>

<div class="card table-wrap">
    <table class="table">
        <thead><tr><th>Docente</th><th>Contacto</th><th>Grados</th><th>Grupos</th><th>Estudiantes</th><th>Postest</th><th>Última actividad</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($teachers as $t): $isMe = (int) $t['id'] === (int) $me['id']; ?>
            <tr class="<?= $t['active'] ? '' : 'row-off' ?>">
                <td class="who"><?= avatar_html($t, 'sm') ?> <span><b><?= e($t['full_name']) ?></b><small>@<?= e($t['username']) ?><?= $t['institution'] ? ' · ' . e($t['institution']) : '' ?></small></span></td>
                <td class="small"><?= $t['email'] ? e($t['email']) . '<br>' : '' ?><?= e($t['phone'] ?? '') ?></td>
                <td><?= e(grades_label($t['grades'])) ?></td>
                <td><?= (int) $t['groups_n'] ?><?= $t['control_n'] ? ' <small class="muted">(' . (int) $t['control_n'] . ' control)</small>' : '' ?></td>
                <td><?= (int) $t['students'] ?></td>
                <td><?= (int) $t['posts'] ?></td>
                <td class="muted small"><?= $t['last_activity'] ? date('d/m/Y', strtotime($t['last_activity'])) : '—' ?></td>
                <td><?= $t['role'] === 'superadmin' ? '<span class="status-pill st-respondido">Súper admin</span>' : ($t['active'] ? '<span class="status-pill st-respondido">Habilitado</span>' : '<span class="status-pill st-archivado">Deshabilitado</span>') ?></td>
                <td class="nowrap row-actions">
                    <a class="btn btn-soft btn-sm" href="<?= e(url('admin/index.php?docente=' . (int) $t['id'])) ?>" title="Ver resultados"><?= icon('bxs-bar-chart-alt-2') ?></a>
                    <a class="btn btn-soft btn-sm" href="<?= e(url('admin/students.php?docente=' . (int) $t['id'])) ?>" title="Ver estudiantes"><?= icon('bxs-group') ?></a>
                    <?php if (!$isMe && $t['role'] === 'admin'): ?>
                    <details class="row-edit">
                        <summary class="btn btn-ghost btn-sm" title="Editar"><?= icon('bxs-edit') ?></summary>
                        <form method="post" class="row-edit-form">
                            <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="teacher_id" value="<?= (int) $t['id'] ?>">
                            <input type="text" name="full_name" value="<?= e($t['full_name']) ?>" required>
                            <input type="email" name="email" value="<?= e($t['email']) ?>" placeholder="Correo">
                            <input type="text" name="phone" value="<?= e($t['phone']) ?>" placeholder="Celular">
                            <input type="text" name="institution" value="<?= e($t['institution']) ?>" placeholder="Institución">
                            <div class="grade-checks small-checks"><?php foreach (ALL_GRADES as $g): ?><label class="grade-check"><input type="checkbox" name="grades[]" value="<?= $g ?>" <?= in_array($g, teacher_grades($t), true) ? 'checked' : '' ?>><span><?= $g ?>.º</span></label><?php endforeach; ?></div>
                            <button class="btn btn-primary btn-sm" type="submit">Guardar</button>
                        </form>
                    </details>
                    <form method="post" class="inline" onsubmit="return confirm('¿Generar una contraseña temporal para <?= e($t['username']) ?>?')">
                        <?= csrf_field() ?><input type="hidden" name="teacher_id" value="<?= (int) $t['id'] ?>">
                        <button class="btn btn-ghost btn-sm" name="action" value="reset" title="Restablecer contraseña"><?= icon('bxs-key') ?></button>
                    </form>
                    <form method="post" class="inline" onsubmit="return confirm('<?= $t['active'] ? '¿Deshabilitar la cuenta de ' . e($t['full_name']) . '? No podrá ingresar (sus estudiantes siguen jugando).' : '¿Habilitar la cuenta de ' . e($t['full_name']) . '?' ?>')">
                        <?= csrf_field() ?><input type="hidden" name="teacher_id" value="<?= (int) $t['id'] ?>">
                        <button class="btn <?= $t['active'] ? 'btn-ghost btn-del' : 'btn-primary' ?> btn-sm" name="action" value="toggle" title="<?= $t['active'] ? 'Deshabilitar' : 'Habilitar' ?>"><?= icon($t['active'] ? 'bx-block' : 'bx-check') ?> <?= $t['active'] ? 'Deshabilitar' : 'Habilitar' ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<section class="card" id="nuevo-docente">
    <h2><?= icon('bxs-user-plus') ?> Crear cuenta de docente</h2>
    <p class="muted small">También puedes crearla desde una solicitud en la <a href="<?= e(url('super/inbox.php')) ?>">Bandeja</a>: los datos se llenan solos.</p>
    <form method="post" class="form">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <div class="grid-2">
            <label>Nombres y apellidos<input type="text" name="full_name" value="<?= e($f['full_name']) ?>" required maxlength="120"></label>
            <label>Institución educativa<input type="text" name="institution" value="<?= e($f['institution']) ?>" maxlength="150"></label>
        </div>
        <div class="grid-3">
            <label>Correo<input type="email" name="email" value="<?= e($f['email']) ?>" maxlength="150"></label>
            <label>Celular / WhatsApp<input type="text" name="phone" value="<?= e($f['phone']) ?>" maxlength="30"></label>
            <label>Usuario <span class="muted">(opcional)</span><input type="text" name="username" value="<?= e($f['username']) ?>" maxlength="30" placeholder="Se genera solo"></label>
        </div>
        <div class="field-label">Grados con los que trabajará</div>
        <div class="grade-checks"><?php foreach (ALL_GRADES as $g): ?><label class="grade-check"><input type="checkbox" name="grades[]" value="<?= $g ?>" <?= in_array($g, $fGrades, true) ? 'checked' : '' ?>><span><?= $g ?>.º</span></label><?php endforeach; ?></div>
        <button class="btn btn-primary" type="submit"><?= icon('bx-check') ?> Crear docente con contraseña temporal</button>
    </form>
</section>
<?php page_footer();
