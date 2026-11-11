<?php
/**
 * Seeder: cuenta del súper administrador.
 *  - admin / admin123 (rol súper admin: administra docentes; no tiene colegio ni estudiantes propios)
 *    Cámbiala desde «Mi progreso» después de instalar.
 * Los docentes se crean desde el panel Súper admin; los estudiantes, desde el panel de cada docente
 * (o el súper admin los registra a nombre de un docente, eligiendo su grupo).
 */
return function (PDO $pdo): void {
    $pdo->prepare('INSERT INTO users (username, full_name, email, password_hash, role, avatar) VALUES (?,?,?,?,?,?)')
        ->execute(['admin', 'Administrador PhishHunter', null, password_hash('admin123', PASSWORD_DEFAULT), 'superadmin', 'avatar-owl']);
};
