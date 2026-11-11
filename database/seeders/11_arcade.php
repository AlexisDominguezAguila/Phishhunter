<?php
/**
 * Seeder: Zona de refuerzo (videoteca de YouTube, memoramas, relacionar,
 * arma tu clave y simulador de oficina). El contenido está en
 * database/arcade_content.php
 */
return function (PDO $pdo): void {
    arcade_install($pdo);
};
