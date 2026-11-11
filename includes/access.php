<?php
/**
 * PhishHunter · Roles y alcance de datos
 *  superadmin -> administra docentes y ve todo; no tiene colegio, grupos ni estudiantes propios
 *                (registra estudiantes a nombre de un docente, en uno de sus grupos)
 *  admin      -> docente: solo ve y gestiona a sus propios estudiantes
 *  student    -> estudiante de un docente y de un grupo (experimental o control)
 */

require_once APP_ROOT . '/database/platform.php';

const ALL_GRADES = [1, 2, 3, 4, 5];
const SECTIONS   = ['A', 'B', 'C', 'D', 'E', 'F'];
const GROUP_TYPES = [
    'experimental' => 'Experimental (juega las misiones)',
    'control'      => 'Control (solo pretest y postest)',
];

function is_super(?array $u): bool
{
    return $u !== null && $u['role'] === 'superadmin';
}

function is_staff(?array $u): bool
{
    return $u !== null && in_array($u['role'], ['admin', 'superadmin'], true);
}

function role_label(array $u): string
{
    return ['superadmin' => 'Súper admin', 'admin' => 'Docente', 'student' => 'Estudiante'][$u['role']] ?? '';
}

/** Grados con los que trabaja un docente (por defecto, todos). */
function teacher_grades(array $teacher): array
{
    $g = array_values(array_filter(array_map('intval', explode(',', (string) ($teacher['grades'] ?? ''))), function ($x) {
        return in_array($x, ALL_GRADES, true);
    }));
    return $g ?: ALL_GRADES;
}

function grades_to_csv(array $grades): ?string
{
    $g = array_values(array_intersect(ALL_GRADES, array_map('intval', $grades)));
    return $g ? implode(',', $g) : null;
}

function grades_label(?string $csv): string
{
    if (!$csv) return '—';
    return implode(', ', array_map(function ($g) { return $g . '.º'; }, explode(',', $csv)));
}

function teacher_groups(int $teacherId): array
{
    return db_all('SELECT g.*, (SELECT COUNT(*) FROM users u WHERE u.group_id = g.id AND u.role = "student") AS students
                     FROM class_groups g WHERE g.teacher_id = ? ORDER BY g.id', [$teacherId]);
}

function student_group(array $student): ?array
{
    static $cache = [];
    $gid = (int) ($student['group_id'] ?? 0);
    if (!$gid) return null;
    if (!array_key_exists($gid, $cache)) {
        $cache[$gid] = db_one('SELECT * FROM class_groups WHERE id = ?', [$gid]);
    }
    return $cache[$gid];
}

function is_control_student(array $u): bool
{
    if ($u['role'] !== 'student') return false;
    $g = student_group($u);
    return $g !== null && $g['type'] === 'control';
}

/**
 * ¿Puede el estudiante usar la intervención gamificada (misiones, refuerzo, videos)?
 * El grupo control solo responde pretest y postest; al terminar el postest se le
 * abre el juego para que también aprenda (buena práctica ética).
 */
function can_play(array $u): bool
{
    if (is_staff($u)) return true;
    if (!is_control_student($u)) return true;
    return has_assessment((int) $u['id'], 'postest');
}

/** ¿El estudiante puede responder el postest? */
function postest_available(array $u): bool
{
    if (is_control_student($u)) {
        $g = student_group($u);
        return (bool) $g['postest_open'];
    }
    return all_missions_completed((int) $u['id']);
}

/**
 * Filtro SQL (sobre el alias «u») de los estudiantes que ve un miembro del personal.
 * Los valores son enteros validados, por eso pueden ir en el SQL.
 */
function student_scope_sql(array $viewer, ?int $teacherId = null, ?int $groupId = null, ?int $grade = null, ?string $groupType = null): string
{
    $w = " AND u.role = 'student'";
    if (is_super($viewer)) {
        if ($teacherId) $w .= ' AND u.teacher_id = ' . (int) $teacherId;
    } else {
        $w .= ' AND u.teacher_id = ' . (int) $viewer['id'];
    }
    if ($groupId) $w .= ' AND u.group_id = ' . (int) $groupId;
    if ($grade)   $w .= ' AND u.grade = ' . (int) $grade;
    if (in_array($groupType, ['experimental', 'control'], true)) {
        $w .= " AND u.group_id IN (SELECT id FROM class_groups WHERE type = '" . $groupType . "')";
    }
    return $w;
}

function can_manage_student(array $viewer, ?array $student): bool
{
    if (!$student || $student['role'] !== 'student') return false;
    return is_super($viewer) || (int) $student['teacher_id'] === (int) $viewer['id'];
}

/** Docentes (para filtros del súper admin). */
function all_teachers(): array
{
    return db_all("SELECT id, full_name, username, role, active, grades FROM users WHERE role IN ('admin','superadmin') ORDER BY role = 'superadmin' DESC, full_name");
}

function new_messages_count(): int
{
    try {
        return (int) db_val("SELECT COUNT(*) FROM messages WHERE status = 'nuevo'");
    } catch (Throwable $e) {
        return 0;
    }
}

/** Contraseña temporal fácil de dictar: Palabra-Palabra-NN */
function temp_password(): string
{
    $words = ['Mango', 'Nube', 'Condor', 'Rio', 'Cedro', 'Luna', 'Maiz', 'Puma', 'Sol', 'Lago', 'Faro', 'Tambor', 'Canoa', 'Cactus', 'Brujula', 'Volcan'];
    $a = $words[random_int(0, count($words) - 1)];
    do { $b = $words[random_int(0, count($words) - 1)]; } while ($b === $a);
    return $a . '-' . $b . '-' . random_int(10, 99);
}

/** Usuario a partir del nombre: primer nombre + primer apellido, sin tildes. Único. */
function make_username(string $fullName): string
{
    $clean = strtr(mb_strtolower(trim($fullName), 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $parts = array_values(array_filter(preg_split('/[^a-z0-9]+/', $clean)));
    if (!$parts) $parts = ['usuario'];
    // «Luis Alberto Chuquihuanga Ramos» -> luis.chuquihuanga (asume 2 nombres cuando hay 4 palabras)
    $first = $parts[0];
    $last  = $parts[count($parts) >= 4 ? 2 : (count($parts) - 1)] ?? '';
    $base  = substr($last && $last !== $first ? $first . '.' . $last : $first, 0, 26);
    $name = $base;
    $n = 2;
    while (db_val('SELECT 1 FROM users WHERE username = ?', [$name])) {
        $name = $base . $n++;
    }
    return $name;
}

/** Crea la cuenta de un estudiante (con 20 monedas de bienvenida). Devuelve sus credenciales. */
function create_student(int $teacherId, int $groupId, string $fullName, int $grade, string $section, ?string $username = null, ?string $password = null): array
{
    $username = $username ?: make_username($fullName);
    $password = $password ?: temp_password();
    db_exec('INSERT INTO users (username, full_name, password_hash, role, teacher_id, group_id, grade, section, avatar, coins) VALUES (?,?,?,?,?,?,?,?,?,?)', [
        $username, $fullName, password_hash($password, PASSWORD_DEFAULT), 'student', $teacherId, $groupId, $grade, $section, 'avatar-fox', 20,
    ]);
    $id = (int) db()->lastInsertId();
    db_exec('INSERT INTO xp_log (user_id, xp, coins, reason) VALUES (?,?,?,?)', [$id, 0, 20, 'Regalo de bienvenida']);
    return ['id' => $id, 'full_name' => $fullName, 'username' => $username, 'password' => $password, 'grade' => $grade, 'section' => $section];
}

/** Crea una cuenta de docente con contraseña temporal. Devuelve sus credenciales. */
function create_teacher(array $d): array
{
    $username = !empty($d['username']) ? $d['username'] : make_username($d['full_name']);
    $password = !empty($d['password']) ? $d['password'] : temp_password();
    db_exec('INSERT INTO users (username, full_name, email, phone, institution, password_hash, role, grades, avatar, must_change_password) VALUES (?,?,?,?,?,?,?,?,?,1)', [
        $username, $d['full_name'], $d['email'] ?: null, $d['phone'] ?: null, $d['institution'] ?: null,
        password_hash($password, PASSWORD_DEFAULT), 'admin', $d['grades'] ?: '3,4,5', 'avatar-owl',
    ]);
    $id = (int) db()->lastInsertId();
    ensure_default_group(db(), $id);
    return ['id' => $id, 'full_name' => $d['full_name'], 'username' => $username, 'password' => $password];
}

/** Texto listo para enviar al docente con sus credenciales. */
function teacher_welcome_text(string $name, string $username, string $password): string
{
    $link = (isset($_SERVER['HTTP_HOST']) ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : '') . url('login.php');
    return "Hola, " . $name . ":\n\n"
        . "Tu cuenta de docente en " . APP_NAME . " ya está activa.\n"
        . "Ingresa en: " . $link . "\n"
        . "Usuario: " . $username . "\n"
        . "Contraseña temporal: " . $password . "\n\n"
        . "Al ingresar por primera vez te pediremos cambiar la contraseña. Desde tu panel podrás crear tus grupos (experimental o control) y registrar a tus estudiantes.\n\n"
        . "Saludos.";
}

/** Número para WhatsApp (Perú por defecto: 9 dígitos que empiezan en 9 → +51). */
function wa_number(?string $phone): string
{
    $d = preg_replace('/\D+/', '', (string) $phone);
    if (strlen($d) === 9 && $d[0] === '9') $d = '51' . $d;
    return $d;
}

/** Caja para copiar la respuesta y enviarla por correo o WhatsApp. */
function share_box(string $text, ?string $email, ?string $phone, string $subject): string
{
    $id = 'share-' . bin2hex(random_bytes(3));
    $h  = '<div class="share-box"><label class="field-label" for="' . $id . '">Mensaje listo para enviar</label>';
    $h .= '<textarea id="' . $id . '" rows="8" readonly>' . e($text) . '</textarea><div class="share-actions">';
    $h .= '<button type="button" class="btn btn-soft btn-sm" data-copy="' . $id . '">' . icon('bx-copy') . ' Copiar</button>';
    if ($email) {
        $h .= '<a class="btn btn-soft btn-sm" href="mailto:' . e($email) . '?subject=' . rawurlencode($subject) . '&amp;body=' . rawurlencode($text) . '">' . icon('bxs-envelope') . ' Enviar por correo</a>';
    }
    if (wa_number($phone)) {
        $h .= '<a class="btn btn-soft btn-sm btn-wa" target="_blank" rel="noopener" href="https://wa.me/' . e(wa_number($phone)) . '?text=' . rawurlencode($text) . '">' . icon('bxl-whatsapp') . ' Enviar por WhatsApp</a>';
    }
    return $h . '</div><p class="muted small">El sistema no envía correos por sí solo: usa estos botones para mandarlo desde tu correo o tu WhatsApp.</p></div>';
}

/**
 * Institución que se muestra en el pie de página:
 *  - súper admin y visitantes: ninguna (la plataforma es de todos)
 *  - docente: la suya
 *  - estudiante: la de su docente
 */
function footer_institution(?array $u): string
{
    if (!$u || is_super($u)) return '';
    if ($u['role'] === 'admin') return trim((string) ($u['institution'] ?? ''));
    static $cache = [];
    $tid = (int) ($u['teacher_id'] ?? 0);
    if (!$tid) return '';
    if (!array_key_exists($tid, $cache)) {
        $cache[$tid] = trim((string) db_val('SELECT institution FROM users WHERE id = ?', [$tid]));
    }
    return $cache[$tid];
}
