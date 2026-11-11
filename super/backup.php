<?php
/**
 * Súper admin · Respaldo de la base de datos (.sql)
 * Genera un volcado completo (estructura + datos) que se puede importar en
 * phpMyAdmin (WAMP, Hostinger u otro hosting). No necesita mysqldump.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$me = require_super();

/** Escribe el volcado SQL en $out (recurso de archivo). */
function sql_dump($out): array
{
    $pdo = db();
    $q = function ($v) use ($pdo) {
        if ($v === null) return 'NULL';
        if (is_int($v) || is_float($v)) return (string) $v;
        return $pdo->quote((string) $v);
    };
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $stats = [];
    fwrite($out, "-- PhishHunter · respaldo de la base de datos\n-- Generado: " . date('Y-m-d H:i:s') . " (America/Lima)\n-- Importar en phpMyAdmin dentro de una base de datos vacía.\n\n");
    fwrite($out, "SET NAMES utf8mb4;\nSET time_zone = '+00:00';\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");
    foreach ($tables as $t) {
        $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $t) . '`')->fetch(PDO::FETCH_NUM)[1];
        // Compatibilidad MySQL 8 -> MariaDB / MySQL 5.7
        $create = str_replace(['utf8mb4_0900_ai_ci', ' DEFAULT ENCRYPTION=\'N\''], ['utf8mb4_unicode_ci', ''], $create);
        $create = preg_replace('/\s+AUTO_INCREMENT=\d+/', '', $create);
        $create = preg_replace('#\s*/\*!8\d+ DEFAULT ENCRYPTION=\'N\' \*/#', '', $create);
        fwrite($out, "-- ------------------------------------------------------\n-- Tabla `$t`\n-- ------------------------------------------------------\nDROP TABLE IF EXISTS `$t`;\n$create;\n\n");
        $st = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $t) . '`');
        $n = 0;
        $batch = [];
        $cols = null;
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === null) $cols = '(`' . implode('`,`', array_keys($row)) . '`)';
            $batch[] = '(' . implode(',', array_map($q, array_values($row))) . ')';
            $n++;
            if (count($batch) >= 200) {
                fwrite($out, "INSERT INTO `$t` $cols VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if ($batch) fwrite($out, "INSERT INTO `$t` $cols VALUES\n" . implode(",\n", $batch) . ";\n");
        fwrite($out, "\n");
        $stats[$t] = $n;
    }
    fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
    return $stats;
}

if (is_post()) {
    csrf_check();
    $name = 'phishhunter_' . date('Y-m-d_His') . '.sql';
    if (($_POST['action'] ?? '') === 'save') {
        $dir = APP_ROOT . '/database/backups';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $fh = fopen($dir . '/' . $name, 'wb');
        $stats = sql_dump($fh);
        fclose($fh);
        flash('success', 'Respaldo guardado en el servidor: database/backups/' . $name . ' (' . array_sum($stats) . ' filas, ' . count($stats) . ' tablas). Esa carpeta no es accesible desde el navegador.');
        redirect('super/backup.php');
    }
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'wb');
    sql_dump($out);
    exit;
}

$tables = db_all("SELECT table_name AS t, table_rows AS r FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name");
$saved = is_dir(APP_ROOT . '/database/backups') ? array_reverse(glob(APP_ROOT . '/database/backups/*.sql') ?: []) : [];

page_header('Respaldo de la base de datos', 'super');
?>
<div class="section-head">
    <div><h1><?= icon('bxs-data') ?> Respaldo de la base de datos</h1>
        <p class="muted">Descarga un archivo <code>.sql</code> con todas las tablas y datos (docentes, estudiantes, evaluaciones y progreso). Sirve para guardar una copia o para mudar el juego a un hosting: se importa en phpMyAdmin.</p></div>
    <a class="btn btn-soft" href="<?= e(url('super/index.php')) ?>"><?= icon('bxs-user-badge') ?> Docentes</a>
</div>
<div class="alert alert-info"><?= icon('bxs-lock-alt') ?> El respaldo contiene nombres de estudiantes y contraseñas cifradas. Guárdalo en un lugar privado y no lo compartas.</div>
<section class="card">
    <h2><?= icon('bxs-download') ?> Crear respaldo</h2>
    <form method="post" class="form-actions" style="justify-content:flex-start">
        <?= csrf_field() ?>
        <button class="btn btn-primary" name="action" value="download"><?= icon('bxs-download') ?> Descargar .sql</button>
        <button class="btn btn-soft" name="action" value="save"><?= icon('bxs-hdd') ?> Guardar copia en el servidor</button>
    </form>
    <?php if ($saved): ?>
        <p class="muted small">Copias en el servidor (carpeta <code>database/backups</code>): <?= e(implode(', ', array_map('basename', array_slice($saved, 0, 5)))) ?></p>
    <?php endif; ?>
</section>
<section class="card table-wrap">
    <h2><?= icon('bx-table') ?> Tablas</h2>
    <table class="table"><thead><tr><th>Tabla</th><th>Filas (aprox.)</th></tr></thead><tbody>
    <?php foreach ($tables as $t): ?><tr><td><code><?= e($t['t']) ?></code></td><td><?= (int) $t['r'] ?></td></tr><?php endforeach; ?>
    </tbody></table>
</section>
<?php page_footer();
