<?php
/**
 * PhishHunter · Instalador de base de datos y seeders.
 *
 * Uso por consola (desde C:\wamp64\www\game):
 *     php database/seed.php            -> crea la BD, las tablas y carga todos los datos
 *
 * También se ejecuta desde el navegador con install.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/seed_helpers.php';
require_once __DIR__ . '/arcade.php';
require_once __DIR__ . '/platform.php';

function phishhunter_install(bool $withDemo = true, ?callable $log = null): void
{
    $log = $log ?: function ($m) { echo $m . PHP_EOL; };

    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT), DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . DB_NAME . '`');
    $log('· Base de datos «' . DB_NAME . '» lista');

    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $schema = preg_replace('/^\s*--.*$/m', '', $schema);
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $sql) {
        $pdo->exec($sql);
    }
    $log('· Tablas creadas');
    platform_schema($pdo);
    $log('· Roles, grupos y mensajes listos');

    $files = glob(__DIR__ . '/seeders/*.php');
    sort($files);
    foreach ($files as $file) {
        $name = basename($file, '.php');
        $seeder = require $file;
        $pdo->beginTransaction();
        $seeder($pdo);
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
        $log('· Seeder ' . $name);
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    try {
        phishhunter_install(!in_array('--no-demo', $argv, true));
        echo PHP_EOL . "Instalación completa. Usuario docente: admin / admin123" . PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
