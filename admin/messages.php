<?php
/**
 * Docente · Mensajes al administrador de la plataforma (súper admin).
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$me = require_admin();
if (is_super($me)) {
    redirect('super/inbox.php');
}

$errors = [];
$f = ['subject' => '', 'body' => ''];
if (is_post()) {
    csrf_check();
    foreach ($f as $k => $v) $f[$k] = trim((string) ($_POST[$k] ?? ''));
    if (mb_strlen($f['subject']) < 3) $errors[] = 'Escribe un asunto.';
    if (mb_strlen($f['body']) < 5) $errors[] = 'Escribe tu mensaje.';
    if (!$errors) {
        db_exec('INSERT INTO messages (kind, from_user_id, name, email, phone, institution, grades, subject, body) VALUES (?,?,?,?,?,?,?,?,?)', [
            'mensaje', $me['id'], $me['full_name'], $me['email'], $me['phone'], $me['institution'], $me['grades'],
            mb_substr($f['subject'], 0, 150), mb_substr($f['body'], 0, 3000),
        ]);
        flash('success', 'Mensaje enviado. Verás la respuesta aquí mismo.');
        redirect('admin/messages.php');
    }
}

$list = db_all("SELECT * FROM messages WHERE kind = 'mensaje' AND from_user_id = ? ORDER BY created_at DESC", [$me['id']]);

page_header('Mensajes', 'messages');
?>
<div class="section-head">
    <div><h1><?= icon('bxs-message-rounded-dots') ?> Mensajes al administrador</h1>
        <p class="muted">¿Necesitas más grados, ayuda con tus grupos o reportar un problema? Escribe aquí y te responderemos en esta misma página.</p></div>
</div>
<div class="grid-2">
    <section class="card">
        <h2><?= icon('bxs-send') ?> Nuevo mensaje</h2>
        <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <label>Asunto<input type="text" name="subject" value="<?= e($f['subject']) ?>" maxlength="150" required></label>
            <label>Mensaje<textarea name="body" rows="6" maxlength="3000" required><?= e($f['body']) ?></textarea></label>
            <button class="btn btn-primary btn-block" type="submit"><?= icon('bxs-send') ?> Enviar</button>
        </form>
    </section>
    <section class="card">
        <h2><?= icon('bx-history') ?> Mis mensajes</h2>
        <?php foreach ($list as $m): ?>
            <article class="msg-thread">
                <div class="mt-head"><b><?= e($m['subject']) ?></b><span class="status-pill st-<?= e($m['status']) ?>"><?= $m['reply'] ? 'Respondido' : 'Enviado' ?></span></div>
                <small class="muted"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></small>
                <p><?= nl2br(e($m['body'])) ?></p>
                <?php if ($m['reply']): ?>
                    <div class="mt-reply"><b><?= icon('bxs-user-badge') ?> Respuesta del administrador</b><small class="muted"> · <?= date('d/m/Y H:i', strtotime($m['replied_at'])) ?></small><p><?= nl2br(e($m['reply'])) ?></p></div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$list): ?><p class="muted">Aún no has enviado mensajes.</p><?php endif; ?>
    </section>
</div>
<?php page_footer();
