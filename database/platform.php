<?php
/**
 * PhishHunter · Plataforma multi-docente
 *  - Roles: superadmin (administra docentes), admin (docente), student.
 *  - Cada estudiante pertenece a un docente y a un grupo (experimental o control).
 *  - Grados de 1.º a 5.º de secundaria (cada docente elige con cuáles trabaja).
 *  - Mensajes: solicitudes de cuenta de docentes nuevos y mensajes de docentes al súper admin.
 *
 * platform_schema() es idempotente: sirve para instalaciones nuevas y para
 * actualizar una base existente SIN borrar datos.
 */

function col_exists(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $st->execute([$table, $col]);
    return (int) $st->fetchColumn() > 0;
}

function platform_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS class_groups (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        teacher_id    INT UNSIGNED NOT NULL,
        name          VARCHAR(80) NOT NULL,
        type          ENUM('experimental','control') NOT NULL DEFAULT 'experimental',
        description   VARCHAR(255) NULL,
        postest_open  TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Grupo control: el docente habilita el postest',
        created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_groups_teacher (teacher_id),
        CONSTRAINT fk_groups_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        kind            ENUM('solicitud','mensaje') NOT NULL,
        from_user_id    INT UNSIGNED NULL,
        name            VARCHAR(120) NOT NULL,
        email           VARCHAR(150) NULL,
        phone           VARCHAR(30) NULL,
        institution     VARCHAR(150) NULL,
        grades          VARCHAR(20) NULL,
        subject         VARCHAR(150) NOT NULL,
        body            TEXT NOT NULL,
        status          ENUM('nuevo','leido','respondido','archivado') NOT NULL DEFAULT 'nuevo',
        reply           TEXT NULL,
        replied_at      DATETIME NULL,
        created_user_id INT UNSIGNED NULL COMMENT 'Docente creado a partir de la solicitud',
        ip              VARCHAR(45) NULL,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_messages_status (status, created_at),
        CONSTRAINT fk_messages_from FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Usuarios: rol súper admin, dueño (docente), grupo, estado y datos de contacto
    $pdo->exec("ALTER TABLE users MODIFY `role` ENUM('student','admin','superadmin') NOT NULL DEFAULT 'student'");
    $pdo->exec("ALTER TABLE users MODIFY grade TINYINT UNSIGNED NULL COMMENT '1.º a 5.º de secundaria'");
    $cols = [
        'teacher_id'   => "ADD COLUMN teacher_id INT UNSIGNED NULL AFTER `role`",
        'group_id'     => "ADD COLUMN group_id INT UNSIGNED NULL AFTER teacher_id",
        'active'       => "ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER group_id",
        'must_change_password' => "ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER active",
        'phone'        => "ADD COLUMN phone VARCHAR(30) NULL AFTER email",
        'institution'  => "ADD COLUMN institution VARCHAR(150) NULL AFTER phone",
        'grades'       => "ADD COLUMN grades VARCHAR(20) NULL COMMENT 'Docente: grados con los que trabaja (p. ej. 3,4,5)' AFTER section",
    ];
    foreach ($cols as $col => $sql) {
        if (!col_exists($pdo, 'users', $col)) {
            $pdo->exec("ALTER TABLE users $sql");
        }
    }
    try { $pdo->exec('ALTER TABLE users ADD INDEX idx_users_teacher (teacher_id)'); } catch (Throwable $e) { /* ya existe */ }
    try { $pdo->exec('ALTER TABLE users ADD INDEX idx_users_group (group_id)'); } catch (Throwable $e) { /* ya existe */ }

    // Instalaciones anteriores: el único docente pasa a ser súper admin y dueño de los estudiantes existentes.
    $hasSuper = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin'")->fetchColumn();
    if (!$hasSuper) {
        $first = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetchColumn();
        if ($first) {
            $pdo->prepare("UPDATE users SET role = 'superadmin' WHERE id = ?")->execute([$first]);
        }
    }
    $super = $pdo->query("SELECT id FROM users WHERE role = 'superadmin' ORDER BY id LIMIT 1")->fetchColumn();
    if ($super) {
        $pdo->prepare("UPDATE users SET teacher_id = ? WHERE role = 'student' AND teacher_id IS NULL")->execute([$super]);
    }
    // El súper admin no tiene colegio ni grupos propios: se quitan su institución y sus grupos vacíos.
    // (Si aún tiene estudiantes de una versión anterior, sus grupos se conservan hasta moverlos a un docente.)
    if ($super) {
        $pdo->prepare('UPDATE users SET institution = NULL WHERE id = ?')->execute([$super]);
        $pdo->prepare("DELETE FROM class_groups WHERE teacher_id = ? AND id NOT IN (SELECT gid FROM (SELECT DISTINCT group_id AS gid FROM users WHERE group_id IS NOT NULL) x)")->execute([$super]);
    }
    // Todo docente con estudiantes sin grupo recibe un «Grupo experimental» por defecto.
    $rows = $pdo->query("SELECT DISTINCT teacher_id FROM users WHERE role = 'student' AND group_id IS NULL AND teacher_id IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $tid) {
        $gid = ensure_default_group($pdo, (int) $tid);
        $pdo->prepare("UPDATE users SET group_id = ? WHERE role = 'student' AND teacher_id = ? AND group_id IS NULL")->execute([$gid, $tid]);
    }
}

/** Devuelve el primer grupo del docente; si no tiene, crea «Grupo experimental». */
function ensure_default_group(PDO $pdo, int $teacherId): int
{
    $st = $pdo->prepare('SELECT id FROM class_groups WHERE teacher_id = ? ORDER BY id LIMIT 1');
    $st->execute([$teacherId]);
    $gid = $st->fetchColumn();
    if ($gid) return (int) $gid;
    $pdo->prepare("INSERT INTO class_groups (teacher_id, name, type, description) VALUES (?,?,?,?)")
        ->execute([$teacherId, 'Grupo experimental', 'experimental', 'Juega las misiones gamificadas (intervención).']);
    return (int) $pdo->lastInsertId();
}
