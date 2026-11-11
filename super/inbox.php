<?php
/**
 * Súper admin · Bandeja
 *  - Solicitudes de «Soy docente nuevo»: crear la cuenta y responder con credenciales temporales.
 *  - Mensajes de docentes: responder (el docente ve la respuesta en su panel).
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$me = require_super();

if (is_post()) {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $m = db_one('SELECT * FROM messages WHERE id = ?', [$id]);
    $action = $_POST['action'] ?? '';
    if (!$m) {
        flash('error', 'Mensaje no encontrado.');
        redirect('super/inbox.php');
    }
    if ($action === 'create_teacher' && $m['kind'] === 'solicitud' && !$m['created_user_id']) {
        $d = [
            'full_name'   => trim((string) ($_POST['full_name'] ?? $m['name'])),
            'email'       => trim((string) ($_POST['email'] ?? '')),
            'phone'       => trim((string) ($_POST['phone'] ?? '')),
            'institution' => trim((string) ($_POST['institution'] ?? '')),
            'username'    => trim((string) ($_POST['username'] ?? '')),
            'grades'      => grades_to_csv((array) ($_POST['grades'] ?? [])),
        ];
        $err = null;
        if (mb_strlen($d['full_name']) < 5) $err = 'Revisa el nombre.';
        elseif ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err = 'El correo no es válido.';
        elseif ($d['username'] !== '' && (!preg_match('/^[a-zA-Z0-9._]{3,30}$/', $d['username']) || db_val('SELECT 1 FROM users WHERE username = ?', [$d['username']]))) $err = 'Ese usuario no es válido o ya existe.';
        elseif (!$d['grades']) $err = 'Elige al menos un grado.';
        if ($err) {
            flash('error', $err);
            redirect('super/inbox.php?id=' . $id);
        }
        $cred = create_teacher($d);
        $reply = teacher_welcome_text($d['full_name'], $cred['username'], $cred['password']);
        // En la base solo se guarda la respuesta SIN la contraseña.
        $stored = preg_replace('/Contraseña temporal: .*/u', 'Contraseña temporal: (entregada al docente)', $reply);
        db_exec("UPDATE messages SET status = 'respondido', reply = ?, replied_at = NOW(), created_user_id = ? WHERE id = ?", [$stored, $cred['id'], $id]);
        $_SESSION['inbox_reply'] = ['id' => $id, 'text' => $reply];
        flash('success', 'Cuenta creada para ' . $d['full_name'] . '. Envíale el mensaje con sus credenciales.');
        redirect('super/inbox.php?id=' . $id);
    } elseif ($action === 'reply') {
        $text = trim((string) ($_POST['reply'] ?? ''));
        if (mb_strlen($text) < 2) {
            flash('error', 'Escribe una respuesta.');
        } else {
            db_exec("UPDATE messages SET status = 'respondido', reply = ?, replied_at = NOW() WHERE id = ?", [mb_substr($text, 0, 3000), $id]);
            flash('success', $m['kind'] === 'mensaje' ? 'Respuesta guardada: el docente la verá en su panel.' : 'Respuesta guardada.');
            if ($m['kind'] === 'solicitud') $_SESSION['inbox_reply'] = ['id' => $id, 'text' => $text];
        }
        redirect('super/inbox.php?id=' . $id);
    } elseif (in_array($action, ['archive', 'unarchive'], true)) {
        db_exec('UPDATE messages SET status = ? WHERE id = ?', [$action === 'archive' ? 'archivado' : ($m['reply'] ? 'respondido' : 'leido'), $id]);
        redirect('super/inbox.php' . ($action === 'archive' ? '' : '?id=' . $id));
    } elseif ($action === 'delete') {
        db_exec('DELETE FROM messages WHERE id = ?', [$id]);
        flash('success', 'Mensaje eliminado.');
        redirect('super/inbox.php');
    }
    redirect('super/inbox.php');
}

$tab = in_array($_GET['tab'] ?? '', ['solicitud', 'mensaje', 'archivado'], true) ? $_GET['tab'] : 'todos';
$where = $tab === 'archivado' ? "status = 'archivado'" : "status <> 'archivado'" . ($tab !== 'todos' ? " AND kind = '" . $tab . "'" : '');
$list = db_all("SELECT * FROM messages WHERE $where ORDER BY status = 'nuevo' DESC, created_at DESC LIMIT 200");
$counts = db_one("SELECT SUM(status <> 'archivado') todos, SUM(kind = 'solicitud' AND status <> 'archivado') sol, SUM(kind = 'mensaje' AND status <> 'archivado') msg, SUM(status = 'archivado') arc, SUM(status = 'nuevo') nuevos FROM messages");

$open = null;
if (!empty($_GET['id'])) {
    $open = db_one('SELECT * FROM messages WHERE id = ?', [(int) $_GET['id']]);
    if ($open && $open['status'] === 'nuevo') {
        db_exec("UPDATE messages SET status = 'leido' WHERE id = ?", [$open['id']]);
        $open['status'] = 'leido';
    }
}
$flashReply = $_SESSION['inbox_reply'] ?? null;
unset($_SESSION['inbox_reply']);

page_header('Bandeja', 'inbox');
$st = ['nuevo' => 'Nuevo', 'leido' => 'Leído', 'respondido' => 'Respondido', 'archivado' => 'Archivado'];
?>
<div class="section-head">
    <div><h1><?= icon('bxs-inbox') ?> Bandeja</h1><p class="muted">Solicitudes de docentes nuevos y mensajes de tus docentes.</p></div>
    <a class="btn btn-soft" href="<?= e(url('super/index.php')) ?>"><?= icon('bxs-user-badge') ?> Docentes</a>
</div>
<div class="filters">
    <div class="seg-control">
        <a class="<?= $tab === 'todos' ? 'on' : '' ?>" href="?">Todo (<?= (int) $counts['todos'] ?>)</a>
        <a class="<?= $tab === 'solicitud' ? 'on' : '' ?>" href="?tab=solicitud">Solicitudes (<?= (int) $counts['sol'] ?>)</a>
        <a class="<?= $tab === 'mensaje' ? 'on' : '' ?>" href="?tab=mensaje">Mensajes (<?= (int) $counts['msg'] ?>)</a>
        <a class="<?= $tab === 'archivado' ? 'on' : '' ?>" href="?tab=archivado">Archivados (<?= (int) $counts['arc'] ?>)</a>
    </div>
</div>

<div class="inbox">
    <ul class="inbox-list card">
        <?php foreach ($list as $m): ?>
            <li class="<?= $open && (int) $open['id'] === (int) $m['id'] ? 'active' : '' ?> <?= $m['status'] === 'nuevo' ? 'unread' : '' ?>">
                <a href="?<?= $tab !== 'todos' ? 'tab=' . e($tab) . '&amp;' : '' ?>id=<?= (int) $m['id'] ?>">
                    <span class="ib-icon"><?= icon($m['kind'] === 'solicitud' ? 'bxs-user-plus' : 'bxs-message-rounded-dots') ?></span>
                    <span class="ib-main"><b><?= e($m['name']) ?></b><small><?= e($m['kind'] === 'solicitud' ? ($m['institution'] ?: 'Solicitud de cuenta') : $m['subject']) ?></small></span>
                    <span class="ib-meta"><span class="status-pill st-<?= e($m['status']) ?>"><?= $st[$m['status']] ?></span><small><?= date('d/m', strtotime($m['created_at'])) ?></small></span>
                </a>
            </li>
        <?php endforeach; ?>
        <?php if (!$list): ?><li class="muted empty-li">No hay mensajes aquí.</li><?php endif; ?>
    </ul>

    <section class="inbox-view card">
        <?php if (!$open): ?>
            <?= mascot_say('Elige un mensaje de la lista. Las solicitudes de docentes nuevos se atienden creando su cuenta con un clic.', 'hola') ?>
        <?php else: ?>
            <div class="card-head">
                <div>
                    <span class="group-tag <?= $open['kind'] === 'solicitud' ? 'gt-control' : 'gt-experimental' ?>"><?= $open['kind'] === 'solicitud' ? 'Solicitud de cuenta' : 'Mensaje de docente' ?></span>
                    <h2><?= e($open['kind'] === 'solicitud' ? $open['name'] : $open['subject']) ?></h2>
                </div>
                <span class="status-pill st-<?= e($open['status']) ?>"><?= $st[$open['status']] ?></span>
            </div>
            <dl class="contact-dl">
                <div><dt>Nombre</dt><dd><?= e($open['name']) ?></dd></div>
                <div><dt>Correo</dt><dd><?= $open['email'] ? '<a href="mailto:' . e($open['email']) . '">' . e($open['email']) . '</a>' : '—' ?></dd></div>
                <div><dt>Celular</dt><dd><?= $open['phone'] ? e($open['phone']) . (wa_number($open['phone']) ? ' · <a target="_blank" rel="noopener" href="https://wa.me/' . e(wa_number($open['phone'])) . '">WhatsApp</a>' : '') : '—' ?></dd></div>
                <div><dt>Institución</dt><dd><?= e($open['institution'] ?: '—') ?></dd></div>
                <div><dt>Grados</dt><dd><?= e(grades_label($open['grades'])) ?></dd></div>
                <div><dt>Fecha</dt><dd><?= date('d/m/Y H:i', strtotime($open['created_at'])) ?></dd></div>
            </dl>
            <div class="msg-body"><?= nl2br(e($open['body'])) ?></div>

            <?php if ($flashReply && (int) $flashReply['id'] === (int) $open['id']): ?>
                <?= share_box($flashReply['text'], $open['email'], $open['phone'], $open['kind'] === 'solicitud' ? 'Tu cuenta de docente en ' . APP_NAME : 'Respuesta: ' . $open['subject']) ?>
            <?php elseif ($open['reply']): ?>
                <div class="mt-reply"><b><?= icon('bx-check') ?> Respuesta</b><small class="muted"> · <?= date('d/m/Y H:i', strtotime($open['replied_at'])) ?></small><p><?= nl2br(e($open['reply'])) ?></p></div>
            <?php endif; ?>

            <?php if ($open['kind'] === 'solicitud' && !$open['created_user_id']):
                $existing = $open['email'] ? db_one("SELECT id, username FROM users WHERE email = ? AND role IN ('admin','superadmin')", [$open['email']]) : null; ?>
                <h3 class="mt"><?= icon('bxs-user-plus') ?> Crear la cuenta de este docente</h3>
                <?php if ($existing): ?><div class="alert alert-warning">Ya existe un docente con ese correo (@<?= e($existing['username']) ?>).</div><?php endif; ?>
                <form method="post" class="form">
                    <?= csrf_field() ?><input type="hidden" name="action" value="create_teacher"><input type="hidden" name="id" value="<?= (int) $open['id'] ?>">
                    <div class="grid-2">
                        <label>Nombres y apellidos<input type="text" name="full_name" value="<?= e($open['name']) ?>" required></label>
                        <label>Institución<input type="text" name="institution" value="<?= e($open['institution']) ?>"></label>
                    </div>
                    <div class="grid-3">
                        <label>Correo<input type="email" name="email" value="<?= e($open['email']) ?>"></label>
                        <label>Celular<input type="text" name="phone" value="<?= e($open['phone']) ?>"></label>
                        <label>Usuario <span class="muted">(opcional)</span><input type="text" name="username" placeholder="<?= e(make_username($open['name'])) ?>"></label>
                    </div>
                    <div class="grade-checks"><?php $gs = $open['grades'] ? array_map('intval', explode(',', $open['grades'])) : [3, 4, 5];
                        foreach (ALL_GRADES as $g): ?><label class="grade-check"><input type="checkbox" name="grades[]" value="<?= $g ?>" <?= in_array($g, $gs, true) ? 'checked' : '' ?>><span><?= $g ?>.º</span></label><?php endforeach; ?></div>
                    <button class="btn btn-primary" type="submit"><?= icon('bx-check') ?> Crear cuenta y preparar respuesta</button>
                </form>
            <?php elseif ($open['kind'] === 'solicitud' && $open['created_user_id']): ?>
                <p class="muted small"><?= icon('bx-check') ?> Cuenta creada. Puedes restablecer su contraseña desde <a href="<?= e(url('super/index.php')) ?>">Docentes</a> si la necesita otra vez.</p>
            <?php endif; ?>

            <?php if ($open['kind'] === 'mensaje' || $open['created_user_id'] || $open['status'] !== 'archivado'): ?>
            <h3 class="mt"><?= icon('bxs-send') ?> <?= $open['kind'] === 'mensaje' ? 'Responder al docente' : 'Otra respuesta' ?></h3>
            <form method="post" class="form">
                <?= csrf_field() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="id" value="<?= (int) $open['id'] ?>">
                <textarea name="reply" rows="4" maxlength="3000" placeholder="<?= $open['kind'] === 'mensaje' ? 'El docente verá esta respuesta en su panel (Mensajes).' : 'Por ejemplo, si no puedes crear su cuenta, explícale por qué.' ?>"></textarea>
                <button class="btn btn-soft" type="submit"><?= icon('bxs-send') ?> Guardar respuesta</button>
            </form>
            <?php endif; ?>

            <div class="res-actions left">
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $open['id'] ?>">
                    <button class="btn btn-ghost btn-sm" name="action" value="<?= $open['status'] === 'archivado' ? 'unarchive' : 'archive' ?>"><?= icon('bxs-archive') ?> <?= $open['status'] === 'archivado' ? 'Desarchivar' : 'Archivar' ?></button></form>
                <form method="post" class="inline" onsubmit="return confirm('¿Eliminar este mensaje?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $open['id'] ?>">
                    <button class="btn btn-ghost btn-sm btn-del" name="action" value="delete"><?= icon('bxs-trash') ?> Eliminar</button></form>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php page_footer();
