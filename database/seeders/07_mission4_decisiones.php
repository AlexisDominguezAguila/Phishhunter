<?php
/**
 * Misión 4 · Decide con cabeza fría  (Sesión 4)
 * Dimensión: toma de decisiones seguras (ítems 9–16).
 */
return function (PDO $pdo): void {
    $m = seed_mission($pdo, [
        'slug' => 'decide-con-cabeza-fria', 'order' => 4, 'icon' => 'bxs-compass', 'color' => '#2BB39A',
        'dimension' => 'decisiones',
        'title' => 'Decide con cabeza fría',
        'subtitle' => 'Pausa, verifica y reporta',
        'description' => 'Reconocer el anzuelo es la mitad del trabajo; la otra mitad es decidir qué hacer. Practica la pausa segura, la verificación por otro canal y el reporte a una persona responsable.',
        'objectives' => [
            'Aplicar la regla «Pausa – Verifica – Reporta» ante un mensaje dudoso.',
            'Confirmar solicitudes inusuales por otro canal (llamada, en persona, app oficial).',
            'Saber a quién avisar y qué hacer si ya abriste un enlace o descargaste algo.',
        ],
        'lesson' => [
            ['icon' => 'bx-pause', 'title' => '1. Pausa', 'text' => 'Si un mensaje te genera prisa, miedo o emoción, no respondas de inmediato. Respirar 10 segundos rompe el efecto de la urgencia.'],
            ['icon' => 'bxs-phone', 'title' => '2. Verifica por otro canal', 'text' => 'Si «tu amigo» te pide dinero, llámalo. Si «el banco» te escribe, abre su app oficial. Nunca verifiques respondiendo al mismo mensaje ni usando el número que te dieron ahí.'],
            ['icon' => 'bxs-megaphone', 'title' => '3. Reporta', 'text' => 'Avisa a tu docente, tutor o familia. Usa el botón «Reportar» de la app (WhatsApp, Instagram, Gmail). Reportar protege a otros compañeros.'],
            ['icon' => 'bx-block', 'title' => 'No respondas ni reenvíes', 'text' => 'Responder confirma que tu número está activo. Reenviar puede hacer que otros caigan. Bloquea y reporta.'],
            ['icon' => 'bxs-error-circle', 'title' => 'Si ya caíste', 'text' => 'No te castigues. Avisa de inmediato, cambia tu contraseña desde la app oficial, cierra sesiones abiertas y activa la verificación en dos pasos. Actuar rápido reduce el daño.'],
        ],
        'xp' => 120, 'coins' => 35,
    ]);

    $sc = function ($d, $title, $situation, $message, $options, $answer, $feedback, $e, $dim = 'decisiones') {
        return ['d' => $d, 'dim' => $dim,
            'c' => ['title' => $title, 'situation' => $situation, 'message' => $message, 'question' => '¿Qué harías?', 'options' => $options],
            's' => ['answer' => $answer, 'feedback' => $feedback], 'e' => $e];
    };

    seed_game($pdo, $m, [
        'slug' => 'que-harias', 'type' => 'scenario', 'order' => 1, 'icon' => 'bx-question-mark',
        'dimension' => 'decisiones', 'per_play' => 5, 'pass' => 60,
        'title' => '¿Qué harías?',
        'description' => 'Vive situaciones reales y elige la acción más segura.',
        'instructions' => 'Lee la situación y elige qué harías. Cada decisión tiene una consecuencia simulada. Piensa en la regla: Pausa – Verifica – Reporta.',
        'items' => [
            $sc(1, 'El tío que necesita plata', 'Te llega un WhatsApp de un número desconocido.',
                ['from' => '+51 931 442 019', 'text' => 'Sobrino, soy tu tío Jorge, cambié de número. Estoy en un apuro, ¿me yapeas S/ 200? Mañana te devuelvo, no le digas a tu mamá.'],
                ['Le yapeo rápido, es familia', 'Llamo a mi tío a su número de siempre o le pregunto a mi mamá antes de hacer algo', 'Le pido una foto para confirmar', 'Le respondo pidiéndole su DNI'],
                1,
                ['Perdiste S/ 200: era un estafador que usó un «número nuevo» y pidió secreto.', '¡Bien! Verificar por otro canal desarma el engaño. Tu tío real no sabía nada.', 'Una foto se puede sacar de sus redes sociales; no es verificación suficiente.', 'Seguir la conversación con el estafador no sirve para verificar y le da tiempo para convencerte.'],
                'Pedir secreto + número nuevo + dinero urgente = señales de fraude. Verifica siempre por un canal que tú ya conoces.'),

            $sc(1, 'Premio en el juego', 'Mientras juegas en línea, aparece un mensaje en el chat del juego.',
                ['from' => 'ADMIN_Garena_Oficial', 'text' => 'Ganaste un skin legendario Entra a este enlace y coloca tu correo y contraseña para reclamarlo en 10 minutos.'],
                ['Entro al enlace y coloco mis datos', 'Ignoro el mensaje, no uso el enlace y lo reporto en el juego', 'Le paso los datos de mi cuenta secundaria', 'Le pregunto al «admin» si es real'],
                1,
                ['Te robaron la cuenta y todo lo que habías conseguido en el juego.', '¡Correcto! Los premios reales aparecen dentro del juego, nunca piden contraseña. Reportar ayuda a bloquear al estafador.', 'Aun así entregas credenciales; además muchos usan la misma clave en varias cuentas.', 'El estafador te dirá que sí es real. Nunca verifiques con el mismo remitente.'],
                'Los administradores reales nunca piden tu contraseña. Reportar la cuenta en el juego protege a otros jugadores.'),

            $sc(2, 'El archivo del grupo', 'En el grupo de WhatsApp del salón, un número que no conoces comparte un archivo.',
                ['from' => 'Número desconocido (en el grupo)', 'text' => ' Notas_finales_3ro.apk — Descarguen para ver sus notas antes que salgan oficialmente'],
                ['Lo descargo para ver mis notas', 'No lo descargo y aviso al tutor o docente del aula', 'Lo reenvío a otros grupos', 'Lo descargo pero no lo abro'],
                1,
                ['Instalaste una app maliciosa que puede leer tus mensajes y códigos.', '¡Bien hecho! Un .apk inesperado puede ser malware. Avisar al docente permite alertar a todo el salón.', 'Estás ayudando a que el ataque se propague.', 'Descargar ya es un riesgo, y luego es fácil abrirlo por error.'],
                'Las notas oficiales se revisan en los canales del colegio. Un archivo .apk instala aplicaciones: nunca lo abras si no sabes de dónde viene.'),

            $sc(2, 'Llamada del «banco»', 'Recibes una llamada al celular de tu papá mientras él no está.',
                ['from' => 'Llamada · +51 1 311 9898', 'text' => '«Buenas tardes, llamamos del área de seguridad del banco. Detectamos una compra sospechosa. Para anularla, díctenos el código que acaba de llegar por SMS.»'],
                ['Dicto el código para ayudar a mi papá', 'No doy ningún código, cuelgo y le cuento a mi papá para que llame al número oficial del banco', 'Pido que me llamen más tarde', 'Doy solo la mitad del código'],
                1,
                ['Con ese código autorizaron una transferencia desde la cuenta de tu papá.', '¡Correcto! Los bancos nunca piden códigos por teléfono. Colgar y avisar es lo más seguro.', 'Te volverán a llamar con más presión. Mejor cortar y avisar.', 'Cualquier parte del código puede servirles. No se comparte nada.'],
                'Esto es vishing (phishing por voz). Un código de verificación es como la llave de la cuenta: nadie legítimo te lo pedirá.'),

            $sc(2, 'La profesora en Instagram', 'Una cuenta con la foto de tu profesora te escribe por Instagram.',
                ['from' => '@prof.rosario.amauta_', 'text' => 'Hola, soy tu profe. Estoy organizando las notas y necesito tu usuario y contraseña de PerúEduca para subir tu trabajo. Es rápido'],
                ['Se la doy porque es mi profesora', 'No la doy; le pregunto en persona en el colegio y aviso si no fue ella', 'Le doy solo el usuario', 'Le cambio la contraseña después de dársela'],
                1,
                ['Alguien entró a tu cuenta y envió mensajes a tus compañeros haciéndose pasar por ti.', '¡Muy bien! Ningún docente necesita tu contraseña. La cuenta era falsa y tu aviso ayudó a denunciarla.', 'El usuario es la mitad de lo que necesitan; luego intentarán conseguir la contraseña.', 'En ese tiempo ya pueden haber cambiado tu clave y tomado la cuenta.'],
                'Se puede crear una cuenta falsa con la foto de cualquier persona. Una solicitud inusual se verifica en persona o por el canal oficial del colegio.'),

            $sc(3, 'Ya hice clic…', 'Abriste un enlace de un correo de «Google» y escribiste tu contraseña. Luego notas que la dirección era «goog1e-login.com».',
                ['from' => 'Pensamiento', 'text' => 'Uy… creo que caí. ¿Y ahora?'],
                ['No digo nada para que no me regañen', 'Entro a la app o a la web oficial, cambio mi contraseña, cierro sesiones abiertas, activo la verificación en dos pasos y aviso a un adulto', 'Borro el correo y listo', 'Espero a ver si pasa algo'],
                1,
                ['Pasaron días y el atacante usó tu cuenta para engañar a tus contactos.', '¡Excelente reacción! Actuar rápido y pedir ayuda reduce el daño. Equivocarse le pasa a cualquiera.', 'Borrar el correo no cambia que el atacante ya tiene tu contraseña.', 'Cada minuto cuenta: el atacante puede cambiar tu contraseña primero.'],
                'Si caes, actúa: cambia la clave desde el sitio oficial, revisa la actividad, activa 2 pasos y avisa. Comunicarlo a tiempo es una conducta de seguridad, no un error.',
                'proteccion'),

            $sc(3, 'Oferta de trabajo', 'Un mensaje ofrece trabajo desde casa.',
                ['from' => 'Recursos Humanos TikTok Perú', 'text' => 'Gana S/ 300 diarios dando «me gusta» a videos. Solo paga S/ 50 de inscripción para activar tu cuenta de trabajador.'],
                ['Pago la inscripción, es poco dinero', 'No pago nada: un trabajo real no cobra por contratarte. Lo reporto y lo comento en casa', 'Le paso mis datos para que me contacten', 'Invito a mis amigos para ganar más'],
                1,
                ['Pagaste y te pidieron cada vez más «comisiones». Nunca recibiste nada.', '¡Correcto! Ganancias exageradas + pago previo = estafa.', 'Tus datos pueden usarse para otros fraudes o para suplantarte.', 'Estarías ayudando a que otros caigan en la estafa.'],
                'Si un beneficio exige que pagues primero, es una señal clara de fraude (ítem 12: rechazar premios que exigen un pago).'),

            $sc(1, 'Mensaje sospechoso en el correo', 'Te llega un correo de «Soporte» pidiendo verificar tu cuenta del colegio. Ya sabes que es phishing.',
                ['from' => 'soporte@amauta-verificacion.com', 'text' => 'Verifique su cuenta institucional en las próximas 24 horas o será desactivada.'],
                ['Le respondo diciéndole que sé que es falso', 'Lo reporto como phishing en el correo, no respondo y aviso al docente responsable', 'Lo reenvío a mis compañeros como broma', 'Lo dejo en la bandeja sin hacer nada'],
                1,
                ['Responder confirma que tu correo está activo y te llegarán más ataques.', '¡Perfecto! Reportar ayuda a que el sistema lo bloquee para otros y avisar permite alertar al colegio.', 'Alguien podría tomarlo en serio y caer.', 'Está bien no abrirlo, pero reportarlo protege a los demás.'],
                'No respondas ni reenvíes. Usa «Reportar phishing» y avisa a un responsable (ítems 14 y 16).'),
        ],
    ]);

    seed_game($pdo, $m, [
        'slug' => 'prueba-4', 'type' => 'quiz', 'order' => 2, 'icon' => 'bxs-edit',
        'dimension' => 'decisiones', 'per_play' => 5, 'pass' => 60,
        'title' => 'Mini-prueba: decisiones seguras',
        'description' => 'Demuestra que sabes qué hacer frente a un anzuelo.',
        'instructions' => 'Elige la acción más segura en cada caso.',
        'items' => [
            ['d' => 1, 'dim' => 'decisiones', 'c' => ['question' => '¿Cuál es el orden de la regla de oro frente a un mensaje dudoso?', 'options' => ['Clic – Pregunta – Borra', 'Pausa – Verifica – Reporta', 'Responde – Reenvía – Olvida', 'Descarga – Abre – Revisa']],
             's' => ['answer' => 1], 'e' => 'Pausa para no actuar por impulso, verifica por otro canal y reporta a un responsable.'],
            ['d' => 1, 'dim' => 'decisiones', 'c' => ['question' => 'Un amigo te pide por WhatsApp un código que te llegó por SMS. ¿Qué haces?', 'options' => ['Se lo mando, es mi amigo', 'No lo comparto y lo llamo o le pregunto en persona', 'Le mando solo 3 números', 'Se lo mando y luego borro el chat']],
             's' => ['answer' => 1], 'e' => 'Los códigos no se comparten con nadie. Probablemente la cuenta de tu amigo fue robada.'],
            ['d' => 2, 'dim' => 'decisiones', 'c' => ['question' => '¿Cuál es una forma CORRECTA de verificar un mensaje del banco?', 'options' => ['Llamar al número que aparece en el mismo mensaje', 'Responder el mensaje preguntando si es real', 'Abrir la app oficial o llamar al número oficial que está en la tarjeta o en su web', 'Abrir el enlace y ver si se ve bonito']],
             's' => ['answer' => 2], 'e' => 'El número o enlace del mensaje puede ser del atacante. Verifica por un canal que tú ya conoces.'],
            ['d' => 2, 'dim' => 'decisiones', 'c' => ['question' => 'Descargaste un archivo sospechoso en la computadora del colegio. ¿Qué haces?', 'options' => ['Lo borro y no digo nada', 'Aviso de inmediato al docente o encargado de la sala de cómputo', 'Lo abro para ver qué es', 'Apago la pantalla y me voy']],
             's' => ['answer' => 1], 'e' => 'Avisar a tiempo permite revisar el equipo y proteger a los demás (ítem 15).'],
            ['d' => 2, 'dim' => 'decisiones', 'c' => ['question' => 'Un mensaje dice que ganaste un premio pero debes pagar el envío. Lo correcto es…', 'options' => ['Pagar rápido antes de que se acabe', 'Rechazarlo: un premio real no exige pagos', 'Pagar la mitad', 'Pedir que te descuenten el envío']],
             's' => ['answer' => 1], 'e' => 'Si hay que pagar para recibir un premio, no es un premio: es una estafa.'],
            ['d' => 3, 'dim' => 'decisiones', 'c' => ['question' => '¿Por qué NO debes responder a un mensaje de phishing aunque sea para insultar al estafador?', 'options' => ['Porque es de mala educación', 'Porque confirmas que tu número o correo está activo y atento', 'Porque cuesta saldo', 'Sí se debe responder']],
             's' => ['answer' => 1], 'e' => 'Responder te marca como «objetivo activo». Lo mejor es bloquear y reportar.'],
            ['d' => 3, 'dim' => 'proteccion', 'c' => ['question' => 'Si escribiste tu contraseña en una página falsa, lo PRIMERO es…', 'options' => ['Esperar a ver si pasa algo', 'Cambiar la contraseña desde el sitio oficial y avisar a un adulto', 'Crear otra cuenta', 'Borrar el historial']],
             's' => ['answer' => 1], 'e' => 'Cambia la clave cuanto antes, cierra sesiones y activa la verificación en dos pasos.'],
            ['d' => 1, 'dim' => 'decisiones', 'c' => ['question' => '¿A quién le puedes reportar un mensaje sospechoso en el colegio?', 'options' => ['A nadie, es mi problema', 'Al docente, tutor o responsable de la institución', 'Al mismo remitente', 'A un desconocido en redes']],
             's' => ['answer' => 1], 'e' => 'Reportar a un responsable ayuda a alertar a todos y a tomar medidas (ítem 14).'],
        ],
    ]);
};
