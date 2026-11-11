<?php
/**
 * PhishHunter · Enrutador de URLs amigables
 * Atiende direcciones como /refuerzo, /clasificacion o /mision/que-es-phishing
 * y ejecuta el script correspondiente (la tabla está en includes/routes.php).
 *
 * Apache: lo activa el .htaccess de la raíz.
 * Servidor de PHP (pruebas): php -S localhost:8000 router.php
 */

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Servidor integrado de PHP: los archivos reales se sirven tal cual.
if (PHP_SAPI === 'cli-server') {
    $real = realpath($_SERVER['DOCUMENT_ROOT'] . rawurldecode($uriPath));
    if ($real !== false && is_file($real) && $real !== __FILE__) {
        return false;
    }
}

define('PH_ROUTED', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/routes.php';

// Parte de la ruta que va después de la carpeta de la app (p. ej. /game)
$webRoot = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/router.php')), '/');
if (PHP_SAPI === 'cli-server') {
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $webRoot = rtrim(substr(str_replace('\\', '/', __DIR__), strlen($docRoot)), '/');
}
$rel = $uriPath;
if ($webRoot !== '' && strpos($rel, $webRoot) === 0) {
    $rel = substr($rel, strlen($webRoot));
}

$match = match_route($rel);
if ($match === null) {
    // Página no encontrada (con el diseño de la app)
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
    $_SERVER['SCRIPT_NAME']     = $webRoot . '/index.php';
    require_once __DIR__ . '/includes/bootstrap.php';
    http_response_code(404);
    page_header('Página no encontrada');
    echo '<section class="card not-found"><img src="' . e(url('assets/img/mascot/chispa-piensa.svg')) . '" alt="Chispa" width="120">'
       . '<h1>' . icon('bx-search-alt') . ' Esta página no existe</h1>'
       . '<p class="muted">Revisa la dirección: <code>' . e($uriPath) . '</code>. Un buen cazador de phishing siempre mira bien la URL.</p>'
       . '<a class="btn btn-primary" href="' . e(url(current_user() ? 'dashboard.php' : 'index.php')) . '">' . icon('bxs-home') . ' Ir al inicio</a></section>';
    page_footer();
    exit;
}
[$script, $params] = $match;
foreach ($params as $k => $v) {
    $_GET[$k] = $v;
    $_REQUEST[$k] = $v;
}

$file = __DIR__ . '/' . $script;
$_SERVER['SCRIPT_FILENAME'] = $file;
$_SERVER['SCRIPT_NAME']     = $webRoot . '/' . $script;
$_SERVER['PHP_SELF']        = $_SERVER['SCRIPT_NAME'];
chdir(dirname($file));
require $file;
