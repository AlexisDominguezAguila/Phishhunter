<?php
/**
 * Misión 5 · Protege tus cuentas  (Sesión 5)
 * Dimensión: prácticas de protección digital (ítems 17–24).
 */
return function (PDO $pdo): void {
    $m = seed_mission($pdo, [
        'slug' => 'protege-tus-cuentas', 'order' => 5, 'icon' => 'bxs-shield', 'color' => '#E0457B',
        'dimension' => 'proteccion',
        'title' => 'Protege tus cuentas',
        'subtitle' => 'Contraseñas fuertes, doble candado y buenos hábitos',
        'description' => 'Aunque alguien intente engañarte, unas buenas defensas hacen que el ataque fracase: contraseñas largas y distintas, verificación en dos pasos, códigos privados, equipos actualizados y cerrar sesión.',
        'objectives' => [
            'Crear contraseñas largas, únicas y fáciles de recordar (frases de paso).',
            'Explicar para qué sirve la verificación en dos pasos y por qué los códigos no se comparten.',
            'Diferenciar prácticas seguras y riesgosas en el uso diario de dispositivos.',
        ],
        'lesson' => [
            ['icon' => 'bxs-message-dots', 'title' => 'Frases de paso', 'text' => 'Una contraseña larga es más fuerte que una corta con símbolos raros. Ejemplo de idea: 4 palabras sin relación + un número y un signo, como «Mango-Nube-Charango-Rio7!». (¡No uses este ejemplo!)'],
            ['icon' => 'bxs-key', 'title' => 'Una llave por puerta', 'text' => 'Usa contraseñas distintas para cada cuenta. Si roban una, las demás siguen seguras. Un gestor de contraseñas puede ayudarte a recordarlas.'],
            ['icon' => 'bxs-lock', 'title' => 'Doble candado (2FA)', 'text' => 'La verificación en dos pasos pide algo más que la contraseña: un código o una confirmación en tu celular. Así, aunque roben tu clave, no pueden entrar.'],
            ['icon' => 'bxs-lock', 'title' => 'Códigos: solo para ti', 'text' => 'Los códigos que llegan por SMS o correo son personales. Nadie legítimo (banco, soporte, docente, administrador) te los pedirá.'],
            ['icon' => 'bx-refresh', 'title' => 'Actualiza y cierra sesión', 'text' => 'Las actualizaciones corrigen fallas de seguridad. En computadoras compartidas (sala de cómputo, cabinas) cierra sesión y no guardes contraseñas en el navegador.'],
        ],
        'xp' => 120, 'coins' => 35,
    ]);

    $ch = function ($d, $q, $options, $answer, $feedback, $e) {
        return ['d' => $d, 'c' => ['question' => $q, 'options' => $options], 's' => ['answer' => $answer, 'feedback' => $feedback], 'e' => $e];
    };

    seed_game($pdo, $m, [
        'slug' => 'forja-de-claves', 'type' => 'choice', 'order' => 1, 'icon' => 'bxs-lock',
        'dimension' => 'proteccion', 'per_play' => 6, 'pass' => 60,
        'title' => 'Forja de claves',
        'description' => 'Elige las contraseñas y hábitos que mejor protegen tus cuentas.',
        'instructions' => 'Primero prueba el medidor de fuerza en el taller (¡no escribas tu contraseña real!). Luego elige la opción más segura en cada reto.',
        'items' => [
            $ch(1, '¿Cuál de estas contraseñas es la más segura?', ['123456', 'maria2010', 'Tortuga-Lapiz-Volcan-Mate9', 'qwerty!'], 2,
                ['Es la contraseña más usada del mundo: se adivina en menos de un segundo.', 'Nombre + año son datos fáciles de adivinar mirando tus redes.', '¡Correcto! Es larga, combina palabras sin relación, número y signos.', 'Es corta y sigue un patrón del teclado.'],
                'La longitud es lo que más fuerza aporta. Las frases de paso son largas y fáciles de recordar para ti, pero difíciles de adivinar.'),
            $ch(1, '¿Qué dato NO deberías usar nunca en tu contraseña?', ['Una palabra inventada', 'Tu fecha de nacimiento o el nombre de tu mascota', 'Varias palabras sin relación', 'Un signo como ! o #'], 1,
                ['Las palabras inventadas pueden ayudar.', '¡Exacto! Son datos que otros pueden conocer o encontrar en tus redes.', 'Es una buena técnica.', 'Los signos suman variedad.'],
                'Evita datos personales que aparezcan en redes sociales: cumpleaños, nombres, equipo de fútbol, mascota.'),
            $ch(1, 'Usas la misma contraseña en tu correo, juego y red social. Si roban la del juego…', ['Solo pierdes el juego', 'Pueden entrar también a tu correo y red social', 'No pasa nada', 'Se bloquea todo automáticamente'], 1,
                ['Los atacantes prueban la misma clave en otros servicios.', '¡Correcto! Por eso cada cuenta necesita su propia contraseña.', 'Sí pasa: reutilizar claves es muy riesgoso.', 'No existe tal bloqueo automático.'],
                'Una llave por puerta: contraseñas distintas evitan el efecto dominó (ítem 17).'),
            $ch(2, '¿Qué hace la verificación en dos pasos (2FA)?', ['Hace que tu clave sea más corta', 'Pide una segunda prueba (código o confirmación en tu celular) además de la contraseña', 'Borra tus mensajes', 'Te avisa de los cumpleaños'], 1,
                ['No cambia la contraseña.', '¡Bien! Aunque alguien sepa tu contraseña, sin el segundo paso no puede entrar.', 'No tiene relación.', 'No tiene relación.'],
                'Activa la verificación en dos pasos en WhatsApp, Instagram, Google y tus juegos (ítem 18).'),
            $ch(2, 'Llega un SMS con un código de verificación que TÚ no pediste. ¿Qué significa?', ['Que ganaste algo', 'Que alguien podría estar intentando entrar a tu cuenta', 'Que tu celular está roto', 'Nada importante'], 1,
                ['No es un premio.', '¡Correcto! No compartas ese código y cambia tu contraseña si sospechas.', 'No es un fallo del equipo.', 'Es una alerta que conviene atender.'],
                'Un código que no pediste es una alarma: alguien tiene tu usuario o contraseña. Cambia la clave y mantén el código en privado (ítem 19).'),
            $ch(2, 'Terminaste de usar tu cuenta en la computadora de la sala de cómputo. ¿Qué haces?', ['Cierro la pestaña y me voy', 'Cierro sesión y no guardo la contraseña en el navegador', 'Dejo la sesión abierta para la próxima clase', 'Apago el monitor'], 1,
                ['Cerrar la pestaña no siempre cierra la sesión.', '¡Muy bien! Así el siguiente estudiante no puede entrar a tu cuenta.', 'Cualquiera podría usar tu cuenta.', 'La computadora sigue encendida con tu cuenta abierta.'],
                'En equipos compartidos: cerrar sesión siempre y rechazar «¿Guardar contraseña?» (ítem 23).'),
            $ch(3, 'Tu celular muestra «Actualización de seguridad disponible». ¿Qué conviene?', ['Ignorarla para siempre', 'Instalarla desde la configuración oficial del sistema cuando tengas wifi y batería', 'Descargar la actualización de un enlace que me mandaron', 'Desinstalar las apps'], 1,
                ['Sin actualizar, las fallas conocidas quedan abiertas para los atacantes.', '¡Correcto! Las actualizaciones corrigen agujeros de seguridad.', 'Las actualizaciones nunca llegan por enlaces en mensajes: eso sería un engaño.', 'No es necesario.'],
                'Mantén el sistema, las apps y el navegador actualizados desde las tiendas y ajustes oficiales (ítem 21).'),
            $ch(3, 'Alguien que dice ser «administrador de la plataforma del colegio» te pide tu contraseña para «migrar tu cuenta». ¿Qué respondes?', ['Se la doy porque es administrador', 'No la doy: un administrador real no necesita mi contraseña. Lo informo al docente', 'Le doy una pista', 'Le doy la contraseña y luego la cambio'], 1,
                ['Un administrador real tiene herramientas propias; jamás necesita tu clave.', '¡Excelente! Resistir a la «autoridad» es clave. Reportarlo protege a todos.', 'Una pista les ayuda a adivinarla.', 'Pueden cambiarla antes que tú y dejarte fuera.'],
                'Ni soporte técnico, ni docentes, ni administradores necesitan tu contraseña (ítem 22). Este fue el ítem con menor puntaje en el estudio: ¡practícalo!'),
        ],
    ]);

    $card = function ($d, $icon, $text, $answer, $e) {
        return ['d' => $d, 'c' => ['icon' => $icon, 'card' => $text], 's' => ['answer' => $answer], 'e' => $e];
    };

    seed_game($pdo, $m, [
        'slug' => 'seguro-o-riesgoso', 'type' => 'sort', 'order' => 2, 'icon' => 'bxs-folder-open',
        'dimension' => 'proteccion', 'per_play' => 10, 'pass' => 60,
        'title' => 'Seguro o riesgoso',
        'description' => 'Clasifica hábitos digitales en la canasta correcta.',
        'instructions' => 'Arrastra cada tarjeta a «Seguro» o «Riesgoso», o usa los botones. Piensa en lo que harías en tu día a día.',
        'items' => [
            $card(1, 'bxs-lock', 'Activar la verificación en dos pasos en WhatsApp', 'seguro', 'Añade un PIN extra: aunque tengan tu código SMS, no pueden registrar tu cuenta.'),
            $card(1, 'bx-repeat', 'Usar la misma contraseña para todo', 'riesgoso', 'Si se filtra una, caen todas tus cuentas.'),
            $card(1, 'bx-log-out', 'Cerrar sesión al terminar en la sala de cómputo', 'seguro', 'Evita que el siguiente usuario entre a tu cuenta.'),
            $card(1, 'bxs-lock', 'Compartir el código SMS con un amigo que lo pide', 'riesgoso', 'El código es la llave de tu cuenta; probablemente la cuenta de tu amigo fue robada.'),
            $card(1, 'bx-refresh', 'Instalar las actualizaciones del sistema', 'seguro', 'Corrigen fallas de seguridad conocidas.'),
            $card(2, 'bxs-save', 'Aceptar «Guardar contraseña» en una computadora pública', 'riesgoso', 'Cualquiera que use después la computadora podría entrar a tu cuenta.'),
            $card(2, 'bxs-megaphone', 'Reportar un mensaje de phishing en la app y avisar al docente', 'seguro', 'Protege a tus compañeros y ayuda a bloquear al atacante.'),
            $card(2, 'bx-wifi', 'Entrar a tu banco usando el wifi gratis de una plaza sin verificar la página', 'riesgoso', 'En redes públicas y sin revisar la dirección, aumenta el riesgo de páginas falsas.'),
            $card(2, 'bxs-message-dots', 'Crear una frase de paso larga con palabras sin relación', 'seguro', 'La longitud hace que la contraseña sea muy difícil de adivinar.'),
            $card(2, 'bxs-mobile', 'Instalar apps .apk que te mandan por WhatsApp', 'riesgoso', 'Pueden contener malware que roba códigos y mensajes. Usa solo tiendas oficiales.'),
            $card(3, 'bx-search-alt', 'Revisar la dirección completa de la página antes de escribir la contraseña', 'seguro', 'Es la última barrera antes de entregar tus datos a una página falsa.'),
            $card(3, 'bxs-camera', 'Publicar una foto de tu DNI o de tu tarjeta en redes', 'riesgoso', 'Esos datos sirven para suplantarte o para ataques dirigidos.'),
            $card(3, 'bxs-user', 'Compartir consejos de seguridad con tu familia', 'seguro', 'Difundir buenas prácticas protege a quienes te rodean (ítem 24).'),
            $card(3, 'bxs-user', 'Dar tu contraseña a «soporte técnico» que te escribe por chat', 'riesgoso', 'El soporte real nunca pide contraseñas.'),
            $card(3, 'bxs-lock-alt', 'Bloquear el celular con PIN o huella', 'seguro', 'Si pierdes el celular, protege tus apps y códigos.'),
        ],
    ]);

    seed_game($pdo, $m, [
        'slug' => 'prueba-5', 'type' => 'quiz', 'order' => 3, 'icon' => 'bxs-edit',
        'dimension' => 'proteccion', 'per_play' => 5, 'pass' => 60,
        'title' => 'Mini-prueba: protección de cuentas',
        'description' => 'Comprueba tus defensas digitales.',
        'instructions' => 'Elige la respuesta correcta.',
        'items' => [
            ['d' => 1, 'dim' => 'proteccion', 'c' => ['question' => '¿Qué característica hace MÁS fuerte a una contraseña?', 'options' => ['Que sea tu nombre', 'Que sea larga (por ejemplo, una frase de varias palabras)', 'Que sea corta para recordarla', 'Que sea 12345678']],
             's' => ['answer' => 1], 'e' => 'La longitud es el factor que más aumenta la fuerza de una contraseña.'],
            ['d' => 1, 'dim' => 'proteccion', 'c' => ['question' => '¿Quién puede pedirte legítimamente tu código de verificación?', 'options' => ['El banco', 'Soporte técnico', 'Un amigo', 'Nadie']],
             's' => ['answer' => 3], 'e' => 'El código es solo para ti. Nadie legítimo te lo pedirá.'],
            ['d' => 2, 'dim' => 'proteccion', 'c' => ['question' => '¿Para qué sirve la verificación en dos pasos?', 'options' => ['Para que la cuenta sea más rápida', 'Para que, aunque roben tu contraseña, no puedan entrar sin el segundo factor', 'Para tener dos cuentas', 'Para recordar la contraseña']],
             's' => ['answer' => 1], 'e' => 'Es un segundo candado. Actívalo en todas las cuentas que lo permitan.'],
            ['d' => 2, 'dim' => 'proteccion', 'c' => ['question' => 'En una computadora compartida, al terminar debes…', 'options' => ['Cerrar sesión y no guardar contraseñas', 'Dejar todo abierto', 'Solo minimizar la ventana', 'Cambiar el fondo de pantalla']],
             's' => ['answer' => 0], 'e' => 'Cerrar sesión evita que otra persona use tu cuenta.'],
            ['d' => 2, 'dim' => 'proteccion', 'c' => ['question' => '¿Por qué es importante actualizar el celular y el navegador?', 'options' => ['Para cambiar los colores', 'Porque corrigen fallas de seguridad que los atacantes aprovechan', 'No es importante', 'Para tener más fotos']],
             's' => ['answer' => 1], 'e' => 'Las actualizaciones cierran puertas que los atacantes conocen.'],
            ['d' => 3, 'dim' => 'proteccion', 'c' => ['question' => 'Una persona dice ser administrador del sistema y pide tu contraseña para «revisar tu cuenta». ¿Qué haces?', 'options' => ['Se la doy', 'No la doy y lo reporto: un administrador real no la necesita', 'Le doy la mitad', 'Le pido que me la devuelva luego']],
             's' => ['answer' => 1], 'e' => 'La autoridad es un disfraz frecuente. Nunca compartas tu contraseña.'],
            ['d' => 3, 'dim' => 'proteccion', 'c' => ['question' => '¿Qué es mejor para recordar muchas contraseñas distintas?', 'options' => ['Usar la misma en todo', 'Anotarlas en un papel pegado a la pantalla', 'Usar frases de paso y, si es posible, un gestor de contraseñas confiable', 'Compartirlas con un amigo para que te las recuerde']],
             's' => ['answer' => 2], 'e' => 'Un gestor de contraseñas guarda tus claves de forma cifrada. Las frases de paso son fáciles de recordar.'],
            ['d' => 1, 'dim' => 'proteccion', 'c' => ['question' => 'Recibiste un mensaje de phishing y ya lo identificaste. ¿Qué práctica ayuda a otros?', 'options' => ['Borrarlo en silencio', 'Reportarlo en la plataforma y compartir recomendaciones con tu familia o compañeros', 'Reenviarlo como broma', 'Responder al estafador']],
             's' => ['answer' => 1], 'e' => 'Reportar y compartir recomendaciones multiplica la protección (ítem 24).'],
        ],
    ]);
};
