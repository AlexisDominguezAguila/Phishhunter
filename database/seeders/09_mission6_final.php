<?php
/**
 * Misión 6 · Operación Anzuelo Roto  (Sesión 6 · reto integrador)
 * Integra las tres dimensiones con casos de mayor dificultad.
 */
return function (PDO $pdo): void {
    $m = seed_mission($pdo, [
        'slug' => 'operacion-anzuelo-roto', 'order' => 6, 'icon' => 'bx-target-lock', 'color' => '#D4A017',
        'dimension' => 'integrador',
        'title' => 'Operación Anzuelo Roto',
        'subtitle' => 'Misión final: demuestra todo lo que aprendiste',
        'description' => 'Los atacantes ya no cometen errores obvios: usan nombres reales, autoridad y conversaciones largas. Enfrenta chats trampa y mensajes difíciles donde las señales son sutiles.',
        'objectives' => [
            'Resistir la presión de autoridad, amistad o urgencia en conversaciones.',
            'Clasificar mensajes difíciles combinando varias señales.',
            'Integrar reconocimiento, decisión y protección en una sola respuesta.',
        ],
        'lesson' => [
            ['icon' => 'bxs-mask', 'title' => 'Ataques con disfraz', 'text' => 'El atacante puede usar el nombre de tu docente, del director, de un familiar o de «soporte técnico». Cuanta más autoridad o cercanía finge, más importante es verificar.'],
            ['icon' => 'bxs-message-rounded-dots', 'title' => 'Conversaciones largas', 'text' => 'A veces empiezan con un saludo normal y, después de ganar tu confianza, llega la petición: un código, una foto del DNI, dinero o una contraseña.'],
            ['icon' => 'bxs-flask', 'title' => 'Mensajes perfectos', 'text' => 'Sin faltas, con logo y con tu nombre. Aun así, revisa: ¿esperabas este mensaje?, ¿qué te pide?, ¿a dónde te lleva?, ¿puedes verificarlo por otro canal?'],
            ['icon' => 'bx-target-lock', 'title' => 'Tu respuesta completa', 'text' => 'Reconoce la señal → no entregues nada → verifica por otro canal → protege tu cuenta → reporta. ¡Eso es ser un PhishHunter!'],
        ],
        'xp' => 150, 'coins' => 50,
    ]);

    $chat = function ($d, $dim, $contact, $sub, $avatar, $messages, $options, $answer, $feedback, $e) {
        $msgs = [];
        foreach ($messages as $mm) {
            $msgs[] = ['from' => $mm[0], 'text' => $mm[1]];
        }
        return ['d' => $d, 'dim' => $dim,
            'c' => ['contact' => ['name' => $contact, 'sub' => $sub, 'avatar' => $avatar], 'messages' => $msgs, 'options' => $options],
            's' => ['answer' => $answer, 'feedback' => $feedback], 'e' => $e];
    };

    seed_game($pdo, $m, [
        'slug' => 'chat-trampa', 'type' => 'chat', 'order' => 1, 'icon' => 'bxs-message-rounded-dots',
        'dimension' => 'proteccion', 'per_play' => 4, 'pass' => 60,
        'title' => 'Chat trampa',
        'description' => 'Conversaciones que parecen normales… hasta que llega el anzuelo.',
        'instructions' => 'Lee la conversación y elige tu respuesta. Recuerda: nadie legítimo necesita tus contraseñas ni tus códigos.',
        'items' => [
            $chat(1, 'proteccion', 'Soporte PerúEduca', 'cuenta verificada', 'bxs-user', [
                    ['them', 'Hola, te escribimos de soporte técnico de PerúEduca.'],
                    ['them', 'Estamos migrando las cuentas de los estudiantes al nuevo servidor 2026.'],
                    ['them', 'Para no perder tus cursos, envíanos tu usuario y contraseña actuales. Tienes hasta hoy a las 6 p. m.'],
                ],
                ['Ok, mi usuario es… y mi contraseña es…', 'No comparto mi contraseña. Lo consultaré con mi docente y en la web oficial.', '¿Me confirmas que eres de soporte?', 'Te mando solo mi usuario'],
                1,
                ['¡Cuenta perdida! Cambiaron tu contraseña y ya no puedes entrar.', '¡Inquebrantable! Soporte nunca pide contraseñas. Avisar al docente ayuda a alertar a todos.', 'Te dirán que sí. Nunca verifiques con el mismo remitente.', 'Con tu usuario ya tienen la mitad; luego insistirán por la contraseña.'],
                'Un «check azul» o un nombre oficial no garantiza nada. Ningún soporte técnico, administrador ni docente necesita tu contraseña (ítem 22).'),

            $chat(2, 'proteccion', 'Diego 3ro B', 'número guardado', 'bxs-user', [
                    ['them', 'Holaaa, ¿ya hiciste la tarea de CTA?'],
                    ['me', 'Sí, casi toda'],
                    ['them', 'Oye, te llegó un código de 6 dígitos de WhatsApp por error, es que estoy entrando en mi nueva cel y puse mal el número'],
                    ['them', 'Pásamelo porfa, rápido que se vence'],
                ],
                ['Te lo paso, toma: 381 204', 'No lo comparto. Te llamo ahora para confirmar que eres tú.', 'Te paso solo los primeros números', 'Reenvío el SMS completo'],
                1,
                ['Ahora el atacante controla tu WhatsApp y escribirá a tus contactos pidiendo lo mismo.', '¡Excelente! La cuenta de Diego fue robada; el atacante buscaba la tuya. Llamarlo por otro medio lo confirmó.', 'Cualquier parte del código ayuda al atacante. No se comparte.', 'Reenviar el SMS es lo mismo que dar el código.'],
                'Aunque el contacto sea conocido, su cuenta pudo ser robada. Los códigos no se comparten con nadie (ítem 19).'),

            $chat(2, 'decisiones', 'Director Amauta', '+51 966 781 330', 'bxs-user', [
                    ['them', 'Buenos días, joven. Soy el director. Guarda este número.'],
                    ['them', 'Necesito que me ayudes con algo confidencial. ¿Puedes comprar 3 tarjetas de recarga de S/ 30 y enviarme los códigos por aquí?'],
                    ['them', 'Te lo reembolso mañana en el colegio. No lo comentes con nadie, por favor.'],
                ],
                ['Claro, director, ya voy a la bodega', 'Prefiero confirmarlo en persona en el colegio o con mi tutor antes de hacer algo.', 'Le pido que me mande una foto de su DNI', 'Le digo que no tengo plata pero que le paso mi Yape'],
                1,
                ['Enviaste S/ 90 en recargas a un estafador. El director nunca te escribió.', '¡Muy bien! Autoridad + secreto + dinero = fraude. Verificar en persona lo desarma.', 'Un DNI puede ser robado o falso; no es una verificación válida.', 'Seguir la conversación le da más oportunidades para engañarte.'],
                'Se llama «fraude del CEO» o suplantación de autoridad. Un pedido confidencial, urgente y con dinero se verifica siempre en persona (ítems 10 y 13).'),

            $chat(3, 'decisiones', 'Tienda Oficial Samsung Perú', 'Business', 'bxs-shopping-bag', [
                    ['them', '¡Hola! Participaste en nuestra encuesta y ganaste un Galaxy A15'],
                    ['them', 'Solo cubre el costo de envío (S/ 15) y completa el formulario con tus datos y los de tu tarjeta.'],
                    ['them', 'Oferta válida solo por 30 minutos'],
                ],
                ['¡Genial! ¿A dónde deposito?', 'No participé en ninguna encuesta y un premio real no pide pagos. Bloqueo y reporto.', 'Le mando mis datos pero sin la tarjeta', 'Lo comparto en el grupo del salón por si alguien lo quiere'],
                1,
                ['Te cobraron el «envío» y luego usaron tu tarjeta para otras compras.', '¡Correcto! Premio inesperado + pago previo + urgencia. Bloquear y reportar es la mejor respuesta.', 'Tus datos personales sirven para otros engaños o para suplantarte.', 'Estás ayudando al estafador a llegar a más personas.'],
                'La etiqueta «Business» o un logo no prueba que sea la empresa real. Rechaza premios que exigen pagos (ítem 12) y reporta (ítem 24).'),

            $chat(3, 'reconocimiento', 'Mamá', 'número guardado', 'bxs-user', [
                    ['them', 'Hijo, se me cayó el celular al agua te escribo del cel de una amiga'],
                    ['them', 'Necesito que me pases la clave de la app del banco que te enseñé, para pagar algo urgente'],
                ],
                ['Te la paso ahorita', 'La llamo al número de mi mamá o a alguien de la familia para confirmar antes de hacer nada.', 'Le pregunto «¿cómo se llama mi perro?» y si acierta se la paso', 'Le mando una foto de la tarjeta'],
                1,
                ['Era un estafador. Vaciaron la cuenta familiar.', '¡Perfecto! Tu mamá estaba bien y nunca escribió. Llamar por otro canal salvó el dinero de la familia.', 'Ese dato puede estar en tus redes sociales. No es una verificación segura.', 'Con la foto de la tarjeta pueden hacer compras.'],
                'Aunque diga ser un familiar, las contraseñas no se entregan por chat. Verifica con una llamada (ítem 10).'),

            $chat(1, 'proteccion', 'Garena Soporte', 'cuenta nueva', 'bxs-joystick', [
                    ['them', 'Detectamos que tu cuenta usa hacks. Será baneada en 1 hora.'],
                    ['them', 'Si no fuiste tú, verifica tu identidad enviando tu correo, contraseña y el código que te llegará.'],
                ],
                ['Envío todo, no quiero que me baneen', 'No envío nada. Reviso desde la app oficial del juego y activo la verificación en dos pasos.', 'Le envío solo el correo', 'Le digo que yo no uso hacks y le mando mi contraseña para que revise'],
                1,
                ['Perdiste la cuenta y todos tus objetos del juego.', '¡Muy bien! Las sanciones reales se ven dentro del juego. Tu cuenta sigue segura.', 'Con tu correo pueden iniciar el robo de la cuenta.', 'Nunca entregues tu contraseña para «demostrar» algo.'],
                'Amenaza + plazo + pedido de contraseña y código. Revisa siempre desde la app oficial.'),
        ],
    ]);

    $cl = function ($d, $dim, $content, $answer, $signals, $e) {
        return ['d' => $d, 'dim' => $dim, 'c' => $content, 's' => ['answer' => $answer, 'signals' => $signals], 'e' => $e];
    };

    seed_game($pdo, $m, [
        'slug' => 'phishing-experto', 'type' => 'classify', 'order' => 2, 'icon' => 'bxs-bullseye',
        'dimension' => 'reconocimiento', 'per_play' => 6, 'pass' => 60,
        'title' => 'Casos difíciles',
        'description' => 'Mensajes casi perfectos. ¿Podrás detectar el anzuelo?',
        'instructions' => 'Estos mensajes están bien escritos y parecen reales. Revisa remitente, enlaces, lo que te piden y el contexto antes de decidir.',
        'items' => [
            $cl(2, 'reconocimiento', ['channel' => 'email', 'from_name' => 'Instagram', 'from' => 'security@mail.instagram.com', 'subject' => 'Código de inicio de sesión', 'time' => '06:41',
                    'body' => "Hola, @tu_usuario:\nAlguien intentó iniciar sesión en tu cuenta. Si fuiste tú, usa este código: 551 902.\nSi no fuiste tú, cambia tu contraseña desde la aplicación de Instagram."],
                'legit', ['Dominio oficial mail.instagram.com', 'No pide que respondas ni entregues el código', 'Te sugiere actuar desde la app'],
                'Es un correo legítimo de seguridad. Si no fuiste tú, cambia la contraseña desde la app y nunca compartas el código.'),
            $cl(3, 'reconocimiento', ['channel' => 'email', 'from_name' => 'Microsoft 365', 'from' => 'no-reply@microsoft-365.support', 'subject' => 'Tienes 3 mensajes retenidos', 'time' => '11:02',
                    'body' => "Hola, estudiante:\nTres mensajes de tu docente fueron retenidos por nuestro filtro. Revísalos antes de que se eliminen de forma automática.",
                    'link' => ['text' => 'Revisar mensajes retenidos', 'url' => 'https://microsoft-365.support/quarantine/login']],
                'phishing', ['Dominio microsoft-365.support no es de Microsoft', 'Te lleva a una página de inicio de sesión', 'Presión leve: «antes de que se eliminen»'],
                'Muy bien escrito, pero el dominio es falso y el enlace lleva a un «login». Los mensajes retenidos se revisan entrando por tu cuenta oficial.'),
            $cl(3, 'reconocimiento', ['channel' => 'sms', 'from_name' => 'BCP', 'from' => 'BCP', 'time' => '14:20',
                    'body' => 'BCP: Se registro un consumo de S/ 1,299.00 en SAGA FALABELLA. Si no reconoces esta operacion llama al (01) 311-9898 o ingresa a',
                    'link' => ['text' => 'viabcp.com.pe-alerta.info', 'url' => 'https://viabcp.com.pe-alerta.info']],
                'phishing', ['El remitente «BCP» puede falsificarse en SMS', 'El enlace real es pe-alerta.info', 'Genera miedo con un consumo alto'],
                'Los SMS pueden mostrar un nombre falso como remitente. El dominio real es «pe-alerta.info». Si te preocupa un consumo, revisa desde la app oficial.'),
            $cl(2, 'decisiones', ['channel' => 'whatsapp', 'from_name' => 'Tutor Prof. Luis', 'from' => 'Contacto guardado', 'time' => '17:30',
                    'body' => 'Buenas tardes, chicos. Mañana la clase de tutoría será en la sala de cómputo. Traigan sus auriculares. No olviden cerrar sesión al terminar como vimos en clase'],
                'legit', ['Contacto guardado y conocido', 'Mensaje informativo, sin enlaces', 'No pide datos, dinero ni códigos'],
                'Mensaje legítimo y sin solicitudes. Recuerda: la sospecha aparece cuando alguien pide datos, dinero, códigos o te apura.'),
            $cl(3, 'reconocimiento', ['channel' => 'email', 'from_name' => 'Tu compañera Andrea', 'from' => 'andrea.quispe@gmail.com', 'subject' => 'Fotos del paseo', 'time' => '20:15',
                    'body' => "¡Hola! Te comparto las fotos del paseo a Ayabaca. Salimos geniales jaja. Descarga el archivo, está comprimido porque pesaba mucho.",
                    'attachment' => 'Fotos_Paseo_Ayabaca.zip.exe'],
                'phishing', ['Adjunto con doble extensión .zip.exe (programa ejecutable)', 'La cuenta de tu compañera pudo ser robada', 'Usa la curiosidad como gancho'],
                'Aunque venga de alguien conocido, un .exe disfrazado es malware. Pregúntale a Andrea por otro medio si ella lo envió.'),
            $cl(2, 'reconocimiento', ['channel' => 'social', 'from_name' => 'minedu_becas_2026', 'from' => '@minedu_becas_2026', 'time' => '12:00',
                    'body' => ' BECA TABLET 2026: el Minedu entregará tablets a estudiantes de secundaria. Inscríbete con tu DNI, el DNI de tu apoderado y el número de su tarjeta para el «depósito de garantía».',
                    'link' => ['text' => 'becas-minedu.online', 'url' => 'https://becas-minedu.online']],
                'phishing', ['Cuenta no oficial en redes', 'Pide número de tarjeta como «garantía»', 'Dominio .online en lugar de .gob.pe'],
                'Los programas del Minedu se publican en gob.pe/minedu y nunca piden datos de tarjetas.'),
            $cl(3, 'decisiones', ['channel' => 'email', 'from_name' => 'Google', 'from' => 'no-reply@accounts.google.com', 'subject' => 'Se activó la verificación en 2 pasos', 'time' => '09:12',
                    'body' => "Activaste la verificación en 2 pasos en tu cuenta de Google.\nA partir de ahora, cuando inicies sesión en un dispositivo nuevo, te pediremos una segunda confirmación.\nSi no hiciste este cambio, revisa la seguridad de tu cuenta desde tu configuración de Google."],
                'legit', ['Dominio oficial', 'Solo confirma una acción que tú hiciste', 'No pide datos ni tiene enlaces urgentes'],
                'Legítimo. Confirmaciones de cambios que TÚ hiciste son normales. Si no lo hiciste, revisa desde la configuración oficial.'),
            $cl(3, 'reconocimiento', ['channel' => 'sms', 'from_name' => '+51 940 772 115', 'from' => '+51 940 772 115', 'time' => '07:05',
                    'body' => 'Hola, soy Rosa de la UGEL Ayabaca. Tu hijo(a) fue seleccionado(a) para el programa de laptops. Confirma respondiendo con el código que te llegará por SMS en un momento.'],
                'phishing', ['Número personal que dice ser de una institución', 'Pide reenviar un código que llegará', 'Premio inesperado'],
                'El «código que te llegará» es de tu WhatsApp o banco: si lo entregas, pierdes la cuenta. Las instituciones no piden códigos.'),
        ],
    ]);

    seed_game($pdo, $m, [
        'slug' => 'prueba-final', 'type' => 'quiz', 'order' => 3, 'icon' => 'bxs-flag-checkered',
        'dimension' => 'decisiones', 'per_play' => 8, 'pass' => 70,
        'title' => 'Prueba final PhishHunter',
        'description' => 'La prueba integradora: reconocimiento, decisión y protección.',
        'instructions' => 'Responde con calma. Esta prueba integra todo lo aprendido y necesitas 70 % para aprobar.',
        'items' => [
            ['d' => 1, 'dim' => 'reconocimiento', 'c' => ['question' => '¿Cuál de estas combinaciones es MÁS típica de un phishing?', 'options' => ['Mensaje informativo sin enlaces', 'Urgencia + solicitud de contraseña + enlace a un dominio extraño', 'Recordatorio de clase del tutor', 'Confirmación de una acción que tú hiciste']],
             's' => ['answer' => 1], 'e' => 'Las señales combinadas son la mejor evidencia.'],
            ['d' => 2, 'dim' => 'reconocimiento', 'c' => ['question' => 'En https://perueduca.pe.aula-virtual.site/ingreso el dominio real es…', 'options' => ['perueduca.pe', 'aula-virtual.site', 'ingreso', 'pe.aula']],
             's' => ['answer' => 1], 'e' => 'Lo que está justo antes de la primera «/»: aula-virtual.site.'],
            ['d' => 2, 'dim' => 'reconocimiento', 'c' => ['question' => 'Un mensaje viene de un contacto conocido pero te pide un código de verificación. ¿Qué indica?', 'options' => ['Que es seguro porque lo conoces', 'Que su cuenta pudo ser robada; hay que verificar por otro medio', 'Que ganaste algo', 'Nada']],
             's' => ['answer' => 1], 'e' => 'Las cuentas robadas se usan para engañar a los contactos.'],
            ['d' => 1, 'dim' => 'decisiones', 'c' => ['question' => 'La forma más segura de ingresar a tu cuenta del banco o de la plataforma es…', 'options' => ['Usar el enlace que llega por SMS', 'Abrir la app oficial o escribir la dirección oficial', 'Buscar el enlace en un grupo de WhatsApp', 'Usar el enlace de un anuncio']],
             's' => ['answer' => 1], 'e' => 'Ingresar por la puerta oficial evita páginas clonadas (ítem 9).'],
            ['d' => 2, 'dim' => 'decisiones', 'c' => ['question' => 'Tu «tío» con número nuevo te pide dinero urgente y en secreto. ¿Qué haces primero?', 'options' => ['Le envío el dinero', 'Lo llamo a su número de siempre o consulto con mi familia', 'Le pido una foto', 'Le respondo con insultos']],
             's' => ['answer' => 1], 'e' => 'Verificar por un canal conocido desarma la suplantación (ítem 10).'],
            ['d' => 3, 'dim' => 'decisiones', 'c' => ['question' => 'Detectaste un phishing en el grupo del salón. La mejor acción completa es…', 'options' => ['Ignorarlo', 'No abrirlo, reportarlo en la app y avisar al tutor para que alerte a todos', 'Reenviarlo a otros grupos', 'Responder al remitente']],
             's' => ['answer' => 1], 'e' => 'Reportar y avisar protege a todo el grupo (ítems 14 y 16).'],
            ['d' => 1, 'dim' => 'proteccion', 'c' => ['question' => '¿Cuál es la contraseña más segura?', 'options' => ['Amauta2026', 'Cielo-Choclo-Tambor-Lago4!', '11111111', 'MiNombre']],
             's' => ['answer' => 1], 'e' => 'Larga, con palabras sin relación, número y signo.'],
            ['d' => 2, 'dim' => 'proteccion', 'c' => ['question' => 'Si alguien consigue tu contraseña, ¿qué medida puede impedir que entre a tu cuenta?', 'options' => ['Cambiar la foto de perfil', 'La verificación en dos pasos', 'Borrar el historial', 'Tener muchos seguidores']],
             's' => ['answer' => 1], 'e' => 'El segundo factor bloquea el acceso aunque tengan tu clave (ítem 18).'],
            ['d' => 3, 'dim' => 'proteccion', 'c' => ['question' => '«Soy el administrador de la plataforma, necesito tu contraseña para migrar tu cuenta». Respuesta correcta:', 'options' => ['Dársela porque es autoridad', 'No darla nunca y reportarlo', 'Darla y luego cambiarla', 'Darle una pista']],
             's' => ['answer' => 1], 'e' => 'Ningún administrador necesita tu contraseña (ítem 22).'],
            ['d' => 3, 'dim' => 'proteccion', 'c' => ['question' => 'Escribiste tu contraseña en una página falsa. ¿Qué pasos siguen?', 'options' => ['Nada, ya pasó', 'Cambiar la contraseña desde el sitio oficial, cerrar sesiones abiertas, activar 2 pasos y avisar a un adulto', 'Crear otra cuenta y abandonar la anterior', 'Esperar un correo del atacante']],
             's' => ['answer' => 1], 'e' => 'Actuar rápido y pedir ayuda reduce el daño.'],
            ['d' => 2, 'dim' => 'reconocimiento', 'c' => ['question' => 'Un QR en un afiche parece pegado encima de otro. ¿Qué señal es?', 'options' => ['Señal de modernidad', 'Posible manipulación para llevarte a una página falsa', 'Descuento oficial', 'Ninguna']],
             's' => ['answer' => 1], 'e' => 'Los QR superpuestos son una técnica de phishing (quishing) (ítem 7).'],
            ['d' => 1, 'dim' => 'proteccion', 'c' => ['question' => 'En la sala de cómputo, al terminar tu sesión debes…', 'options' => ['Cerrar sesión en todas tus cuentas', 'Dejar la cuenta abierta para el siguiente', 'Guardar la contraseña en el navegador', 'Solo apagar el monitor']],
             's' => ['answer' => 0], 'e' => 'Cerrar sesión evita accesos no autorizados (ítem 23).'],
        ],
    ]);
};
