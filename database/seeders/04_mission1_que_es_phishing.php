<?php
/**
 * Misión 1 · ¿Qué es el phishing?  (Sesión 1 de la intervención)
 * Dimensión principal: reconocimiento de señales.
 */
return function (PDO $pdo): void {
    $m = seed_mission($pdo, [
        'slug' => 'que-es-phishing', 'order' => 1, 'icon' => 'bx-anchor', 'color' => '#8BC34A',
        'dimension' => 'reconocimiento',
        'title' => '¿Qué es el phishing?',
        'subtitle' => 'Conoce el anzuelo antes de que te pesquen',
        'description' => 'Descubre cómo los estafadores se hacen pasar por bancos, colegios, amigos o juegos para robar tus datos, y por qué cualquiera puede caer si no se detiene a revisar.',
        'objectives' => [
            'Explicar con tus palabras qué es el phishing y qué busca el atacante.',
            'Distinguir los canales: correo (phishing), SMS (smishing), llamadas (vishing) y redes sociales.',
            'Clasificar mensajes reales y fraudulentos observando más de una señal.',
        ],
        'lesson' => [
            ['icon' => 'bx-anchor', 'title' => 'El anzuelo digital', 'text' => 'El phishing es un engaño en el que alguien se hace pasar por una entidad o persona de confianza (tu banco, el colegio, un amigo, un juego) para que entregues contraseñas, códigos, datos personales o dinero.'],
            ['icon' => 'bxs-envelope', 'title' => 'Llega por muchos canales', 'text' => 'Por correo se llama phishing; por SMS, smishing; por llamada o audio, vishing. También llega por WhatsApp, Instagram, TikTok o dentro de los juegos. Cambia el canal, pero el truco es el mismo: manipular tu decisión.'],
            ['icon' => 'bxs-brain', 'title' => 'Juegan con tus emociones', 'text' => 'Usan urgencia («tu cuenta se cerrará hoy»), miedo («detectamos un problema»), premios («ganaste un celular») o autoridad («soy el director»). Si sientes presión, es momento de hacer una pausa.'],
            ['icon' => 'bxs-bullseye', 'title' => 'Siempre piden algo', 'text' => 'Al final, el mensaje te pide una acción: abrir un enlace, descargar un archivo, responder con datos o entregar un código. Esa acción es el anzuelo.'],
            ['icon' => 'bxs-like', 'title' => 'Equivocarse no es de tontos', 'text' => 'El phishing está diseñado para engañar incluso a adultos con experiencia. Lo importante es detenerse, revisar y pedir ayuda. Aquí puedes equivocarte sin riesgo: todos los mensajes son simulados.'],
        ],
        'xp' => 100, 'coins' => 30,
    ]);

    // -----------------------------------------------------------------
    //  Juego: ¿Phishing o legítimo?
    // -----------------------------------------------------------------
    seed_game($pdo, $m, [
        'slug' => 'phishing-o-legitimo', 'type' => 'classify', 'order' => 1, 'icon' => 'bx-water',
        'dimension' => 'reconocimiento', 'per_play' => 6, 'pass' => 60,
        'title' => '¿Phishing o legítimo?',
        'description' => 'Revisa cada mensaje y decide si es un anzuelo o un mensaje confiable.',
        'instructions' => 'Lee con calma cada mensaje. Observa quién lo envía, qué te pide y cómo te lo pide. No hay tiempo límite: revisar bien vale más que responder rápido.',
        'items' => [
            ['d' => 1, 'c' => ['channel' => 'sms', 'from_name' => '+51 944 118 207', 'from' => '+51 944 118 207', 'time' => '08:12',
                'body' => 'BCP: Su cuenta ha sido BLOQUEADA por actividad inusual. Para evitar la suspension definitiva valide sus datos hoy aqui:',
                'link' => ['text' => 'bcp-validacion.com/pe', 'url' => 'http://bcp-validacion.com/pe']],
             's' => ['answer' => 'phishing', 'signals' => ['Número de celular desconocido haciéndose pasar por un banco', 'Urgencia y amenaza de bloqueo', 'Enlace que no es el dominio oficial del banco (viabcp.com)']],
             'e' => 'Los bancos no te piden validar datos mediante enlaces en un SMS. El dominio «bcp-validacion.com» no es el oficial. Si dudas, entra a la app oficial del banco o llama a su número oficial.'],

            ['d' => 1, 'c' => ['channel' => 'whatsapp', 'from_name' => 'Sorteos Free Fire', 'from' => '+1 (302) 555-0148', 'time' => '19:40',
                'body' => '¡FELICIDADES! Fuiste seleccionado para recibir 5000 DIAMANTES GRATIS. Solo ingresa tu usuario y contraseña de Garena antes de las 12 de la noche',
                'link' => ['text' => 'diamantes-gratis-ff.xyz', 'url' => 'http://diamantes-gratis-ff.xyz']],
             's' => ['answer' => 'phishing', 'signals' => ['Premio demasiado bueno para ser verdad', 'Pide usuario y contraseña', 'Plazo límite para presionarte', 'Número extranjero y dominio .xyz']],
             'e' => 'Ningún juego regala diamantes pidiéndote tu contraseña. Es una de las trampas más comunes entre estudiantes: te roban la cuenta del juego y luego escriben a tus contactos.'],

            ['d' => 1, 'c' => ['channel' => 'email', 'from_name' => 'PerúEduca', 'from' => 'notificaciones@perueduca.pe', 'subject' => 'Nuevo curso disponible en tu aula', 'time' => '10:05',
                'body' => "Hola, estudiante:\nTu docente publicó un nuevo recurso en el aula virtual «Ciudadanía digital». Puedes revisarlo cuando ingreses a PerúEduca con tu usuario de siempre.\n\nEste mensaje es informativo; no respondas a este correo."],
             's' => ['answer' => 'legit', 'signals' => ['Remitente con el dominio oficial perueduca.pe', 'No pide datos ni contraseñas', 'No trae enlaces ni urgencia; te invita a entrar por tu cuenta']],
             'e' => 'Es legítimo: viene del dominio oficial, no te presiona, no pide datos y te sugiere entrar por el camino de siempre. Aun así, la mejor práctica es ingresar escribiendo tú mismo la dirección.'],

            ['d' => 2, 'c' => ['channel' => 'email', 'from_name' => 'Netflix', 'from' => 'soporte@netflix-pagos-peru.com', 'subject' => 'Problema con tu método de pago', 'time' => '07:58',
                'body' => "No pudimos procesar tu último pago. Actualiza tu tarjeta en las próximas 24 horas o tu cuenta será suspendida.\n\n[ACTUALIZAR PAGO]",
                'link' => ['text' => 'ACTUALIZAR PAGO', 'url' => 'http://netflix-pagos-peru.com/actualizar']],
             's' => ['answer' => 'phishing', 'signals' => ['Dominio del remitente falso (netflix-pagos-peru.com)', 'Amenaza de suspensión en 24 horas', 'Pide datos de tarjeta mediante un enlace']],
             'e' => 'El nombre dice «Netflix», pero la dirección real es netflix-pagos-peru.com. Cualquiera puede poner el nombre que quiera; lo que importa es el dominio después de la @.'],

            ['d' => 2, 'c' => ['channel' => 'sms', 'from_name' => 'Olva Courier', 'from' => '+51 912 330 471', 'time' => '13:22',
                'body' => 'OLVA: Tu paquete no pudo ser entregado por direccion incompleta. Paga S/ 2.50 de reprogramacion aqui:',
                'link' => ['text' => 'olva-entregas.top/pago', 'url' => 'http://olva-entregas.top/pago']],
             's' => ['answer' => 'phishing', 'signals' => ['No esperabas ningún paquete', 'Pide un pago pequeño para parecer inofensivo', 'Dominio extraño .top']],
             'e' => 'El «pago pequeño» es un truco: lo que buscan son los datos de tu tarjeta. Si esperas un envío, revisa el estado desde la web oficial de la empresa escribiendo tú mismo la dirección.'],

            ['d' => 2, 'c' => ['channel' => 'whatsapp', 'from_name' => 'Mamá', 'from' => 'Contacto guardado', 'time' => '18:03',
                'body' => 'Hijo, ya salí del trabajo. ¿Compras pan cuando regreses del colegio? Te dejé plata en la mesa.'],
             's' => ['answer' => 'legit', 'signals' => ['Contacto guardado y conocido', 'Conversación habitual, sin enlaces', 'No pide datos, códigos ni dinero']],
             'e' => 'Mensaje normal: contacto conocido, sin enlaces ni solicitudes de datos o dinero. Sospecha si un contacto conocido de pronto te pide códigos, dinero urgente o que abras un enlace.'],

            ['d' => 2, 'c' => ['channel' => 'social', 'from_name' => 'instagram.soporte_oficial', 'from' => '@instagram.soporte_oficial', 'time' => '21:15',
                'body' => ' Tu cuenta infringió nuestras normas de derechos de autor. Si no apelas en 12 horas será eliminada. Completa el formulario de apelación:',
                'link' => ['text' => 'bit.ly/apelar-ig', 'url' => 'https://bit.ly/apelar-ig']],
             's' => ['answer' => 'phishing', 'signals' => ['Cuenta que imita a soporte oficial', 'Amenaza de eliminación con plazo', 'Enlace acortado que oculta el destino real']],
             'e' => 'Instagram no te escribe por mensaje directo desde cuentas como «soporte_oficial». Las alertas reales aparecen en la configuración de la app (Centro de cuentas / Correos de Instagram).'],

            ['d' => 3, 'c' => ['channel' => 'email', 'from_name' => 'Dirección I.E. AMAUTA', 'from' => 'direccion.amauta@gmail.com', 'subject' => 'URGENTE: actualización de datos para becas', 'time' => '09:30',
                'body' => "Estimado estudiante:\nPor encargo de UGEL debe enviar HOY su DNI escaneado, el número de celular de sus padres y la clave de su correo institucional para ser considerado en las becas 2026.\n\nAtte. Dirección",
                'attachment' => 'Formulario_Becas.docm'],
             's' => ['answer' => 'phishing', 'signals' => ['Pide la clave del correo: ninguna institución la necesita', 'Urgencia («HOY»)', 'Adjunto con macros (.docm)', 'Correo gratuito que dice ser la dirección']],
             'e' => 'Nadie del colegio necesita tu contraseña. Además, un archivo .docm puede contener código malicioso. Ante la duda, pregunta en persona al docente o en la dirección.'],

            ['d' => 3, 'c' => ['channel' => 'sms', 'from_name' => 'Yape', 'from' => 'Yape', 'time' => '16:47',
                'body' => 'Tu codigo de verificacion de Yape es 482913. No lo compartas con nadie. Yape nunca te lo pedira.'],
             's' => ['answer' => 'legit', 'signals' => ['Solo informa un código que tú solicitaste', 'Te advierte que no lo compartas', 'No trae enlaces']],
             'e' => 'Es un mensaje de verificación normal si TÚ estabas iniciando sesión. Lo importante: ese código nunca se comparte. Si no lo solicitaste, alguien intenta entrar a tu cuenta; no lo entregues y cambia tu clave.'],

            ['d' => 3, 'c' => ['channel' => 'email', 'from_name' => 'Google', 'from' => 'no-reply@accounts.google.com', 'subject' => 'Alerta de seguridad', 'time' => '22:10',
                'body' => "Se inició sesión en tu cuenta desde un nuevo dispositivo Windows.\nSi fuiste tú, no necesitas hacer nada. Si no fuiste tú, revisa tu actividad desde la configuración de tu cuenta de Google."],
             's' => ['answer' => 'legit', 'signals' => ['Dominio oficial accounts.google.com', 'No pide contraseña ni datos', 'Te sugiere revisar desde la configuración, no desde un enlace urgente']],
             'e' => 'Las alertas reales informan y te dejan revisar por tu cuenta. Aun así, lo más seguro es abrir la app o escribir myaccount.google.com tú mismo, en lugar de tocar botones del correo.'],
        ],
    ]);

    // -----------------------------------------------------------------
    //  Mini-prueba 1
    // -----------------------------------------------------------------
    seed_game($pdo, $m, [
        'slug' => 'prueba-1', 'type' => 'quiz', 'order' => 2, 'icon' => 'bxs-edit',
        'dimension' => 'reconocimiento', 'per_play' => 5, 'pass' => 60,
        'title' => 'Mini-prueba: el anzuelo',
        'description' => 'Demuestra lo que aprendiste sobre qué es el phishing.',
        'instructions' => 'Responde cada pregunta. Verás la explicación después de cada respuesta. Tu resultado se guarda como evidencia de aprendizaje.',
        'items' => [
            ['d' => 1, 'dim' => 'reconocimiento', 'c' => ['question' => '¿Qué es el phishing?', 'options' => ['Un virus que daña la pantalla del celular', 'Un engaño en el que alguien se hace pasar por una entidad o persona de confianza para robar datos o dinero', 'Una aplicación para pescar en línea', 'Un tipo de publicidad legal']],
             's' => ['answer' => 1], 'e' => 'El phishing es un engaño (ingeniería social): suplantan a alguien confiable para que entregues información o hagas algo inseguro.'],
            ['d' => 1, 'dim' => 'reconocimiento', 'c' => ['question' => 'Si el engaño llega por SMS (mensaje de texto), se llama…', 'options' => ['Vishing', 'Smishing', 'Spam', 'Hacking']],
             's' => ['answer' => 1], 'e' => 'Smishing = SMS + phishing. Vishing es por voz (llamadas o audios).'],
            ['d' => 1, 'dim' => 'reconocimiento', 'c' => ['question' => '¿Qué emoción NO suelen usar los mensajes de phishing para presionarte?', 'options' => ['Urgencia', 'Miedo', 'Codicia por un premio', 'Calma y paciencia para que lo pienses bien']],
             's' => ['answer' => 3], 'e' => 'El atacante quiere que actúes rápido y sin pensar. Un mensaje que te da tiempo y te invita a verificar suele ser una buena señal.'],
            ['d' => 2, 'dim' => 'reconocimiento', 'c' => ['question' => 'Un mensaje está muy bien escrito, sin faltas de ortografía y con el logo oficial. Entonces…', 'options' => ['Seguro es real', 'Aún puede ser falso: hay que revisar remitente, enlace y lo que pide', 'Solo es falso si tiene faltas', 'Es real si tiene el logo']],
             's' => ['answer' => 1], 'e' => 'Copiar un logo es muy fácil y muchos ataques están bien redactados. Decide con varias señales, no solo por la ortografía.'],
            ['d' => 2, 'dim' => 'reconocimiento', 'c' => ['question' => '¿Qué buscan casi siempre los atacantes?', 'options' => ['Que aprendas algo nuevo', 'Que hagas una acción: abrir un enlace, descargar un archivo, entregar datos o un código', 'Que borres tus fotos', 'Que actualices tu celular']],
             's' => ['answer' => 1], 'e' => 'El mensaje siempre termina en una acción. Identificar qué te piden es clave para detectar el anzuelo.'],
            ['d' => 2, 'dim' => 'decisiones', 'c' => ['question' => 'Te equivocaste y abriste un enlace sospechoso. ¿Qué es lo mejor?', 'options' => ['Ocultarlo por vergüenza', 'Avisar pronto a un docente o familiar y cambiar la contraseña si la escribiste', 'Esperar a ver qué pasa', 'Reenviarlo a tus amigos para que opinen']],
             's' => ['answer' => 1], 'e' => 'Pedir ayuda rápido reduce el daño. Equivocarse le pasa a cualquiera; lo importante es actuar a tiempo.'],
            ['d' => 3, 'dim' => 'reconocimiento', 'c' => ['question' => '«Spear phishing» es…', 'options' => ['Un ataque dirigido a una persona o grupo específico con datos personalizados', 'Un ataque solo por llamadas', 'Un antivirus', 'Un juego en línea']],
             's' => ['answer' => 0], 'e' => 'En el spear phishing el atacante investiga a su víctima (nombre, colegio, amigos) para que el mensaje parezca más creíble.'],
            ['d' => 3, 'dim' => 'reconocimiento', 'c' => ['question' => '¿Por dónde puede llegar el phishing?', 'options' => ['Solo por correo', 'Solo por redes sociales', 'Por correo, SMS, llamadas, WhatsApp, redes sociales y hasta dentro de juegos', 'Solo por computadoras']],
             's' => ['answer' => 2], 'e' => 'El canal cambia, pero la técnica de engaño es la misma. Mantén la misma desconfianza en cualquier canal.'],
        ],
    ]);
};
