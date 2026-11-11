<?php
/**
 * PhishHunter · URLs amigables
 *
 * Tabla única de rutas: la usan url()/redirect() para generar enlaces
 * («/refuerzo», «/clasificacion») y router.php para atenderlos.
 *
 *  - Apache (WAMP, la mayoría de hostings): el .htaccess de la raíz envía a
 *    router.php todo lo que no sea un archivo real.
 *  - Nginx: `try_files $uri $uri/ /router.php?$query_string;`
 *  - Si el servidor no reescribe URLs, la app sigue funcionando con «.php».
 *
 * En config/config.php puedes forzarlo: define('PRETTY_URLS', true | false);
 * Por defecto ('auto') se activa solo si el .htaccess está funcionando.
 */

if (!defined('PRETTY_URLS')) {
    define('PRETTY_URLS', 'auto');
}

/** Ruta amigable => script. «{param}» toma un segmento y lo pasa como $_GET[param]. */
function routes(): array
{
    return [
        ''                   => 'index.php',
        'ingresar'           => 'login.php',
        'salir'              => 'logout.php',
        'registro'           => 'register.php',
        'soy-docente'        => 'teacher_request.php',
        'misiones'           => 'dashboard.php',
        'mision/{m}'         => 'mission.php',
        'jugar/{g}'          => 'play.php',
        'evaluacion/{type}'  => 'evaluation.php',
        'evaluacion'         => 'evaluation.php',
        'refuerzo/{g}'       => 'arcade_play.php',
        'refuerzo'           => 'arcade.php',
        'videos'             => 'videos.php',
        'clasificacion'      => 'leaderboard.php',
        'recompensas'        => 'rewards.php',
        'progreso/{id}'      => 'profile.php',
        'progreso'           => 'profile.php',
        // Panel del docente
        'panel'              => 'admin/index.php',
        'panel/estudiantes'  => 'admin/students.php',
        'panel/grupos'       => 'admin/groups.php',
        'panel/mensajes'     => 'admin/messages.php',
        'panel/exportar'     => 'admin/export.php',
        'panel/importar'     => 'admin/import.php',
        // Súper admin
        'panel/docentes'     => 'super/index.php',
        'panel/bandeja'      => 'super/inbox.php',
        'panel/respaldo'     => 'super/backup.php',
        // Instalación y mantenimiento
        'instalar'           => 'install.php',
        'actualizar'         => 'upgrade.php',
        // API (JSON)
        'api/juego'          => 'api/game.php',
        'api/refuerzo'       => 'api/arcade.php',
    ];
}

/** ¿Se generan URLs amigables? */
function pretty_urls(): bool
{
    static $on = null;
    if ($on !== null) return $on;
    if (PRETTY_URLS !== 'auto') return $on = (bool) PRETTY_URLS;
    if (PHP_SAPI === 'cli-server') return $on = true;       // php -S con router.php
    if (defined('PH_ROUTED')) return $on = true;            // la petición ya llegó por una URL amigable
    // El .htaccess marca cada petición cuando mod_rewrite está activo.
    return $on = !empty($_SERVER['PH_PRETTY']) || !empty($_SERVER['REDIRECT_PH_PRETTY']);
}

/**
 * Convierte «mission.php?m=slug&x=1#top» en «mision/slug?x=1#top».
 * Devuelve null si el script no tiene ruta amigable.
 */
function pretty_path(string $path): ?string
{
    $hash = '';
    if (($p = strpos($path, '#')) !== false) { $hash = substr($path, $p); $path = substr($path, 0, $p); }
    $query = '';
    if (($p = strpos($path, '?')) !== false) { $query = substr($path, $p + 1); $path = substr($path, 0, $p); }
    $script = ltrim($path, '/');
    if ($script === '') $script = 'index.php';
    if (substr($script, -4) !== '.php') return null;

    parse_str($query, $params);
    $best = null;
    foreach (routes() as $route => $target) {
        if ($target !== $script) continue;
        if (preg_match_all('/\{(\w+)\}/', $route, $m)) {
            // Ruta con parámetros: solo si la consulta los trae todos (y son simples)
            $ok = true;
            foreach ($m[1] as $name) {
                if (!isset($params[$name]) || !is_scalar($params[$name]) || $params[$name] === '' || strpbrk((string) $params[$name], "/\\") !== false) { $ok = false; break; }
            }
            if (!$ok) continue;
            $out = $route;
            foreach ($m[1] as $name) {
                $out = str_replace('{' . $name . '}', rawurlencode((string) $params[$name]), $out);
                unset($params[$name]);
            }
            $best = $out;
            break;
        }
        if ($best === null) $best = $route;
    }
    if ($best === null) return null;
    $qs = $params ? '?' . http_build_query($params) : '';
    return $best . $qs . $hash;
}

/**
 * Busca la ruta amigable de la petición. Devuelve [script, parámetros] o null.
 */
function match_route(string $path): ?array
{
    $path = trim(rawurldecode($path), '/');
    foreach (routes() as $route => $target) {
        $re = '#^' . preg_replace('/\\\\\{(\w+)\\\\\}/', '(?P<$1>[^/]+)', preg_quote($route, '#')) . '$#u';
        if (preg_match($re, $path, $m)) {
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return [$target, $params];
        }
    }
    return null;
}

/**
 * Si alguien abre la dirección antigua con «.php» (marcador, enlace viejo)
 * y las URLs amigables están activas, lo lleva a la nueva (solo GET).
 */
function redirect_legacy_php(): void
{
    if (PHP_SAPI === 'cli' || defined('PH_ROUTED') || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || !pretty_urls()) return;
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if (substr($uriPath, -4) !== '.php') return;
    $root = str_replace('\\', '/', realpath(APP_ROOT));
    $file = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    if ($file === '' || strpos($file, $root . '/') !== 0) return;
    $rel = substr($file, strlen($root) + 1);
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    $pretty = pretty_path($rel . ($qs !== '' ? '?' . $qs : ''));
    if ($pretty === null) return;
    header('Location: ' . base_url() . '/' . $pretty, true, 301);
    exit;
}
