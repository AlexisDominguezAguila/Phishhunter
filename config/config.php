<?php
/**
 * PhishHunter · Configuración general
 * Ajusta estos valores si tu instalación de WAMP usa otros datos.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'db_phishhunter');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'PhishHunter');
define('APP_TAGLINE', 'Aprende, juega, protégete');

// Zona horaria de Perú (rachas diarias y tabla semanal)
date_default_timezone_set('America/Lima');

// Muestra errores mientras se desarrolla. Pon false en producción.
define('APP_DEBUG', true);
