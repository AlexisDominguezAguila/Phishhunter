<?php
/**
 * El registro libre está desactivado: las cuentas de estudiantes las crea
 * el docente desde Panel docente Estudiantes.
 */
require_once __DIR__ . '/includes/bootstrap.php';

flash('info', 'Las cuentas las crea tu docente. Pídele tu usuario y contraseña.');
redirect(current_user() ? 'dashboard.php' : 'login.php');
