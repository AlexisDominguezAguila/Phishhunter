<?php
/**
 * API de la Zona de refuerzo y la videoteca (JSON).
 *   action=start   game            -> crea la partida y devuelve los datos públicos
 *   action=flip    attempt token   -> memorama: voltea una carta
 *   action=match   attempt item links{L:R}  -> relacionar: corrige una ronda
 *   action=build   attempt item cards[]     -> arma tu clave: corrige una ronda
 *   action=office  attempt msg act          -> oficina: registra la decisión sobre un mensaje
 *   action=doc     attempt doc values{}     -> oficina: envía un documento
 *   action=finish  attempt         -> cierra la partida y reparte recompensas
 *   action=video_watched video
 *   action=video_answer  video answer
 */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
}
$user = require_login();
csrf_check();

if (!arcade_ready()) {
    json_response(['ok' => false, 'error' => 'La Zona de refuerzo aún no está instalada. El súper admin debe abrir la página «Actualizar» (/actualizar).'], 503);
}

$in     = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $in['action'] ?? '';
$uid    = (int) $user['id'];
if (!can_play($user)) {
    json_response(['ok' => false, 'error' => 'Tu grupo aún no tiene acceso a los juegos.'], 403);
}

function need_attempt(int $uid, array $in, ?string $type = null): array
{
    $att = arcade_load($uid, (int) ($in['attempt'] ?? 0));
    if (!$att || $att['status'] !== 'playing' || ($type && $att['type'] !== $type)) {
        json_response(['ok' => false, 'error' => 'La partida no está activa. Vuelve a empezar.'], 400);
    }
    return $att;
}

function out(array $r): void
{
    json_response($r, $r['ok'] ? 200 : 400);
}

try {
    switch ($action) {
        case 'start':
            $game = arcade_game_for($user, (string) ($in['game'] ?? ''));
            if (!$game || !$game['unlocked']) {
                json_response(['ok' => false, 'error' => 'Juego no disponible todavía.'], 403);
            }
            json_response(['ok' => true] + arcade_start($uid, $game));

        case 'flip':
            out(arcade_flip(need_attempt($uid, $in, 'memory'), (string) ($in['token'] ?? '')));

        case 'match':
            out(arcade_match_answer(need_attempt($uid, $in, 'match'), (int) ($in['item'] ?? 0), is_array($in['links'] ?? null) ? $in['links'] : []));

        case 'build':
            out(arcade_builder_answer(need_attempt($uid, $in, 'builder'), (int) ($in['item'] ?? 0), is_array($in['cards'] ?? null) ? $in['cards'] : []));

        case 'office':
            out(arcade_office_action(need_attempt($uid, $in, 'office'), (string) ($in['msg'] ?? ''), (string) ($in['act'] ?? '')));

        case 'doc':
            out(arcade_office_doc(need_attempt($uid, $in, 'office'), (string) ($in['doc'] ?? ''), is_array($in['values'] ?? null) ? $in['values'] : []));

        case 'finish':
            out(arcade_finish($uid, need_attempt($uid, $in)));

        case 'video_watched':
            out(video_watched($uid, (int) ($in['video'] ?? 0)));

        case 'video_answer':
            out(video_answer($uid, (int) ($in['video'] ?? 0), (int) ($in['answer'] ?? -1)));

        default:
            json_response(['ok' => false, 'error' => 'Acción desconocida'], 400);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => APP_DEBUG ? $e->getMessage() : 'Error interno'], 500);
}
