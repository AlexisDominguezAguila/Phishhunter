<?php
/**
 * Panel · Importar evaluaciones aplicadas en papel o en Excel
 *
 * Pega las filas de tu Excel (una por estudiante): nombre + las 24 respuestas
 * del pretest/postest (y, en el postest, opcionalmente las 9 de valoración).
 *  - Si el estudiante ya tiene cuenta con ese docente, se vincula a ella.
 *  - Si no, se le crea la cuenta (usuario y contraseña temporal).
 *  - La evaluación se guarda igual que si la hubiera respondido en la plataforma
 *    (mismos puntajes, niveles y recompensas).
 * Primero se muestra una vista previa; nada se guarda hasta confirmar.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$me = require_admin();
$isSuper = is_super($me);
if (!$isSuper) ensure_default_group(db(), (int) $me['id']);

$destGroups = db_all("SELECT g.*, t.full_name AS teacher_name, t.grades AS teacher_grades, t.active AS teacher_active
                        FROM class_groups g JOIN users t ON t.id = g.teacher_id
                       WHERE t.role = 'admin'" . ($isSuper ? '' : ' AND t.id = ' . (int) $me['id']) . "
                    ORDER BY t.full_name, g.id");
if (!$isSuper) {
    $destGroups = db_all('SELECT g.*, ? AS teacher_name, ? AS teacher_grades, 1 AS teacher_active FROM class_groups g WHERE g.teacher_id = ? ORDER BY g.id',
        [$me['full_name'], $me['grades'], (int) $me['id']]);
}
$destById = [];
foreach ($destGroups as $g) $destById[(int) $g['id']] = $g;

/** Minúsculas sin tildes ni signos, para comparar nombres. */
function name_tokens(string $name): array
{
    $n = strtr(mb_strtolower($name, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return array_values(array_filter(preg_split('/[^a-z0-9]+/', $n)));
}

/** «PEREZ GOMEZ, ana lucia» -> «Perez Gomez, Ana Lucia» */
function nice_name(string $name): string
{
    $name = preg_replace('/\s+/u', ' ', trim($name));
    $name = preg_replace('/\s*,\s*/u', ', ', $name);
    return mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

/**
 * Lee las filas pegadas desde Excel. Cada fila: [N.°] Nombre [Género] r1 … rN [totales…]
 * Devuelve [filas válidas, errores].
 */
function parse_rows(string $text, int $needed, int $optional): array
{
    $rows = [];
    $errors = [];
    foreach (preg_split('/\R/u', $text) as $ln => $line) {
        if (trim($line) === '') continue;
        $cells = array_map('trim', strpos($line, "\t") !== false ? explode("\t", $line) : explode(';', $line));
        $name = null;
        $nums = [];
        foreach ($cells as $c) {
            if ($name === null) {
                if (preg_match('/\p{L}.*\p{L}.*\p{L}/u', $c)) $name = $c;   // primera celda con texto = nombre
                continue;
            }
            if ($c === '' || !preg_match('/^-?\d+([.,]0+)?$/', $c)) continue;  // género u otros textos
            $nums[] = (int) $c;
        }
        if ($name === null || !$nums) continue;                               // encabezados
        $take = count($nums) >= $needed + $optional && $optional ? $needed + $optional : $needed;
        if (count($nums) < $needed) {
            $errors[] = 'Fila ' . ($ln + 1) . ' (' . $name . '): tiene ' . count($nums) . ' respuestas y se necesitan ' . $needed . '.';
            continue;
        }
        $vals = array_slice($nums, 0, $take);
        foreach ($vals as $i => $v) {
            if ($v < 1 || $v > 5) {
                $errors[] = 'Fila ' . ($ln + 1) . ' (' . $name . '): la respuesta ' . ($i + 1) . ' vale ' . $v . ' (debe ser de 1 a 5).';
                continue 2;
            }
        }
        $rows[] = ['line' => $ln + 1, 'name' => $name, 'values' => $vals];
    }
    return [$rows, $errors];
}

/** Puntajes por dimensión y nivel (mismas reglas que save_assessment). */
function score_preview(array $vals): array
{
    $d1 = array_sum(array_slice($vals, 0, 8));
    $d2 = array_sum(array_slice($vals, 8, 9));
    $d3 = array_sum(array_slice($vals, 17, 7));
    $t = $d1 + $d2 + $d3;
    return ['d1' => $d1, 'd2' => $d2, 'd3' => $d3, 'total' => $t, 'level' => level_from_ranges($t, PREVENTION_GLOBAL)];
}

$f = [
    'group'   => (string) ($_POST['group'] ?? ($_GET['grupo'] ?? '')),
    'grade'   => (string) ($_POST['grade'] ?? ''),
    'section' => strtoupper((string) ($_POST['section'] ?? '')),
    'type'    => ($_POST['type'] ?? 'pretest') === 'postest' ? 'postest' : 'pretest',
    'date'    => (string) ($_POST['date'] ?? ''),
    'nice'    => is_post() ? !empty($_POST['nice']) : true,
    'replace' => !empty($_POST['replace']),
    'rows'    => (string) ($_POST['rows'] ?? ''),
];
$errors = [];
$preview = null;
$result = $_SESSION['import_result'] ?? null;
unset($_SESSION['import_result']);

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? 'preview';
    $dest = $destById[(int) $f['group']] ?? null;
    if (!$dest) $errors[] = 'Elige el grupo de destino.';
    elseif (!(int) $dest['teacher_active']) $errors[] = 'La cuenta de ' . $dest['teacher_name'] . ' está deshabilitada.';
    $grades = $dest ? teacher_grades(['grades' => $dest['teacher_grades']]) : ALL_GRADES;
    if (!in_array((int) $f['grade'], $grades, true)) $errors[] = 'Elige un grado con el que trabaja el docente (' . grades_label(implode(',', $grades)) . ').';
    if (!in_array($f['section'], SECTIONS, true)) $errors[] = 'Elige la sección.';
    if ($f['date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['date'])) $errors[] = 'La fecha no es válida.';
    [$rows, $parseErrors] = parse_rows($f['rows'], 24, $f['type'] === 'postest' ? 9 : 0);
    $errors = array_merge($errors, $parseErrors);
    if (!$rows && !$parseErrors) $errors[] = 'Pega al menos una fila con el nombre y las 24 respuestas.';
    if (count($rows) > 80) $errors[] = 'Máximo 80 estudiantes por importación.';

    if (!$errors) {
        // Estudiantes que ya tiene el docente (para no duplicar cuentas)
        $existing = db_all("SELECT id, full_name, username, grade, section, group_id FROM users WHERE role = 'student' AND teacher_id = ?", [(int) $dest['teacher_id']]);
        $plan = [];
        $usedIds = [];
        foreach ($rows as $r) {
            $name = $f['nice'] ? nice_name($r['name']) : preg_replace('/\s+/u', ' ', trim($r['name']));
            $tok = name_tokens($name);
            $hits = array_values(array_filter($existing, function ($s) use ($tok, $usedIds) {
                if (in_array((int) $s['id'], $usedIds, true)) return false;
                $st = name_tokens($s['full_name']);
                return $st && (!array_diff($st, $tok) || !array_diff($tok, $st));
            }));
            $match = count($hits) === 1 ? $hits[0] : null;
            if ($match) $usedIds[] = (int) $match['id'];
            $had = $match ? has_assessment((int) $match['id'], $f['type']) : false;
            $plan[] = $r + ['full_name' => $name, 'match' => $match, 'had' => $had, 'ambiguous' => count($hits) > 1, 'score' => score_preview($r['values'])];
        }

        if ($action === 'preview') {
            $preview = $plan;
        } else {
            $items = survey_items('prevention');
            $gItems = survey_items('gamification');
            $out = [];
            $when = $f['date'] !== '' ? $f['date'] . ' 08:00:00' : null;
            foreach ($plan as $p) {
                if ($p['match']) {
                    $uid = (int) $p['match']['id'];
                    db_exec('UPDATE users SET full_name = ?, grade = ?, section = ?, group_id = ? WHERE id = ?',
                        [$p['full_name'], (int) $f['grade'], $f['section'], (int) $dest['id'], $uid]);
                    $cred = ['username' => $p['match']['username'], 'password' => null];
                } else {
                    $c = create_student((int) $dest['teacher_id'], (int) $dest['id'], $p['full_name'], (int) $f['grade'], $f['section']);
                    $uid = (int) $c['id'];
                    $cred = ['username' => $c['username'], 'password' => $c['password']];
                }
                $status = $p['match'] ? 'Cuenta existente' : 'Cuenta nueva';
                if ($p['match'] && $p['had'] && !$f['replace']) {
                    $status .= ' · ya tenía ' . $f['type'] . ' (no se cambió)';
                } else {
                    if ($p['had']) {
                        db_exec('DELETE FROM assessments WHERE user_id = ? AND type IN (?, ?)', [$uid, $f['type'], $f['type'] === 'postest' ? 'valoracion' : $f['type']]);
                    }
                    $answers = [];
                    foreach ($items as $i => $it) $answers[$it['id']] = $p['values'][$i];
                    save_assessment($uid, $f['type'], $answers);
                    if ($f['type'] === 'postest' && count($p['values']) >= 33) {
                        $ga = [];
                        foreach ($gItems as $i => $it) $ga[$it['id']] = $p['values'][24 + $i];
                        save_assessment($uid, 'valoracion', $ga);
                    }
                    if ($when) db_exec('UPDATE assessments SET created_at = ? WHERE user_id = ? AND type IN (?, ?)', [$when, $uid, $f['type'], 'valoracion']);
                    if (!$p['had']) {
                        // Mismas recompensas que al responder en la plataforma
                        if ($f['type'] === 'pretest') {
                            add_xp($uid, 30, 10, 'Evaluación diagnóstica completada');
                            award_badge($uid, 'primer-paso');
                        } else {
                            add_xp($uid, 100, 50, 'Evaluación final completada');
                            award_badge($uid, 'graduado');
                            $pre = get_assessment($uid, 'pretest');
                            if ($pre && $p['score']['total'] > (int) $pre['total']) award_badge($uid, 'superacion');
                        }
                        check_achievements($uid);
                    }
                    $status .= ' · ' . $f['type'] . ' registrado';
                }
                $out[] = ['full_name' => $p['full_name'], 'status' => $status, 'total' => $p['score']['total'], 'level' => $p['score']['level']] + $cred;
            }
            $_SESSION['import_result'] = ['rows' => $out, 'type' => $f['type'], 'grade' => (int) $f['grade'], 'section' => $f['section'],
                                          'group' => $dest['name'], 'teacher' => $dest['teacher_name']];
            $new = count(array_filter($out, function ($o) { return $o['password'] !== null; }));
            flash('success', count($out) . ' estudiantes procesados (' . $new . ' cuentas nuevas) en ' . $dest['teacher_name'] . ' › ' . $dest['name'] . '.');
            redirect('admin/import.php#resultado');
        }
    }
}

page_header('Importar evaluaciones', 'students');
?>
<div class="section-head">
    <div>
        <h1><?= icon('bxs-file-import') ?> Importar evaluaciones</h1>
        <p class="muted">¿Aplicaste el pretest o el postest en papel o en Excel? Pega las filas y PhishHunter crea las cuentas que falten y guarda las respuestas con los mismos puntajes y niveles.</p>
    </div>
    <a class="btn btn-soft" href="<?= e(url('admin/students.php')) ?>"><?= icon('bxs-group') ?> Estudiantes</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php if ($result): ?>
<section class="card credentials" id="resultado">
    <div class="card-head">
        <h2><?= icon('bxs-key', 'bx-tada') ?> Resultado de la importación</h2>
        <div class="no-print">
            <button class="btn btn-soft btn-sm" type="button" data-csv="import-table" data-file="credenciales_<?= e($result['grade'] . $result['section']) ?>.csv"><?= icon('bxs-download') ?> Descargar CSV</button>
            <button class="btn btn-soft btn-sm" type="button" onclick="window.print()"><?= icon('bxs-printer') ?> Imprimir</button>
        </div>
    </div>
    <p class="muted small"><?= e($result['teacher']) ?> › <?= e($result['group']) ?> · <?= (int) $result['grade'] ?>.º <?= e($result['section']) ?> · Las contraseñas se muestran <b>solo esta vez</b>: descárgalas o imprímelas y entrégalas en persona.</p>
    <div class="table-wrap">
        <table class="table" id="import-table">
            <thead><tr><th>N.°</th><th>Estudiante</th><th>Usuario</th><th>Contraseña</th><th>Grado</th><th><?= $result['type'] === 'postest' ? 'Postest' : 'Pretest' ?></th><th>Nivel</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td><td><?= e($r['full_name']) ?></td><td><code><?= e($r['username']) ?></code></td>
                    <td><?= $r['password'] !== null ? '<code>' . e($r['password']) . '</code>' : '<span class="muted">(la que ya tenía)</span>' ?></td>
                    <td><?= (int) $result['grade'] ?>.º <?= e($result['section']) ?></td><td><?= (int) $r['total'] ?></td><td><?= level_badge($r['level']) ?></td><td class="small"><?= e($r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if ($preview): ?>
<section class="card" id="vista-previa">
    <h2><?= icon('bxs-show') ?> Vista previa · <?= count($preview) ?> estudiantes</h2>
    <p class="muted small">Revisa que los nombres y puntajes coincidan con tu Excel. Todavía no se ha guardado nada.</p>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Fila</th><th>Estudiante</th><th>Cuenta</th><th>D1</th><th>D2</th><th>D3</th><th>Total</th><th>Nivel</th></tr></thead>
            <tbody>
            <?php foreach ($preview as $p): ?>
                <tr>
                    <td><?= (int) $p['line'] ?></td><td><?= e($p['full_name']) ?></td>
                    <td class="small"><?php if ($p['match']): ?><?= icon('bx-link') ?> Existente: <code><?= e($p['match']['username']) ?></code><?= $p['had'] ? ' <b>· ya tiene ' . e($f['type']) . '</b>' : '' ?><?php elseif ($p['ambiguous']): ?><span class="up"><?= icon('bxs-error') ?> Varias coincidencias: se creará una cuenta nueva</span><?php else: ?><?= icon('bx-plus') ?> Nueva<?php endif; ?></td>
                    <td><?= $p['score']['d1'] ?></td><td><?= $p['score']['d2'] ?></td><td><?= $p['score']['d3'] ?></td><td><b><?= $p['score']['total'] ?></b></td><td><?= level_badge($p['score']['level']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<section class="card no-print">
    <h2><?= icon('bxs-spreadsheet') ?> Datos a importar</h2>
    <form method="post" class="form" id="import-form">
        <?= csrf_field() ?>
        <div class="grid-3">
            <label><?= $isSuper ? 'Destino (docente › grupo)' : 'Grupo' ?>
                <select name="group" required>
                    <option value="">— Elige —</option>
                    <?php foreach ($destGroups as $g): ?>
                        <option value="<?= (int) $g['id'] ?>" <?= $f['group'] === (string) $g['id'] ? 'selected' : '' ?>><?= e(($isSuper ? $g['teacher_name'] . ' › ' : '') . $g['name']) ?> (<?= $g['type'] === 'control' ? 'control' : 'experimental' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Grado
                <select name="grade" required><option value="">—</option><?php foreach (ALL_GRADES as $g): ?><option value="<?= $g ?>" <?= $f['grade'] === (string) $g ? 'selected' : '' ?>><?= $g ?>.º</option><?php endforeach; ?></select>
            </label>
            <label>Sección
                <select name="section" required><option value="">—</option><?php foreach (SECTIONS as $s): ?><option <?= $f['section'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
            </label>
        </div>
        <div class="grid-2">
            <label>Evaluación
                <select name="type">
                    <option value="pretest" <?= $f['type'] === 'pretest' ? 'selected' : '' ?>>Pretest (24 respuestas)</option>
                    <option value="postest" <?= $f['type'] === 'postest' ? 'selected' : '' ?>>Postest (24 respuestas + 9 de valoración opcionales)</option>
                </select>
            </label>
            <label>Fecha en que se aplicó <span class="muted">(opcional)</span>
                <input type="date" name="date" value="<?= e($f['date']) ?>">
            </label>
        </div>
        <label>Filas de tu Excel <span class="muted">(selecciona desde el nombre hasta el ítem 24 —o toda la fila— y pega aquí; una fila por estudiante)</span>
            <textarea name="rows" rows="10" placeholder="PEREZ GOMEZ, Ana Lucia&#9;F&#9;1&#9;2&#9;2&#9;2&#9;…&#10;RAMOS SILVA, Juan Carlos&#9;M&#9;2&#9;2&#9;3&#9;…"><?= e($f['rows']) ?></textarea>
        </label>
        <label class="check"><input type="checkbox" name="nice" value="1" <?= $f['nice'] ? 'checked' : '' ?>> Escribir los nombres con mayúscula inicial («Perez Gomez, Ana Lucia»)</label>
        <label class="check"><input type="checkbox" name="replace" value="1" <?= $f['replace'] ? 'checked' : '' ?>> Si el estudiante ya tiene esta evaluación, reemplazarla</label>
        <p class="muted small">Se toman las primeras 24 cifras después del nombre (el N.° y el género se ignoran; las columnas de totales que vengan después también). Si el nombre coincide con un estudiante que ya existe con ese docente, se usa esa cuenta.</p>
        <div class="form-actions">
            <button class="btn btn-soft" type="submit" name="action" value="preview"><?= icon('bxs-show') ?> Vista previa</button>
            <button class="btn btn-primary" type="submit" name="action" value="import" <?= $preview ? '' : 'onclick="return confirm(\'¿Importar sin revisar la vista previa?\')"' ?>><?= icon('bx-check') ?> Importar</button>
        </div>
    </form>
</section>
<script>
document.querySelectorAll('[data-csv]').forEach(function (b) {
    b.addEventListener('click', function () {
        var rows = [].map.call(document.getElementById(b.dataset.csv).rows, function (tr) {
            return [].map.call(tr.cells, function (td) { return '"' + td.textContent.replace(/"/g, '""').trim() + '"'; }).join(';');
        });
        var blob = new Blob(['﻿' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = b.dataset.file; a.click();
    });
});
</script>
<?php page_footer();
