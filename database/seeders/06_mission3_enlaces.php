<?php
/**
 * Misión 3 · Detective de enlaces  (Sesión 3)
 * Dimensión: toma de decisiones seguras (ítems 4, 9, 11 y 20).
 */
return function (PDO $pdo): void {
    $m = seed_mission($pdo, [
        'slug' => 'detective-de-enlaces', 'order' => 3, 'icon' => 'bx-search-alt', 'color' => '#FF7A59',
        'dimension' => 'decisiones',
        'title' => 'Detective de enlaces',
        'subtitle' => 'Lee una dirección web como un experto',
        'description' => 'Un enlace puede decir una cosa y llevarte a otra. Aprende a encontrar el dominio real, a desconfiar de acortadores y a entrar siempre por la aplicación o la dirección oficial.',
        'objectives' => [
            'Encontrar el dominio real dentro de una dirección web.',
            'Detectar dominios con letras cambiadas, palabras añadidas o terminaciones extrañas.',
            'Preferir la app o la dirección oficial antes que el enlace recibido.',
        ],
        'lesson' => [
            ['icon' => 'bxs-compass', 'title' => 'Anatomía de un enlace', 'text' => 'En https://www.viabcp.com/cuentas, el dominio es «viabcp.com». Lo que manda es lo que está JUSTO antes de la primera barra «/». Todo lo que va a la izquierda son subdominios y lo de la derecha es la ruta.'],
            ['icon' => 'bx-anchor', 'title' => 'La trampa del subdominio', 'text' => 'En https://viabcp.com.seguridad-pe.net/login el dominio real es «seguridad-pe.net», no el banco. Los atacantes ponen el nombre de la marca al inicio para engañarte.'],
            ['icon' => 'bx-font', 'title' => 'Letras gemelas', 'text' => 'g00gle (ceros), faceb0ok, rnicrosoft (r+n), yapé o perueduca-pe.com. Lee despacio, letra por letra.'],
            ['icon' => 'bxs-lock-alt', 'title' => 'El candado no basta', 'text' => '«https» y el candado solo indican que la conexión está cifrada, no que la página sea honesta. Muchos sitios falsos también tienen candado.'],
            ['icon' => 'bx-cut', 'title' => 'Enlaces acortados', 'text' => 'bit.ly, tinyurl o cutt.ly esconden el destino real. Si llegan en un mensaje inesperado que pide datos, no los abras.'],
            ['icon' => 'bxs-home', 'title' => 'Entra por la puerta oficial', 'text' => 'La decisión más segura: no uses el enlace. Abre la app oficial o escribe tú mismo la dirección conocida, y revisa la barra antes de escribir tu contraseña.'],
        ],
        'xp' => 110, 'coins' => 30,
    ]);

    $u = function ($d, $ctx, $url, $answer, $domain, $e) {
        return ['d' => $d, 'c' => ['context' => $ctx, 'url' => $url], 's' => ['answer' => $answer, 'real_domain' => $domain], 'e' => $e];
    };

    seed_game($pdo, $m, [
        'slug' => 'inspector-de-enlaces', 'type' => 'url', 'order' => 1, 'icon' => 'bx-link',
        'dimension' => 'decisiones', 'per_play' => 8, 'pass' => 60,
        'title' => 'Inspector de enlaces',
        'description' => 'Revisa cada dirección y decide si es segura o sospechosa.',
        'instructions' => 'Lee la dirección completa. Busca el dominio real (lo que está justo antes de la primera «/»). Decide: ¿entrarías o no? Después verás cómo se lee la dirección.',
        'items' => [
            $u(1, 'Quieres entrar a tu aula virtual de PerúEduca.', 'https://www.perueduca.pe/', 'seguro', 'perueduca.pe',
                'Es el dominio oficial. Aun así, escribe tú mismo la dirección o usa un marcador guardado.'),
            $u(1, 'Un SMS dice que tu cuenta del banco está bloqueada.', 'http://viabcp-bloqueo.com/validar', 'sospechoso', 'viabcp-bloqueo.com',
                'El dominio real es «viabcp-bloqueo.com», distinto del oficial «viabcp.com». Además, llegó por un SMS alarmista.'),
            $u(1, 'Te llega un sorteo de diamantes para tu juego favorito.', 'https://diamantes-free-fire.xyz/premio', 'sospechoso', 'diamantes-free-fire.xyz',
                'Terminación .xyz y nombre inventado. Los juegos no regalan premios pidiendo tu cuenta en sitios externos.'),
            $u(2, 'Buscas información sobre trámites del Estado.', 'https://www.gob.pe/institucion/minedu', 'seguro', 'gob.pe',
                'gob.pe es la plataforma oficial del Estado peruano. La ruta «/institucion/minedu» está a la derecha del dominio.'),
            $u(2, 'Un correo te pide confirmar tu cuenta de Google.', 'https://accounts.google.com.verificar-cuenta.info/login', 'sospechoso', 'verificar-cuenta.info',
                'La trampa del subdominio: el dominio real es «verificar-cuenta.info». «accounts.google.com» está puesto a la izquierda solo para engañar.'),
            $u(2, 'Un mensaje de «Instagram» sobre derechos de autor.', 'https://instagrarn.com/apelacion', 'sospechoso', 'instagrarn.com',
                '«instagrarn» usa «r»+«n» para imitar la «m». Lee letra por letra.'),
            $u(2, 'Quieres revisar la seguridad de tu cuenta Google.', 'https://myaccount.google.com/security', 'seguro', 'google.com',
                'El dominio real es google.com; «myaccount» es un subdominio de Google. Es la dirección oficial.'),
            $u(3, 'Un amigo te manda «mira este video tuyo».', 'https://bit.ly/3xVideoTuyo', 'sospechoso', 'bit.ly (acortador: oculta el destino real)',
                'Los acortadores esconden a dónde te llevan. Combinado con un mensaje que genera curiosidad o vergüenza, es una señal fuerte. Pregúntale a tu amigo por otro medio.'),
            $u(3, 'Un correo de «Yape» dice que ganaste un bono.', 'https://yape.com.pe.bono-2026.co/registro', 'sospechoso', 'bono-2026.co',
                'El dominio real es «bono-2026.co». La palabra «yape.com.pe» a la izquierda es solo decoración.'),
            $u(3, 'Quieres pagar un servicio desde el sitio oficial de SUNAT.', 'https://www.sunat.gob.pe/', 'seguro', 'sunat.gob.pe',
                'sunat.gob.pe termina en .gob.pe, reservado para entidades del Estado peruano.'),
            $u(3, 'Un mensaje te pide actualizar datos de tu correo institucional.', 'https://login.microsoftonline.com-auth.support/amauta', 'sospechoso', 'com-auth.support',
                'Muy engañoso: el dominio real es «com-auth.support». Busca la última parte antes de la primera «/».'),
            $u(3, 'Un correo de Netflix te recuerda tu fecha de pago.', 'https://www.netflix.com/youraccount', 'seguro', 'netflix.com',
                'El dominio es netflix.com. Aun así, si el correo insiste con urgencia, entra desde la app en vez del enlace.'),
            $u(2, 'Un aviso dice que puedes cobrar el «Bono Familiar».', 'http://bonofamiliar-peru.online/consulta', 'sospechoso', 'bonofamiliar-peru.online',
                'Los bonos del Estado se consultan en portales .gob.pe. «.online» con nombre inventado es sospechoso.'),
            $u(1, 'Quieres entrar a YouTube.', 'https://www.youtube.com/', 'seguro', 'youtube.com',
                'Dominio oficial. Recuerda: aunque la página sea real, no compartas datos personales en comentarios.'),
        ],
    ]);

    seed_game($pdo, $m, [
        'slug' => 'prueba-3', 'type' => 'quiz', 'order' => 2, 'icon' => 'bxs-edit',
        'dimension' => 'decisiones', 'per_play' => 5, 'pass' => 60,
        'title' => 'Mini-prueba: enlaces',
        'description' => 'Comprueba si ya lees enlaces como detective.',
        'instructions' => 'Elige la respuesta correcta en cada pregunta.',
        'items' => [
            ['d' => 1, 'dim' => 'decisiones', 'c' => ['question' => 'En la dirección https://tienda.ejemplo.com/ofertas, ¿cuál es el dominio real?', 'options' => ['tienda', 'ejemplo.com', 'ofertas', 'https']],
             's' => ['answer' => 1], 'e' => 'El dominio es lo que está justo antes de la primera «/»: ejemplo.com. «tienda» es un subdominio.'],
            ['d' => 2, 'dim' => 'decisiones', 'c' => ['question' => 'En https://viabcp.com.cuenta-segura.net/login, ¿quién controla realmente la página?', 'options' => ['El banco BCP', 'El dueño de cuenta-segura.net', 'Google', 'Nadie']],
             's' => ['answer' => 1], 'e' => 'El dominio real es cuenta-segura.net. Poner el nombre del banco al inicio es una trampa.'],
            ['d' => 1, 'dim' => 'decisiones', 'c' => ['question' => 'Una página tiene candado y «https». Eso significa que…', 'options' => ['La página es 100 % confiable', 'La conexión está cifrada, pero la página igual puede ser falsa', 'Es del gobierno', 'No tiene virus']],
             's' => ['answer' => 1], 'e' => 'El candado protege el viaje de los datos, no garantiza la honestidad del sitio.'],
            ['d' => 2, 'dim' => 'decisiones', 'c' => ['question' => 'Te llega un SMS del «banco» con un enlace para desbloquear tu cuenta. ¿Qué es lo más seguro?', 'options' => ['Abrir el enlace y revisar', 'Abrir la app oficial del banco o llamar a su número oficial, sin usar el enlace', 'Responder el SMS', 'Reenviarlo a un amigo']],
             's' => ['answer' => 1], 'e' => 'Entrar por la puerta oficial evita caer en páginas clonadas.'],
            ['d' => 2, 'dim' => 'proteccion', 'c' => ['question' => '¿En qué momento es MÁS importante revisar la dirección completa de la página?', 'options' => ['Antes de escribir tu contraseña o datos', 'Después de cerrar la página', 'Solo cuando la página se ve fea', 'Nunca']],
             's' => ['answer' => 0], 'e' => 'Antes de escribir una contraseña, mira la barra de direcciones: si el dominio no es el oficial, detente.'],
            ['d' => 3, 'dim' => 'decisiones', 'c' => ['question' => '¿Cuál de estas direcciones pertenece a una entidad del Estado peruano?', 'options' => ['minedu-becas.com', 'www.minedu.gob.pe', 'minedu.pe-login.org', 'minedu.online']],
             's' => ['answer' => 1], 'e' => 'Las entidades públicas usan .gob.pe como terminación del dominio.'],
            ['d' => 3, 'dim' => 'decisiones', 'c' => ['question' => '¿Por qué los enlaces acortados (bit.ly, tinyurl) requieren precaución?', 'options' => ['Porque son lentos', 'Porque esconden el destino real', 'Porque son ilegales', 'Porque solo funcionan en computadoras']],
             's' => ['answer' => 1], 'e' => 'No sabes a dónde te llevarán hasta abrirlos. Si vienen en mensajes inesperados, mejor no abrirlos.'],
            ['d' => 3, 'dim' => 'reconocimiento', 'c' => ['question' => '¿Cuál de estos dominios imita a Google?', 'options' => ['google.com', 'g00gle-login.com', 'accounts.google.com', 'myaccount.google.com']],
             's' => ['answer' => 1], 'e' => 'g00gle usa ceros en lugar de «o». Los otros tres pertenecen al dominio google.com.'],
        ],
    ]);
};
