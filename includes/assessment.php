<?php
/**
 * PhishHunter · Evaluación (pretest / postest / valoración)
 * Puntajes e intervalos según la Tabla 5.1 de la tesis.
 */

const PREVENTION_DIMENSIONS = [
    'reconocimiento' => ['label' => 'Reconocimiento de señales',       'items' => 8, 'ranges' => [[8, 18], [19, 29], [30, 40]]],
    'decisiones'     => ['label' => 'Toma de decisiones seguras',      'items' => 9, 'ranges' => [[9, 20], [21, 33], [34, 45]]],
    'proteccion'     => ['label' => 'Prácticas de protección digital', 'items' => 7, 'ranges' => [[7, 16], [17, 25], [26, 35]]],
];
const PREVENTION_GLOBAL = [[24, 55], [56, 88], [89, 120]];

const GAMIFICATION_DIMENSIONS = [
    'motivacion'  => ['label' => 'Motivación e involucramiento',     'items' => 3, 'ranges' => [[3, 7], [8, 11], [12, 15]]],
    'diseno'      => ['label' => 'Diseño y usabilidad',              'items' => 3, 'ranges' => [[3, 7], [8, 11], [12, 15]]],
    'aprendizaje' => ['label' => 'Aprendizaje y utilidad percibidos','items' => 3, 'ranges' => [[3, 7], [8, 11], [12, 15]]],
];
const GAMIFICATION_GLOBAL = [[9, 21], [22, 33], [34, 45]];

const FREQUENCY_SCALE = [1 => 'Nunca', 2 => 'Casi nunca', 3 => 'A veces', 4 => 'Casi siempre', 5 => 'Siempre'];
const AGREEMENT_SCALE = [1 => 'Totalmente en desacuerdo', 2 => 'En desacuerdo', 3 => 'Ni de acuerdo ni en desacuerdo', 4 => 'De acuerdo', 5 => 'Totalmente de acuerdo'];

function level_from_ranges(int $score, array $ranges): string
{
    if ($score <= $ranges[0][1]) return 'Bajo';
    if ($score <= $ranges[1][1]) return 'Medio';
    return 'Alto';
}

function survey_items(string $instrument): array
{
    return db_all('SELECT * FROM survey_items WHERE instrument = ? ORDER BY number', [$instrument]);
}

/**
 * Guarda un cuestionario completo. $answers = [item_id => valor 1..5].
 * Devuelve el id de la evaluación o lanza excepción si falta algún ítem.
 */
function save_assessment(int $userId, string $type, array $answers): int
{
    $instrument = $type === 'valoracion' ? 'gamification' : 'prevention';
    $items = survey_items($instrument);
    $dims  = $instrument === 'gamification' ? GAMIFICATION_DIMENSIONS : PREVENTION_DIMENSIONS;
    $keys  = array_keys($dims);
    $sums  = array_fill_keys($keys, 0);

    foreach ($items as $it) {
        $v = (int) ($answers[$it['id']] ?? 0);
        if ($v < 1 || $v > 5) {
            throw new InvalidArgumentException('Responde todas las preguntas antes de enviar.');
        }
        $sums[$it['dimension']] += $v;
    }
    $total = array_sum($sums);
    $level = level_from_ranges($total, $instrument === 'gamification' ? GAMIFICATION_GLOBAL : PREVENTION_GLOBAL);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        db_exec('INSERT INTO assessments (user_id, type, total, dim1, dim2, dim3, level) VALUES (?,?,?,?,?,?,?)',
            [$userId, $type, $total, $sums[$keys[0]], $sums[$keys[1]], $sums[$keys[2]], $level]);
        $id = (int) $pdo->lastInsertId();
        $st = $pdo->prepare('INSERT INTO assessment_answers (assessment_id, item_id, value) VALUES (?,?,?)');
        foreach ($items as $it) {
            $st->execute([$id, $it['id'], (int) $answers[$it['id']]]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return $id;
}

function get_assessment(int $userId, string $type): ?array
{
    return db_one('SELECT * FROM assessments WHERE user_id = ? AND type = ?', [$userId, $type]);
}

/** Resumen por dimensión con nivel (para perfil y panel docente). */
function assessment_breakdown(?array $a, string $instrument = 'prevention'): array
{
    if (!$a) return [];
    $dims = $instrument === 'gamification' ? GAMIFICATION_DIMENSIONS : PREVENTION_DIMENSIONS;
    $out = [];
    $i = 1;
    foreach ($dims as $key => $d) {
        $score = (int) $a['dim' . $i++];
        $out[$key] = [
            'label'    => $d['label'],
            'score'    => $score,
            'max'      => $d['items'] * 5,
            'min'      => $d['items'],
            'per_item' => round($score / $d['items'], 2),
            'level'    => level_from_ranges($score, $d['ranges']),
        ];
    }
    return $out;
}

/** Rendimiento objetivo en las mini-pruebas y juegos, agrupado por dimensión. */
function learning_by_dimension(int $userId): array
{
    $rows = db_all(
        'SELECT COALESCE(gi.dimension, g.dimension) AS dim, g.is_quiz,
                COUNT(*) AS answered, SUM(aa.points) AS points
           FROM attempt_answers aa
           JOIN game_attempts a ON a.id = aa.attempt_id
           JOIN game_items gi ON gi.id = aa.item_id
           JOIN games g ON g.id = a.game_id
          WHERE a.user_id = ?
       GROUP BY dim, g.is_quiz', [$userId]);
    $out = [];
    foreach (['reconocimiento', 'decisiones', 'proteccion'] as $d) {
        $out[$d] = ['label' => dimension_label($d), 'quiz' => null, 'games' => null, 'quiz_n' => 0, 'games_n' => 0];
    }
    foreach ($rows as $r) {
        if (!isset($out[$r['dim']])) continue;
        $pct = (int) round((float) $r['points'] / max(1, (int) $r['answered']) * 100);
        if ((int) $r['is_quiz'] === 1) {
            $out[$r['dim']]['quiz'] = $pct;
            $out[$r['dim']]['quiz_n'] = (int) $r['answered'];
        } else {
            $out[$r['dim']]['games'] = $pct;
            $out[$r['dim']]['games_n'] = (int) $r['answered'];
        }
    }
    return $out;
}
