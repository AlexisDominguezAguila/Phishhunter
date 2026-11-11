<?php
/**
 * PhishHunter · Actualización sin borrar datos
 * Crea las tablas nuevas (docentes y grupos, videoteca y Zona de refuerzo) y carga su contenido.
 * Conserva usuarios, progreso, evaluaciones y resultados.
 *
 * Uso por consola (desde C:\wamp64\www\game):  php database/upgrade.php
 * O desde el navegador (como docente):         http://localhost/game/upgrade.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/arcade.php';
require_once __DIR__ . '/platform.php';

function phishhunter_upgrade(): array
{
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME), DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    platform_schema($pdo);
    return arcade_install($pdo);
}

/**
 * Descarga Boxicons (CSS + fuentes) a assets/vendor/boxicons para que los
 * iconos funcionen sin internet. Devuelve la lista de archivos guardados.
 */
function boxicons_download(string $version = '2.1.4'): array
{
    $base = 'https://cdn.jsdelivr.net/npm/boxicons@' . $version . '/';
    $dir  = dirname(__DIR__) . '/assets/vendor/boxicons/';
    $files = ['css/boxicons.min.css', 'fonts/boxicons.woff2', 'fonts/boxicons.woff', 'fonts/boxicons.ttf', 'fonts/boxicons.eot', 'fonts/boxicons.svg'];
    $saved = [];
    foreach ($files as $f) {
        $data = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($base . $f);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60, CURLOPT_SSL_VERIFYPEER => true]);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code !== 200) $data = false;
        }
        if ($data === false && ini_get('allow_url_fopen')) {
            $data = @file_get_contents($base . $f);
        }
        if ($data === false || $data === '') {
            throw new RuntimeException('No se pudo descargar ' . $f . '. Verifica la conexión a internet del servidor.');
        }
        if (!is_dir(dirname($dir . $f))) mkdir(dirname($dir . $f), 0775, true);
        file_put_contents($dir . $f, $data);
        $saved[] = $f . ' (' . round(strlen($data) / 1024) . ' KB)';
    }
    return $saved;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    try {
        $r = phishhunter_upgrade();
        echo "OK: Actualización completa: {$r['videos']} videos, {$r['games']} juegos de refuerzo, {$r['items']} casos." . PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
