<?php
/**
 * PhishHunter · Contenido de la Zona de refuerzo
 *   - videos: videoteca de YouTube (por misión) con una pregunta de repaso
 *   - games:  minijuegos de refuerzo (memorama, arma tu clave, relaciona, oficina)
 *
 * Para cambiar un video basta con editar su «youtube» (el código que aparece
 * después de ?v= en el enlace) y volver a ejecutar upgrade.php.
 */

return [

    /* ================================================================
     *  VIDEOTECA
     *  mission = slug de la misión donde aparece (null = solo videoteca)
     * ============================================================== */
    'videos' => [
        ['slug' => 'v-que-es-phishing', 'mission' => 'que-es-phishing', 'youtube' => 'uhzV5-iFb5E',
         'title' => '¿Qué es el phishing?', 'channel' => 'INCIBE', 'minutes' => 2,
         'summary' => 'Explicación corta de cómo los ciberdelincuentes se hacen pasar por empresas o personas de confianza para robar datos.',
         'q' => '¿Cuál es el objetivo principal de un ataque de phishing?',
         'options' => ['Arreglar tu computadora a distancia', 'Engañarte para que entregues datos o hagas clic en algo peligroso', 'Mejorar la velocidad de tu internet', 'Enviarte publicidad legal'],
         'answer' => 1, 'explain' => 'El phishing es un engaño: se disfraza de alguien de confianza para que entregues contraseñas, datos o dinero.'],

        ['slug' => 'v-phishing-2min', 'mission' => 'que-es-phishing', 'youtube' => '8ktaGnFkOoc',
         'title' => 'El phishing explicado en 2 minutos', 'channel' => 'Cybernews en Español', 'minutes' => 2,
         'summary' => 'Un repaso rápido de cómo funciona un ataque de phishing de principio a fin.',
         'q' => 'Si un mensaje te pide actuar «¡ya!» o perderás algo, ¿qué deberías hacer?',
         'options' => ['Actuar rápido para no perderlo', 'Detenerte y verificar por un canal oficial', 'Reenviarlo a tus amigos', 'Responder con tus datos para aclarar'],
         'answer' => 1, 'explain' => 'La urgencia es la señal número uno: busca que no pienses. Pausa y verifica.'],

        ['slug' => 'v-casos-017', 'mission' => 'que-es-phishing', 'youtube' => '7T32WBQRrBA',
         'title' => 'Phishing: casos reales de la línea 017', 'channel' => 'INCIBE', 'minutes' => 3,
         'summary' => 'Casos reales atendidos por una línea de ayuda en ciberseguridad.',
         'q' => 'Si ya caíste en un phishing, ¿qué es lo primero que conviene hacer?',
         'options' => ['Ocultarlo por vergüenza', 'Cambiar la contraseña y avisar a un adulto o al servicio afectado', 'Esperar a ver si pasa algo', 'Borrar el mensaje y olvidarlo'],
         'answer' => 1, 'explain' => 'Actuar rápido limita el daño: cambia contraseñas, activa la doble verificación y pide ayuda.'],

        ['slug' => 'v-smishing', 'mission' => 'senales-de-alerta', 'youtube' => 'vM3QD_ymTwo',
         'title' => '¿Qué es el smishing?', 'channel' => 'INCIBE', 'minutes' => 2,
         'summary' => 'El phishing que llega por SMS: paquetes retenidos, premios y bancos falsos.',
         'q' => 'El smishing es un phishing que llega por…',
         'options' => ['Llamada telefónica', 'Mensaje de texto (SMS)', 'Carta en papel', 'Correo postal'],
         'answer' => 1, 'explain' => 'SMS + phishing = smishing. Suelen traer un enlace acortado y una urgencia.'],

        ['slug' => 'v-vishing', 'mission' => 'senales-de-alerta', 'youtube' => 't-Btf1-8-Lw',
         'title' => '¿Qué es el vishing?', 'channel' => 'INCIBE', 'minutes' => 2,
         'summary' => 'Cuando el engaño llega por una llamada: falsos técnicos, bancos o familiares.',
         'q' => 'Te llama «tu banco» y te pide el código que te acaba de llegar por SMS. ¿Qué haces?',
         'options' => ['Se lo dicto para que me ayuden', 'Cuelgo y llamo yo al número oficial', 'Le doy solo la mitad del código', 'Le pido que me lo envíe por correo'],
         'answer' => 1, 'explain' => 'Ninguna entidad seria te pide códigos por teléfono. Cuelga y llama tú al número oficial.'],

        ['slug' => 'v-url-osi', 'mission' => 'detective-de-enlaces', 'youtube' => 'oF0JKaJJFso',
         'title' => 'Cómo analizar la URL de un correo sospechoso', 'channel' => 'Oficina de Seguridad del Internauta', 'minutes' => 3,
         'summary' => 'Paso a paso para revisar a dónde lleva realmente un enlace antes de abrirlo.',
         'q' => 'En https://banco.seguro-login.net/inicio, ¿cuál es el dominio real?',
         'options' => ['banco', 'seguro-login.net', 'inicio', 'https'],
         'answer' => 1, 'explain' => 'El dominio real es lo que está justo antes de la primera «/»: seguro-login.net. «banco» es solo un subdominio de decoración.'],

        ['slug' => 'v-cybersquatting', 'mission' => 'detective-de-enlaces', 'youtube' => 'UvmWit3yA68',
         'title' => '¿Qué es el cybersquatting?', 'channel' => 'INCIBE', 'minutes' => 2,
         'summary' => 'Dominios que imitan a los reales cambiando una letra o la terminación.',
         'q' => '¿Cuál de estos dominios es una imitación?',
         'options' => ['minedu.gob.pe', 'rnineduc.gob-pe.com', 'gob.pe', 'perueduca.pe'],
         'answer' => 1, 'explain' => '«rn» parece una «m» y la terminación «gob-pe.com» no es un dominio del Estado.'],

        ['slug' => 'v-ingenieria-social', 'mission' => 'decide-con-cabeza-fria', 'youtube' => 'TentnM1-lg0',
         'title' => '¿Qué es la ingeniería social?', 'channel' => 'INCIBE', 'minutes' => 2,
         'summary' => 'Las técnicas para manipular emociones: miedo, confianza, curiosidad o codicia.',
         'q' => 'La ingeniería social ataca principalmente…',
         'options' => ['Los cables de la red', 'Las emociones y la confianza de las personas', 'La batería del celular', 'La pantalla del equipo'],
         'answer' => 1, 'explain' => 'No necesita «hackear» equipos: engaña a las personas usando sus emociones.'],

        ['slug' => 'v-pretexting', 'mission' => 'decide-con-cabeza-fria', 'youtube' => 'cLQRZ5bdGp0',
         'title' => '¿Qué es el pretexting?', 'channel' => 'INCIBE', 'minutes' => 2,
         'summary' => 'El atacante inventa una historia creíble (un pretexto) para pedirte información.',
         'q' => '«Soy de la municipalidad, necesito tu DNI para un bono». Eso es un ejemplo de…',
         'options' => ['Pretexting: una historia inventada para pedir datos', 'Un trámite normal por chat', 'Un error de ortografía', 'Una actualización del sistema'],
         'answer' => 0, 'explain' => 'Inventan un pretexto creíble. Verifica siempre en la fuente oficial antes de dar datos.'],

        ['slug' => 'v-contrasena-segura', 'mission' => 'protege-tus-cuentas', 'youtube' => 'Cp4Tiq3LOrc',
         'title' => 'Contraseña segura: ocho claves', 'channel' => 'PantallasAmigas', 'minutes' => 3,
         'summary' => 'Ocho recomendaciones para crear y cuidar contraseñas.',
         'q' => '¿Cuál es la mejor contraseña?',
         'options' => ['Luis2009', '123456789', 'Mango-Nube-Tractor-Rio7', 'contraseña'],
         'answer' => 2, 'explain' => 'Una frase larga con palabras sin relación, un número y un signo es fácil de recordar y difícil de adivinar.'],

        ['slug' => 'v-contrasenas-robustas', 'mission' => 'protege-tus-cuentas', 'youtube' => 'n0vvSJJCFbs',
         'title' => 'Contraseñas robustas', 'channel' => 'OSI - INCIBE', 'minutes' => 2,
         'summary' => 'Por qué no conviene repetir contraseñas y cómo hacerlas más fuertes.',
         'q' => '¿Por qué es peligroso usar la misma contraseña en todas tus cuentas?',
         'options' => ['Porque se olvida más rápido', 'Si roban una, pueden entrar a todas', 'Porque ocupa más memoria', 'No es peligroso'],
         'answer' => 1, 'explain' => 'Los atacantes prueban la contraseña robada en muchos servicios. Una clave distinta por cuenta corta el efecto dominó.'],

        ['slug' => 'v-2fa', 'mission' => 'protege-tus-cuentas', 'youtube' => 'jr13oJdhX34',
         'title' => 'Verificación en dos pasos', 'channel' => 'Clarín · Buena Data', 'minutes' => 3,
         'summary' => 'Qué es la verificación en dos pasos y por qué conviene activarla.',
         'q' => 'Con la verificación en dos pasos activa, si alguien roba tu contraseña…',
         'options' => ['Entra igual sin problema', 'Todavía necesita un segundo código que solo tú recibes', 'Tu cuenta se borra', 'Tu celular se apaga'],
         'answer' => 1, 'explain' => 'El segundo paso (un código o una app) es un candado extra. Por eso nunca debes compartir esos códigos.'],

        ['slug' => 'v-spear-phishing', 'mission' => 'operacion-anzuelo-roto', 'youtube' => 'l0Rkf2Ut-X0',
         'title' => '¿Qué es el spear phishing?', 'channel' => 'INCIBE', 'minutes' => 2,
         'summary' => 'Ataques personalizados: usan tu nombre, tu colegio o tu trabajo para parecer reales.',
         'q' => 'Un mensaje con tu nombre completo y el de tu colegio…',
         'options' => ['Es seguro porque me conocen', 'Puede ser spear phishing: igual debo verificar', 'Siempre es del colegio', 'Debo responder rápido'],
         'answer' => 1, 'explain' => 'Tus datos pueden estar en redes sociales. Que sepan tu nombre no prueba que sean quienes dicen ser.'],

        ['slug' => 'v-fraude-ceo', 'mission' => 'operacion-anzuelo-roto', 'youtube' => 'a4hWM_e7OaM',
         'title' => 'La estafa del CEO', 'channel' => 'Palabra de hacker', 'minutes' => 5,
         'summary' => 'Cómo los delincuentes se hacen pasar por el jefe para pedir pagos urgentes. Clave para el Simulador de oficina.',
         'q' => 'Tu «jefe» te escribe desde un correo personal pidiendo un pago urgente y secreto. ¿Qué haces?',
         'options' => ['Pago rápido para quedar bien', 'Verifico con él por otro canal (en persona o a su número conocido)', 'Respondo pidiendo más datos por el mismo correo', 'Pago la mitad'],
         'answer' => 1, 'explain' => 'Urgencia + secreto + canal inusual = fraude del CEO. Siempre verifica por un canal que ya conoces.'],
    ],

    /* ================================================================
     *  MINIJUEGOS DE REFUERZO
     *  unlock = número de misión que hay que completar (0 = libre)
     * ============================================================== */
    'games' => [

        /* ------------------------------------------------------------
         *  1. Memorama del cazador (términos definiciones)
         * ---------------------------------------------------------- */
        [
            'slug' => 'memorama-cazador', 'type' => 'memory', 'order' => 1, 'unlock' => 1,
            'icon' => 'bxs-copy-alt', 'color' => '#8BC34A', 'per_play' => 6, 'pass' => 60, 'xp' => 60, 'coins' => 20,
            'badge' => 'memoria-de-acero',
            'title' => 'Memorama del cazador',
            'description' => 'Voltea cartas y une cada término con su definición.',
            'instructions' => 'Voltea dos cartas por turno. Si el término y la definición coinciden, se quedan boca arriba. Tienes algunos errores de cortesía: después de esos, cada fallo resta puntos.',
            'items' => [
                ['a' => 'Phishing', 'b' => 'Engaño que se hace pasar por alguien de confianza para robar datos', 'e' => 'Viene de «fishing» (pescar): lanzan un anzuelo esperando que alguien pique.'],
                ['a' => 'Smishing', 'b' => 'Phishing que llega por mensaje de texto (SMS)', 'e' => 'SMS + phishing. Ej.: «Tu paquete está retenido, paga aquí».'],
                ['a' => 'Vishing', 'b' => 'Phishing por llamada telefónica o mensaje de voz', 'e' => 'Voice + phishing. Falsos bancos o técnicos que llaman.'],
                ['a' => 'Quishing', 'b' => 'Phishing escondido en un código QR', 'e' => 'Antes de pagar o iniciar sesión tras escanear un QR, revisa la dirección que abre.'],
                ['a' => 'Dominio', 'b' => 'Parte de la dirección que dice quién es el dueño real del sitio', 'e' => 'Está justo antes de la primera «/». Ej.: en login.minedu.gob.pe/… el dominio es minedu.gob.pe.'],
                ['a' => 'Doble verificación', 'b' => 'Segundo candado: un código extra además de la contraseña', 'e' => 'También se llama 2FA. Nunca compartas esos códigos.'],
                ['a' => 'Ingeniería social', 'b' => 'Manipular emociones (miedo, prisa, confianza) para engañar', 'e' => 'Ataca a las personas, no a las máquinas.'],
                ['a' => 'Suplantación', 'b' => 'Hacerse pasar por otra persona o empresa', 'e' => 'También llamada «spoofing». El nombre visible puede ser falso.'],
                ['a' => 'Malware', 'b' => 'Programa malicioso que puede llegar en un adjunto', 'e' => 'Cuidado con .exe, .apk o archivos comprimidos que no esperabas.'],
                ['a' => 'Acortador', 'b' => 'Servicio que esconde la dirección real de un enlace', 'e' => 'bit.ly, tinyurl… no son malos, pero ocultan el destino. Desconfía si vienen con urgencia.'],
                ['a' => 'Spear phishing', 'b' => 'Ataque personalizado que usa tu nombre y datos reales', 'e' => 'Es más difícil de detectar porque parece hecho solo para ti.'],
                ['a' => 'Pretexting', 'b' => 'Historia inventada y creíble para pedirte información', 'e' => '«Soy de soporte, estamos migrando cuentas…» es un pretexto clásico.'],
            ],
        ],

        /* ------------------------------------------------------------
         *  2. Relaciona y protege
         * ---------------------------------------------------------- */
        [
            'slug' => 'relaciona-y-protege', 'type' => 'match', 'order' => 2, 'unlock' => 2,
            'icon' => 'bx-link', 'color' => '#FFC83D', 'per_play' => 4, 'pass' => 60, 'xp' => 60, 'coins' => 20,
            'badge' => 'conector',
            'title' => 'Relaciona y protege',
            'description' => 'Une cada elemento de la izquierda con su pareja de la derecha.',
            'instructions' => 'Toca un elemento de la izquierda y luego su pareja en la derecha (o arrástralo). Cuando hayas unido todo, pulsa «Comprobar».',
            'items' => [
                ['prompt' => 'Une cada señal de alerta con su ejemplo', 'd' => 1, 'pairs' => [
                    ['Urgencia', '«Tu cuenta se cerrará en 1 hora»'],
                    ['Premio inesperado', '«¡Ganaste un celular! Solo paga el envío»'],
                    ['Remitente raro', 'soporte@minedu-pe.xyz'],
                    ['Adjunto peligroso', 'Boleta_notas.pdf.exe'],
                ], 'e' => 'Urgencia, premios, remitentes que imitan y adjuntos ejecutables son las señales más comunes.'],
                ['prompt' => 'Une cada ataque con el canal por donde llega', 'd' => 1, 'pairs' => [
                    ['Smishing', 'Mensaje de texto SMS'],
                    ['Vishing', 'Llamada telefónica'],
                    ['Quishing', 'Código QR'],
                    ['Phishing clásico', 'Correo electrónico'],
                ], 'e' => 'El engaño es el mismo; solo cambia el canal.'],
                ['prompt' => 'Une cada situación con la acción más segura', 'd' => 2, 'pairs' => [
                    ['Un amigo pide el código de 6 dígitos que te llegó', 'Llamarlo por teléfono para confirmar'],
                    ['Correo del «banco» con enlace para verificar', 'Entrar escribiendo la dirección oficial'],
                    ['Descargaste un archivo raro y lo abriste', 'Avisar a un adulto y pasar el antivirus'],
                    ['Te piden la contraseña por chat', 'No compartirla y reportar'],
                ], 'e' => 'La regla de oro: no entregues nada y verifica por otro canal que ya conoces.'],
                ['prompt' => 'Une cada dirección con su dominio real', 'd' => 2, 'pairs' => [
                    ['https://login.minedu.gob.pe/acceso', 'minedu.gob.pe'],
                    ['https://minedu.gob.pe.login-seguro.com', 'login-seguro.com'],
                    ['https://www.facebook.com.ingreso.xyz/', 'ingreso.xyz'],
                    ['https://accounts.google.com/signin', 'google.com'],
                ], 'e' => 'El dominio real está justo antes de la primera «/». Todo lo que va a la izquierda es decoración.'],
                ['prompt' => 'Une cada práctica con el riesgo que evita', 'd' => 2, 'pairs' => [
                    ['Contraseña distinta en cada cuenta', 'Que roben todas tus cuentas con una sola clave'],
                    ['Doble verificación activada', 'Que entren aunque sepan tu contraseña'],
                    ['Cerrar sesión en equipos compartidos', 'Que otra persona use tu cuenta abierta'],
                    ['Actualizar el sistema y apps', 'Que aprovechen fallas de seguridad conocidas'],
                ], 'e' => 'Cada hábito de protección bloquea un tipo distinto de ataque.'],
                ['prompt' => 'Une cada término con su definición', 'd' => 3, 'pairs' => [
                    ['Spear phishing', 'Ataque personalizado con tus datos reales'],
                    ['Pretexting', 'Historia inventada para pedirte información'],
                    ['Suplantación', 'Hacerse pasar por otra persona o empresa'],
                    ['Fraude del CEO', 'Falso jefe que pide un pago urgente y secreto'],
                ], 'e' => 'Conocer el nombre de cada técnica te ayuda a reconocerla cuando aparece.'],
            ],
        ],

        /* ------------------------------------------------------------
         *  3. Memorama de claves (contraseña diagnóstico)
         * ---------------------------------------------------------- */
        [
            'slug' => 'memorama-claves', 'type' => 'memory', 'order' => 3, 'unlock' => 4,
            'icon' => 'bxs-key', 'color' => '#E0457B', 'per_play' => 6, 'pass' => 60, 'xp' => 60, 'coins' => 20,
            'badge' => 'memoria-de-acero',
            'title' => 'Memorama de claves',
            'description' => 'Une cada contraseña con el diagnóstico que le corresponde.',
            'instructions' => 'Cada contraseña tiene una carta pareja que explica por qué es débil o por qué es fuerte. ¡Encuentra todas las parejas!',
            'items' => [
                ['a' => 'Luis2009', 'b' => 'Débil: tu nombre y año de nacimiento', 'e' => 'Tu nombre y fecha están en tus redes sociales: son lo primero que prueban.'],
                ['a' => '123456789', 'b' => 'Débil: la secuencia más usada del mundo', 'e' => 'Aparece primero en todas las listas de contraseñas filtradas.'],
                ['a' => 'Firulais', 'b' => 'Débil: el nombre de tu mascota', 'e' => 'Si subiste fotos de tu perro, ya conocen su nombre.'],
                ['a' => 'qwerty', 'b' => 'Débil: letras seguidas del teclado', 'e' => 'Los patrones del teclado se prueban en segundos.'],
                ['a' => 'Amauta2026', 'b' => 'Débil: colegio + año actual', 'e' => 'Datos públicos + año actual = muy fácil de adivinar.'],
                ['a' => 'P@ssw0rd', 'b' => 'Débil: palabra común con símbolos «disfrazados»', 'e' => 'Cambiar a por @ u o por 0 no engaña a los programas de ataque.'],
                ['a' => 'Mango-Nube-Tractor-Rio7', 'b' => 'Fuerte: frase larga de palabras sin relación', 'e' => 'Larga, fácil de recordar y muy difícil de adivinar.'],
                ['a' => 'Café!Lluvia#Cometa82', 'b' => 'Fuerte: palabras, signos y números mezclados', 'e' => 'Mezcla tipos de caracteres y no tiene datos personales.'],
                ['a' => 'La misma clave en todo', 'b' => 'Riesgo: si roban una, entran a todas', 'e' => 'Usa una contraseña distinta por cuenta (un gestor de contraseñas ayuda).'],
                ['a' => 'Clave + código SMS', 'b' => 'Protección: doble verificación activa', 'e' => 'Aunque roben tu clave, falta el segundo candado.'],
            ],
        ],

        /* ------------------------------------------------------------
         *  4. Arma tu clave (puzle de cartas)
         *  Tipos de carta: word, number, symbol, trap (dato personal o débil)
         * ---------------------------------------------------------- */
        [
            'slug' => 'arma-tu-clave', 'type' => 'builder', 'order' => 4, 'unlock' => 5,
            'icon' => 'bxs-extension', 'color' => '#2BB39A', 'per_play' => 4, 'pass' => 70, 'xp' => 70, 'coins' => 25,
            'badge' => 'arquitecto-claves',
            'title' => 'Arma tu clave',
            'description' => 'Puzle de cartas: construye una contraseña fuerte y esquiva las cartas trampa.',
            'instructions' => 'Arrastra o toca las cartas para armar una contraseña. Para ganar la ronda: al menos 3 palabras, un número, un signo, 14 caracteres o más… ¡y ninguna carta con datos personales o claves débiles!',
            'items' => [
                ['d' => 1, 'goal' => 'Crea la clave de tu correo del colegio.',
                 'profile' => 'Te llamas Rosa, naciste en 2010 y estudias en la I.E. Amauta.',
                 'cards' => [['Volcán', 'word'], ['Tijera', 'word'], ['Pingüino', 'word'], ['Arcoíris', 'word'], ['7', 'number'], ['42', 'number'], ['#', 'symbol'], ['!', 'symbol'], ['-', 'symbol'], ['Rosa', 'trap'], ['2010', 'trap'], ['Amauta', 'trap']]],
                ['d' => 1, 'goal' => 'Crea la clave de tu cuenta de videojuegos.',
                 'profile' => 'Tu apodo es «Toño», tu perro se llama Rocky y tu número favorito es 123.',
                 'cards' => [['Cactus', 'word'], ['Linterna', 'word'], ['Mandarina', 'word'], ['Brújula', 'word'], ['58', 'number'], ['9', 'number'], ['*', 'symbol'], ['?', 'symbol'], ['_', 'symbol'], ['Rocky', 'trap'], ['Toño', 'trap'], ['123', 'trap']]],
                ['d' => 2, 'goal' => 'Crea la clave de tu red social.',
                 'profile' => 'Vives en Frías, tu equipo favorito es el de tu barrio y tu cumpleaños es el 15/08.',
                 'cards' => [['Tambor', 'word'], ['Nube', 'word'], ['Canoa', 'word'], ['Girasol', 'word'], ['Pizarra', 'word'], ['3', 'number'], ['64', 'number'], ['@', 'symbol'], ['%', 'symbol'], ['Frias', 'trap'], ['1508', 'trap'], ['password', 'trap'], ['qwerty', 'trap']]],
                ['d' => 2, 'goal' => 'Crea la clave de la cuenta de tu familia en la tienda online.',
                 'profile' => 'Tu mamá se llama Carmen, tu DNI termina en 4471 y tu color favorito es azul.',
                 'cards' => [['Hormiga', 'word'], ['Trompeta', 'word'], ['Manzana', 'word'], ['Satélite', 'word'], ['71', 'number'], ['8', 'number'], ['&', 'symbol'], ['+', 'symbol'], ['Carmen', 'trap'], ['4471', 'trap'], ['azul', 'trap'], ['abc123', 'trap']]],
                ['d' => 3, 'goal' => 'Crea la clave del correo que usarás para tu primer trabajo.',
                 'profile' => 'Te apellidas Chuquihuanga, tu promoción es 2027 y tu mascota se llama Michi.',
                 'cards' => [['Faro', 'word'], ['Cuaderno', 'word'], ['Relámpago', 'word'], ['Maíz', 'word'], ['Koala', 'word'], ['5', 'number'], ['36', 'number'], ['!', 'symbol'], ['#', 'symbol'], ['.', 'symbol'], ['Chuquihuanga', 'trap'], ['2027', 'trap'], ['Michi', 'trap'], ['111111', 'trap']]],
                ['d' => 3, 'goal' => 'Crea la clave maestra de tu gestor de contraseñas (¡la más importante!).',
                 'profile' => 'Tu nombre es Luis, juegas fútbol en el equipo «Los Halcones» y naciste en 2009.',
                 'cards' => [['Zanahoria', 'word'], ['Telescopio', 'word'], ['Acordeón', 'word'], ['Pelícano', 'word'], ['Granizo', 'word'], ['27', 'number'], ['4', 'number'], ['$', 'symbol'], ['=', 'symbol'], ['-', 'symbol'], ['Luis', 'trap'], ['Halcones', 'trap'], ['2009', 'trap'], ['admin', 'trap']]],
            ],
        ],

        /* ------------------------------------------------------------
         *  5. Simulador de oficina (reto final con gran premio)
         * ---------------------------------------------------------- */
        [
            'slug' => 'simulador-oficina', 'type' => 'office', 'order' => 5, 'unlock' => 6,
            'icon' => 'bxs-business', 'color' => '#5B6CFF', 'per_play' => 1, 'pass' => 85, 'xp' => 150, 'coins' => 60,
            'badge' => 'empleado-del-ano',
            'title' => 'Simulador de oficina',
            'description' => 'Tu primer día de práctica: completa documentos mientras te llegan correos y chats… algunos quieren engañarte.',
            'instructions' => 'Completa los documentos de tu bandeja de tareas usando la nota de tu jefa. Mientras trabajas llegarán correos, chats y llamadas: decide qué hacer con cada uno. Si terminas con 85 % o más, sin entregar contraseñas ni dinero, ganas el GRAN PREMIO.',
            'items' => [
                /* ---------------- Turno 1 ---------------- */
                [
                    'd' => 2,
                    'company' => 'Agroexport Frías S.A.C.',
                    'role' => 'Practicante del área de Administración',
                    'boss' => 'Lic. Marina Paz (jefa de Administración)',
                    'note' => [
                        'Hola, bienvenido(a). Tus tareas de hoy:',
                        '1) Registro de asistencia: 12 personas asistieron a la capacitación del lunes 5.',
                        '2) Orden de pago al proveedor Semillas del Norte: S/ 3 450. Cuenta registrada: 0011-0457-0200318866.',
                        '3) Memo de inventario para el área de Logística: 48 cajas de mango.',
                        'Mi correo es mpaz@agroexportfrias.pe. Cualquier duda, pásame la voz por el anexo 204.',
                    ],
                    'docs' => [
                        ['id' => 'asistencia', 'title' => 'Registro de asistencia', 'icon' => 'bxs-notepad', 'fields' => [
                            ['id' => 'evento', 'label' => 'Evento', 'type' => 'select', 'options' => ['Capacitación del lunes 5', 'Reunión de directorio', 'Aniversario'], 'answer' => 'Capacitación del lunes 5'],
                            ['id' => 'total', 'label' => 'Número de asistentes', 'type' => 'text', 'answer' => '12'],
                        ]],
                        ['id' => 'pago', 'title' => 'Orden de pago', 'icon' => 'bxs-credit-card', 'fields' => [
                            ['id' => 'proveedor', 'label' => 'Proveedor', 'type' => 'select', 'options' => ['Semillas del Norte', 'Transportes Piura', 'Agua Clara'], 'answer' => 'Semillas del Norte'],
                            ['id' => 'monto', 'label' => 'Monto (S/)', 'type' => 'text', 'answer' => '3450'],
                            ['id' => 'cuenta', 'label' => 'Cuenta bancaria', 'type' => 'text', 'answer' => '0011-0457-0200318866', 'trap' => '0011-0999-0200777123'],
                        ]],
                        ['id' => 'memo', 'title' => 'Memo de inventario', 'icon' => 'bxs-package', 'fields' => [
                            ['id' => 'para', 'label' => 'Para', 'type' => 'select', 'options' => ['Logística', 'Recursos Humanos', 'Ventas'], 'answer' => 'Logística'],
                            ['id' => 'cajas', 'label' => 'Cajas de mango', 'type' => 'text', 'answer' => '48'],
                        ]],
                    ],
                    'messages' => [
                        ['id' => 'm1', 'channel' => 'email', 'from_name' => 'Marina Paz', 'from' => 'mpaz@agroexportfrias.pe',
                         'subject' => 'Bienvenida y acceso a la carpeta compartida',
                         'body' => "¡Bienvenido(a) al equipo! Ya tienes acceso a la carpeta de Administración en el sistema interno. Cualquier duda me consultas en persona.\n\nMarina",
                         'best' => 'atender', 'ok' => ['archivar'],
                         'fb' => ['atender' => 'Correcto: es tu jefa, desde el dominio real y sin pedir nada raro.', 'archivar' => 'Bien, no pedía ninguna acción.', 'reportar' => 'Era un correo legítimo: dominio correcto y no pide datos. Reportar todo sin revisar también frena el trabajo.', 'verificar' => 'No hacía falta: el mensaje no pedía nada sensible.', 'entregar' => '—']],

                        ['id' => 'm2', 'channel' => 'email', 'from_name' => 'Soporte TI', 'from' => 'soporte@agroexportfrias-it.com',
                         'subject' => ' Tu cuenta nueva será bloqueada en 30 minutos',
                         'body' => "Hola practicante:\nPara activar tu cuenta corporativa debes validar tu contraseña ahora. Si no lo haces en 30 minutos tu acceso será bloqueado.",
                         'link' => ['text' => 'Validar mi cuenta', 'url' => 'https://agroexportfrias-it.com/validar'], 'login' => true,
                         'best' => 'reportar', 'ok' => ['verificar'],
                         'fb' => ['atender' => 'Hiciste clic en el enlace: dominio falso (agroexportfrias-it.com) y urgencia de 30 minutos.', 'archivar' => 'No caíste, pero conviene reportarlo para proteger a tus compañeros.', 'reportar' => '¡Bien! Dominio que imita al real, urgencia y pedido de contraseña: phishing de manual.', 'verificar' => 'Buena idea verificar con TI por el anexo. Lo ideal además es reportarlo.', 'entregar' => '¡Entregaste tu contraseña en una página falsa! TI nunca te pide validar tu clave por correo.']],

                        ['id' => 'm3', 'channel' => 'chat', 'from_name' => 'Jorge (Logística)', 'from' => 'Chat interno',
                         'body' => '¡Hola! Soy Jorge de Logística Cuando tengas el memo del inventario me lo mandas. ¡Gracias!',
                         'best' => 'atender', 'ok' => ['archivar'],
                         'fb' => ['atender' => 'Correcto: un compañero por el chat interno, pidiendo algo que coincide con tus tareas.', 'archivar' => 'Está bien, pero él espera tu memo.', 'reportar' => 'Era un pedido normal y coherente con tu tarea.', 'verificar' => 'No hacía falta: no pide nada sensible.', 'entregar' => '—']],

                        ['id' => 'm4', 'channel' => 'email', 'from_name' => 'Semillas del Norte', 'from' => 'pagos@semillasdelnorte-pe.net',
                         'subject' => 'URGENTE: nueva cuenta bancaria para el pago',
                         'body' => "Estimados:\nPor auditoría cambiamos de banco. Para el pago de hoy usen la NUEVA cuenta 0011-0999-0200777123. La anterior ya no está activa. Por favor no comenten este cambio por teléfono, nuestras líneas están en mantenimiento.",
                         'best' => 'verificar', 'ok' => ['reportar'],
                         'fb' => ['atender' => 'Cambiaste la cuenta: el dinero iría a un estafador. Cambio de cuenta + urgencia + «no llamen» = fraude.', 'archivar' => 'Lo ignoraste, bien, pero tu jefa debe saberlo: es un intento de fraude.', 'reportar' => 'Bien: dominio distinto (-pe.net), urgencia y te piden no verificar.', 'verificar' => '¡Perfecto! Ante un cambio de cuenta bancaria, SIEMPRE se confirma por teléfono con el número que ya conoces.', 'entregar' => '—']],

                        ['id' => 'm5', 'channel' => 'whatsapp', 'from_name' => 'Kiara (amiga del colegio)', 'from' => '+51 9•• ••• 318',
                         'body' => '¡Amigaaa! Mira este video donde sales tú en la fiesta: bit.ly/v1d3o-fiesta ¿eres tú? jajaja',
                         'link' => ['text' => 'bit.ly/v1d3o-fiesta', 'url' => 'https://bit.ly/v1d3o-fiesta'], 'login' => true,
                         'best' => 'verificar', 'ok' => ['reportar', 'archivar'],
                         'fb' => ['atender' => 'Abriste un enlace acortado de un mensaje que busca curiosidad. Así roban cuentas de WhatsApp y redes.', 'archivar' => 'Bien, no lo abriste.', 'reportar' => 'Bien: curiosidad + enlace acortado es un anzuelo clásico.', 'verificar' => '¡Muy bien! Llamar a tu amiga confirma si su cuenta fue robada.', 'entregar' => 'Pusiste tu contraseña en una página falsa de «inicio de sesión» para ver el video.']],

                        ['id' => 'm6', 'channel' => 'email', 'from_name' => 'Marina Paz', 'from' => 'marina.paz.gerencia@gmail.com',
                         'subject' => 'Favor urgente y confidencial',
                         'body' => "¿Estás en tu sitio? Necesito que compres 5 tarjetas de regalo de S/ 200 para unos clientes. Es confidencial, no le comentes a nadie. Envíame los códigos por aquí. Estoy en reunión y no puedo contestar llamadas.",
                         'best' => 'verificar', 'ok' => ['reportar'],
                         'fb' => ['atender' => 'Caíste en el fraude del CEO: correo personal (gmail), urgencia, secreto y «no puedo contestar llamadas».', 'archivar' => 'No caíste, pero tu jefa debe enterarse de que la están suplantando.', 'reportar' => '¡Bien! Correo que no es de la empresa, secreto y tarjetas de regalo: fraude del CEO.', 'verificar' => '¡Excelente! Confirmar en persona o por el anexo 204 desarma el fraude del CEO.', 'entregar' => '—']],

                        ['id' => 'm7', 'channel' => 'phone', 'from_name' => 'Llamada: «Banco Central de Créditos»', 'from' => 'Número oculto',
                         'body' => '«Buenas tardes, le llamamos del banco de la empresa. Detectamos un pago sospechoso. Le acaba de llegar un código por SMS: díctemelo para cancelarlo.»',
                         'best' => 'reportar', 'ok' => ['verificar'],
                         'fb' => ['atender' => 'Dictaste el código: con él autorizan el pago. Un banco jamás pide códigos por teléfono.', 'archivar' => 'Colgar está bien. Avisar a tu jefa ayuda a proteger las cuentas de la empresa.', 'reportar' => '¡Bien! Vishing: número oculto, susto y pedido de código.', 'verificar' => 'Bien: cuelga y llama tú al número oficial del banco.', 'entregar' => '—']],

                        ['id' => 'm8', 'channel' => 'email', 'from_name' => 'Recursos Humanos', 'from' => 'rrhh@agroexportfrias.pe',
                         'subject' => 'Horario de refrigerio',
                         'body' => "Recordatorio: el horario de refrigerio es de 1:00 a 2:00 p. m. en el comedor del segundo piso.\n\nRR. HH.",
                         'best' => 'atender', 'ok' => ['archivar'],
                         'fb' => ['atender' => 'Correcto: dominio real, informativo y sin pedir datos.', 'archivar' => 'Bien, era solo informativo.', 'reportar' => 'Era un aviso normal desde el dominio real de la empresa.', 'verificar' => 'No hacía falta: solo informa un horario.', 'entregar' => '—']],
                    ],
                ],

                /* ---------------- Turno 2 ---------------- */
                [
                    'd' => 3,
                    'company' => 'Colegio Técnico Los Algarrobos',
                    'role' => 'Asistente de secretaría',
                    'boss' => 'Prof. Raúl Neyra (director)',
                    'note' => [
                        'Buenos días. Hoy necesito:',
                        '1) Constancia de estudios para la alumna Ana Ruiz, de 4.º grado.',
                        '2) Pedido de materiales: 30 cuadernos para el aula de 1.º B.',
                        '3) Acta de reunión de padres: se realizó el viernes 9, asistieron 25 padres.',
                        'Mi correo es rneyra@losalgarrobos.edu.pe. Estaré en la dirección toda la mañana.',
                    ],
                    'docs' => [
                        ['id' => 'constancia', 'title' => 'Constancia de estudios', 'icon' => 'bxs-file', 'fields' => [
                            ['id' => 'alumna', 'label' => 'Estudiante', 'type' => 'text', 'answer' => 'Ana Ruiz'],
                            ['id' => 'grado', 'label' => 'Grado', 'type' => 'select', 'options' => ['3.º', '4.º', '5.º'], 'answer' => '4.º'],
                        ]],
                        ['id' => 'pedido', 'title' => 'Pedido de materiales', 'icon' => 'bxs-book-open', 'fields' => [
                            ['id' => 'aula', 'label' => 'Aula', 'type' => 'select', 'options' => ['1.º A', '1.º B', '2.º A'], 'answer' => '1.º B'],
                            ['id' => 'cuadernos', 'label' => 'Cuadernos', 'type' => 'text', 'answer' => '30'],
                        ]],
                        ['id' => 'acta', 'title' => 'Acta de reunión', 'icon' => 'bxs-folder-open', 'fields' => [
                            ['id' => 'dia', 'label' => 'Fecha', 'type' => 'select', 'options' => ['Viernes 9', 'Lunes 12', 'Miércoles 14'], 'answer' => 'Viernes 9'],
                            ['id' => 'padres', 'label' => 'Padres asistentes', 'type' => 'text', 'answer' => '25'],
                        ]],
                    ],
                    'messages' => [
                        ['id' => 'n1', 'channel' => 'email', 'from_name' => 'Raúl Neyra', 'from' => 'rneyra@losalgarrobos.edu.pe',
                         'subject' => 'Plantillas de documentos',
                         'body' => "Te dejé las plantillas de constancias en la carpeta compartida de secretaría. Cualquier duda, ven a la dirección.\n\nRaúl",
                         'best' => 'atender', 'ok' => ['archivar'],
                         'fb' => ['atender' => 'Correcto: dominio real del colegio y sin pedidos raros.', 'archivar' => 'Bien, era informativo.', 'reportar' => 'Era legítimo: dominio correcto y coherente con tu trabajo.', 'verificar' => 'No hacía falta.', 'entregar' => '—']],

                        ['id' => 'n2', 'channel' => 'email', 'from_name' => 'UGEL Piura – Plataforma', 'from' => 'notificaciones@ugel-piura-gob.pe.com',
                         'subject' => 'Actualización obligatoria de datos de estudiantes',
                         'body' => "Estimada secretaría:\nDeben subir HOY la lista de estudiantes con DNI y dirección en el siguiente formulario, o el colegio será sancionado.",
                         'link' => ['text' => 'Abrir formulario', 'url' => 'https://ugel-piura-gob.pe.com/formulario'], 'login' => true,
                         'best' => 'reportar', 'ok' => ['verificar'],
                         'fb' => ['atender' => 'Entregaste datos de menores a un sitio falso: el dominio real termina en gob.pe, no en .com.', 'archivar' => 'No caíste; lo ideal es reportarlo para alertar al director.', 'reportar' => '¡Bien! «gob.pe.com» es un dominio comercial disfrazado. Amenaza + datos personales = phishing.', 'verificar' => 'Bien: confirma con la UGEL por su teléfono oficial.', 'entregar' => 'Pusiste la contraseña del colegio en una página falsa.']],

                        ['id' => 'n3', 'channel' => 'chat', 'from_name' => 'Profe Lucía (1.º B)', 'from' => 'Chat interno',
                         'body' => 'Hola ¿Me avisas cuando pidas los cuadernos de 1.º B? Los chicos ya no tienen dónde escribir. ¡Gracias!',
                         'best' => 'atender', 'ok' => ['archivar'],
                         'fb' => ['atender' => 'Correcto: compañera real, pedido coherente con tu tarea.', 'archivar' => 'Está bien, aunque ella espera tu respuesta.', 'reportar' => 'Era una consulta normal.', 'verificar' => 'No hacía falta.', 'entregar' => '—']],

                        ['id' => 'n4', 'channel' => 'whatsapp', 'from_name' => 'Raúl Neyra (nuevo número)', 'from' => '+51 9•• ••• 052',
                         'body' => 'Hola, soy el director, este es mi número nuevo. Estoy en una reunión en la UGEL. Necesito que me envíes el código que te va a llegar al celular para entrar al sistema del colegio. Es urgente.',
                         'best' => 'verificar', 'ok' => ['reportar'],
                         'fb' => ['atender' => 'Compartiste el código: le diste acceso a tu cuenta a un desconocido. Los códigos no se comparten nunca.', 'archivar' => 'No caíste; avísale al director que lo están suplantando.', 'reportar' => '¡Bien! «Número nuevo» + urgencia + pedido de código = suplantación.', 'verificar' => '¡Excelente! La nota dice que el director está en la dirección: ve a confirmarlo en persona.', 'entregar' => '—']],

                        ['id' => 'n5', 'channel' => 'email', 'from_name' => 'Librería El Estudiante', 'from' => 'ventas@libreriaelestudiante.pe',
                         'subject' => 'Cotización de cuadernos',
                         'body' => "Buenos días, adjuntamos la cotización solicitada por el colegio para cuadernos y útiles. Los precios se mantienen hasta fin de mes.",
                         'attachment' => 'Cotizacion_cuadernos.pdf',
                         'best' => 'atender', 'ok' => ['verificar', 'archivar'],
                         'fb' => ['atender' => 'Correcto: proveedor conocido, adjunto PDF esperado y sin urgencia.', 'archivar' => 'Bien, puedes revisarlo luego.', 'reportar' => 'Era una cotización esperada con un PDF normal.', 'verificar' => 'Verificar con el proveedor nunca está de más.', 'entregar' => '—']],

                        ['id' => 'n6', 'channel' => 'email', 'from_name' => 'Soporte de Correo', 'from' => 'no-reply@correo-losalgarrobos.support',
                         'subject' => 'Tu buzón está lleno (99 %)',
                         'body' => "Tu buzón superó el límite. Descarga el limpiador de buzón para no perder mensajes.",
                         'attachment' => 'LimpiadorBuzon.exe',
                         'best' => 'reportar', 'ok' => ['archivar', 'verificar'],
                         'fb' => ['atender' => 'Abriste un .exe: así se instala malware. Ningún proveedor de correo envía programas para «limpiar».', 'archivar' => 'No lo abriste, bien. Reportarlo protege a los demás.', 'reportar' => '¡Bien! Dominio raro (.support) y adjunto ejecutable.', 'verificar' => 'Bien, consultar con el encargado de sistemas es buena idea.', 'entregar' => '—']],

                        ['id' => 'n7', 'channel' => 'sms', 'from_name' => 'SMS: Premios Escolares', 'from' => '+51 9•• ••• 777',
                         'body' => '¡Felicidades! El colegio ganó 20 laptops. Para recibirlas paga S/ 49 de envío aquí: tinyurl.com/laptops-cole',
                         'link' => ['text' => 'tinyurl.com/laptops-cole', 'url' => 'https://tinyurl.com/laptops-cole'], 'login' => true,
                         'best' => 'reportar', 'ok' => ['archivar'],
                         'fb' => ['atender' => 'Abriste el enlace del premio falso: nadie regala laptops pidiendo pagar el envío.', 'archivar' => 'Bien, no lo abriste.', 'reportar' => '¡Bien! Premio inesperado + pago + enlace acortado.', 'verificar' => 'No hace falta verificar un premio que nunca pediste; mejor repórtalo.', 'entregar' => 'Pusiste datos en una página falsa del «premio».']],

                        ['id' => 'n8', 'channel' => 'chat', 'from_name' => 'Carlos (auxiliar de sistemas)', 'from' => 'Chat interno',
                         'body' => 'Hola, voy a revisar tu computadora a las 11 por el mantenimiento. No necesito tu contraseña, solo que dejes la sesión cerrada.',
                         'best' => 'atender', 'ok' => ['archivar'],
                         'fb' => ['atender' => 'Correcto: aviso interno que además NO pide tu contraseña. Así se comunica un técnico real.', 'archivar' => 'Bien.', 'reportar' => 'Era un aviso legítimo por el chat interno y sin pedir datos.', 'verificar' => 'No era necesario: no pide nada sensible.', 'entregar' => '—']],
                    ],
                ],
            ],
        ],
    ],
];
