<?php
/**
 * Misión 2 · Señales de alerta  (Sesión 2)
 * Dimensión: reconocimiento de señales (ítems 1–8 del instrumento).
 */
return function (PDO $pdo): void {
    $m = seed_mission($pdo, [
        'slug' => 'senales-de-alerta', 'order' => 2, 'icon' => 'bx-radar', 'color' => '#FFC83D',
        'dimension' => 'reconocimiento',
        'title' => 'Señales de alerta',
        'subtitle' => 'Entrena tu radar para detectar anzuelos',
        'description' => 'Aprende a revisar un mensaje como un detective: remitente, asunto, saludo, urgencia, premios, adjuntos, códigos QR y enlaces. Ninguna señal sola es infalible; la clave es combinarlas.',
        'objectives' => [
            'Identificar remitentes y dominios que no coinciden con la entidad.',
            'Reconocer lenguaje de urgencia, amenazas y premios exagerados.',
            'Detectar adjuntos inesperados y códigos QR sospechosos.',
        ],
        'lesson' => [
            ['icon' => 'bxs-user', 'title' => 'Mira el remitente real', 'text' => 'El nombre visible se puede inventar. Revisa la dirección completa: lo que va después de la @ debe ser el dominio oficial (por ejemplo, @sunat.gob.pe y no @sunat-pagos.com).'],
            ['icon' => 'bxs-alarm', 'title' => 'Urgencia y amenazas', 'text' => '«Último aviso», «en 24 horas», «tu cuenta será eliminada». La prisa es una herramienta del atacante para que no pienses.'],
            ['icon' => 'bxs-gift', 'title' => 'Premios que piden algo', 'text' => 'Si ganaste algo en un sorteo en el que no participaste y te piden datos, una recarga o un pago… es un anzuelo.'],
            ['icon' => 'bx-paperclip', 'title' => 'Adjuntos inesperados', 'text' => 'Archivos .exe, .apk, .scr, .docm, .zip o «factura.pdf.exe» que no esperabas pueden instalar programas maliciosos. No los abras; pregunta primero.'],
            ['icon' => 'bx-qr', 'title' => 'Códigos QR', 'text' => 'Un QR también puede llevarte a una página falsa. Revisa que no esté pegado encima de otro y mira la dirección que aparece antes de abrirla.'],
            ['icon' => 'bxs-extension', 'title' => 'Combina señales', 'text' => 'Un mensaje real puede tener un error, y uno falso puede verse perfecto. Decide juntando varias pistas: quién, qué pide, cómo lo pide y a dónde te lleva.'],
        ],
        'xp' => 110, 'coins' => 30,
    ]);

    seed_game($pdo, $m, [
        'slug' => 'caza-de-senales', 'type' => 'spot', 'order' => 1, 'icon' => 'bxs-torch',
        'dimension' => 'reconocimiento', 'per_play' => 4, 'pass' => 60,
        'title' => 'Caza de señales',
        'description' => 'Toca todas las señales de alerta escondidas en cada mensaje.',
        'instructions' => 'Toca cada parte del mensaje que te parezca sospechosa (remitente, asunto, frases, botón, adjunto). Se indica cuántas señales hay. Marcar cosas normales resta puntos, así que revisa antes de confirmar.',
        'items' => [
            ['d' => 1,
             'c' => ['channel' => 'email', 'count' => 4,
                'header' => [
                    ['id' => 'from', 'label' => 'De', 'text' => 'SUNAT Devoluciones <devoluciones@sunat-reembolso.com>'],
                    ['id' => 'to', 'label' => 'Para', 'text' => 'estudiante@correo.com'],
                    ['id' => 'subject', 'label' => 'Asunto', 'text' => 'Tienes una devolución pendiente de S/ 480.00'],
                ],
                'body' => [
                    ['id' => 'b1', 'text' => 'Estimado contribuyente:', 'p' => true],
                    ['id' => 'b2', 'text' => 'Según nuestros registros, le corresponde una devolución de impuestos.', 'p' => true],
                    ['id' => 'b3', 'text' => 'Para recibirla, ingrese el número de su tarjeta y su clave en el siguiente formulario.'],
                    ['id' => 'b4', 'text' => 'Si no lo hace en 48 horas, perderá el beneficio.', 'p' => true],
                    ['id' => 'b5', 'text' => 'Gracias por su atención.', 'p' => true],
                ],
                'button' => ['id' => 'btn', 'text' => 'Cobrar devolución', 'url' => 'http://sunat-reembolso.com/cobrar'],
             ],
             's' => ['flags' => ['from', 'b3', 'b4', 'btn'], 'reasons' => [
                'from' => 'El dominio sunat-reembolso.com no es el oficial (sunat.gob.pe).',
                'b3' => 'Ninguna entidad pide el número de tarjeta y la clave por correo.',
                'b4' => 'Plazo límite para presionarte.',
                'btn' => 'El botón lleva a un sitio que no es de la SUNAT.',
             ]],
             'e' => 'Combina las señales: dominio falso + pedido de clave + urgencia + enlace externo. Las entidades del Estado usan dominios .gob.pe.'],

            ['d' => 1,
             'c' => ['channel' => 'sms', 'count' => 3,
                'header' => [['id' => 'from', 'label' => 'De', 'text' => '+51 987 004 512']],
                'body' => [
                    ['id' => 'b1', 'text' => '¡Felicidades! Tu número fue elegido ganador de un iPhone 15 en el sorteo aniversario de Claro.', 'p' => true],
                    ['id' => 'b2', 'text' => 'Para recibirlo, haz una recarga de S/ 20 y envía tu DNI y dirección.', 'p' => true],
                    ['id' => 'b3', 'text' => 'Reclama aquí: claro-premios.win', 'p' => true],
                ],
             ],
             's' => ['flags' => ['b1', 'b2', 'b3'], 'reasons' => [
                'b1' => 'Premio de un sorteo en el que no participaste.',
                'b2' => 'Un premio real no exige pagos ni recargas, ni te pide datos por SMS.',
                'b3' => 'Dominio .win que no pertenece a la empresa.',
             ]],
             'e' => 'Premio inesperado + pago previo + datos personales + enlace extraño = anzuelo clásico. El número remitente podría parecer normal: por eso importa revisar el contenido.'],

            ['d' => 2,
             'c' => ['channel' => 'email', 'count' => 4,
                'header' => [
                    ['id' => 'from', 'label' => 'De', 'text' => 'Soporte Microsoft <soporte@rnicrosoft-security.com>'],
                    ['id' => 'to', 'label' => 'Para', 'text' => 'usuario@amauta.edu.pe'],
                    ['id' => 'subject', 'label' => 'Asunto', 'text' => 'Su buzón está lleno'],
                ],
                'body' => [
                    ['id' => 'b1', 'text' => 'Estimado usuario:', 'p' => true],
                    ['id' => 'b2', 'text' => 'Su buzón superó el límite de almacenamiento.', 'p' => true],
                    ['id' => 'b3', 'text' => 'Dejará de recibir mensajes en las próximas 2 horas.'],
                    ['id' => 'b4', 'text' => 'Abra el archivo adjunto para ampliar su espacio gratis.', 'p' => true],
                    ['id' => 'b5', 'text' => 'Equipo de TI', 'p' => true],
                ],
                'attachment' => ['id' => 'att', 'name' => 'Ampliar_Buzon.html'],
             ],
             's' => ['flags' => ['from', 'b3', 'b4', 'att'], 'reasons' => [
                'from' => '«rnicrosoft» usa «r» + «n» para imitar la «m» de Microsoft.',
                'b3' => 'Urgencia: solo 2 horas.',
                'b4' => 'Te pide abrir un adjunto para «solucionar» el problema.',
                'att' => 'Un archivo .html adjunto suele abrir una página falsa para robar tu clave.',
             ]],
             'e' => 'Fíjate en las letras parecidas: rn ≈ m, 0 ≈ o, 1 ≈ l. Los atacantes registran dominios casi iguales a los originales.'],

            ['d' => 2,
             'c' => ['channel' => 'whatsapp', 'count' => 3,
                'header' => [['id' => 'from', 'label' => 'De', 'text' => 'Carla (compañera) · +51 955 210 874 (número nuevo)']],
                'body' => [
                    ['id' => 'b1', 'text' => 'Holaa, cambié de número guárdame', 'p' => true],
                    ['id' => 'b2', 'text' => 'Oye, sin querer puse tu número para recibir un código de 6 dígitos. ¿Me lo pasas porfa? Es urgente', 'p' => true],
                    ['id' => 'b3', 'text' => 'No le digas a nadie, me da vergüenza jaja', 'p' => true],
                ],
             ],
             's' => ['flags' => ['from', 'b2', 'b3'], 'reasons' => [
                'from' => 'Un «número nuevo» que dice ser alguien conocido: hay que confirmar por otro medio.',
                'b2' => 'Pedir un código de verificación es la forma típica de robar cuentas de WhatsApp.',
                'b3' => 'Pedirte secreto evita que consultes con alguien más.',
             ]],
             'e' => 'Ese código es la llave de TU cuenta. Si lo entregas, te la roban y escriben a tus contactos. Llama a tu compañera a su número antiguo o pregúntale en persona.'],

            ['d' => 3,
             'c' => ['channel' => 'poster', 'count' => 3,
                'header' => [['id' => 'from', 'label' => 'Afiche en el paradero', 'text' => 'Municipalidad Distrital – Pago de arbitrios con QR']],
                'body' => [
                    ['id' => 'b1', 'text' => '¡Paga tus arbitrios con 50 % de descuento solo hoy!', 'p' => true],
                    ['id' => 'b2', 'text' => '[Código QR pegado encima del original, con bordes despegados]', 'p' => true],
                    ['id' => 'b3', 'text' => 'Al escanear, abre: muni-pagos-descuento.site', 'p' => true],
                    ['id' => 'b4', 'text' => 'Horario de atención: lunes a viernes de 8:00 a 16:00.', 'p' => true],
                ],
             ],
             's' => ['flags' => ['b1', 'b2', 'b3'], 'reasons' => [
                'b1' => 'Descuento exagerado y «solo hoy».',
                'b2' => 'Un QR pegado encima de otro es señal de manipulación.',
                'b3' => 'La dirección no pertenece a la municipalidad (usaría .gob.pe).',
             ]],
             'e' => 'Los QR falsos (quishing) se pegan sobre afiches reales. Antes de pagar, revisa la dirección que muestra la cámara y paga desde canales oficiales.'],

            ['d' => 3,
             'c' => ['channel' => 'email', 'count' => 3,
                'header' => [
                    ['id' => 'from', 'label' => 'De', 'text' => 'PRONABEC Beca 18 <convocatoria@pronabec.gob.pe>'],
                    ['id' => 'to', 'label' => 'Para', 'text' => 'postulante@correo.com'],
                    ['id' => 'subject', 'label' => 'Asunto', 'text' => 'Re: Resultados de preselección'],
                ],
                'body' => [
                    ['id' => 'b1', 'text' => 'Hola, postulante:', 'p' => true],
                    ['id' => 'b2', 'text' => 'Fuiste preseleccionado. Para asegurar tu vacante debes abonar S/ 150 por «derecho de trámite».', 'p' => true],
                    ['id' => 'b3', 'text' => 'Realiza el pago por Yape al número 9XX XXX 321 a nombre de Luis R.'],
                    ['id' => 'b4', 'text' => 'Responde a este correo con tu voucher antes de mañana.', 'p' => true],
                ],
             ],
             's' => ['flags' => ['b2', 'b3', 'b4'], 'reasons' => [
                'b2' => 'Las becas del Estado no cobran por asegurar vacantes.',
                'b3' => 'Pagar a una persona particular por Yape no es un canal oficial.',
                'b4' => 'Plazo corto para que no tengas tiempo de consultar.',
             ]],
             'e' => '¡Ojo! El remitente parece oficial: puede ser suplantado o la cuenta pudo ser robada. Por eso no basta con mirar el remitente: lo que PIDE el mensaje también es una señal. Verifica en pronabec.gob.pe o llamando a sus canales oficiales.'],
        ],
    ]);

    seed_game($pdo, $m, [
        'slug' => 'prueba-2', 'type' => 'quiz', 'order' => 2, 'icon' => 'bxs-edit',
        'dimension' => 'reconocimiento', 'per_play' => 5, 'pass' => 60,
        'title' => 'Mini-prueba: señales de alerta',
        'description' => '¿Tu radar ya detecta las señales? Compruébalo.',
        'instructions' => 'Elige la respuesta correcta. Después de cada pregunta verás la explicación.',
        'items' => [
            ['d' => 1, 'dim' => 'reconocimiento', 'c' => ['question' => '¿Cuál de estas direcciones de correo es más probable que sea FALSA?', 'options' => ['notificaciones@perueduca.pe', 'avisos@sunat.gob.pe', 'soporte@bcp-seguridad-clientes.com', 'no-reply@accounts.google.com']],
             's' => ['answer' => 2], 'e' => 'bcp-seguridad-clientes.com no es el dominio oficial del banco. Los atacantes añaden palabras como «seguridad», «soporte» o «pagos» para parecer reales.'],
            ['d' => 1, 'dim' => 'reconocimiento', 'c' => ['question' => '«Tu cuenta será eliminada en 1 hora si no confirmas tus datos». ¿Qué señal es esta?', 'options' => ['Amabilidad', 'Urgencia y amenaza', 'Mensaje informativo normal', 'Promoción']],
             's' => ['answer' => 1], 'e' => 'La presión de tiempo es una de las señales más comunes del phishing.'],
            ['d' => 2, 'dim' => 'reconocimiento', 'c' => ['question' => '¿Qué archivo adjunto inesperado es MÁS peligroso?', 'options' => ['foto_paseo.jpg', 'tarea_final.pdf.exe', 'horario.png', 'notas.txt']],
             's' => ['answer' => 1], 'e' => 'Una doble extensión como .pdf.exe esconde un programa ejecutable detrás de un nombre inofensivo.'],
            ['d' => 2, 'dim' => 'reconocimiento', 'c' => ['question' => 'Ves un afiche con un código QR pegado encima de otro. ¿Qué haces?', 'options' => ['Lo escaneo rápido para aprovechar la oferta', 'Desconfío: puede estar manipulado. Busco el canal oficial', 'Lo comparto con mis amigos', 'Pago con el QR porque está en la calle']],
             's' => ['answer' => 1], 'e' => 'Un QR superpuesto puede llevarte a una página falsa (quishing).'],
            ['d' => 2, 'dim' => 'reconocimiento', 'c' => ['question' => '«rnercadolibre.com» intenta imitar a…', 'options' => ['mercadolibre.com usando «rn» en lugar de «m»', 'Un sitio de mercados de Lima', 'Un dominio oficial nuevo', 'Nada, es correcto']],
             's' => ['answer' => 0], 'e' => 'Las letras «r» + «n» juntas parecen una «m». Lee las direcciones despacio.'],
            ['d' => 3, 'dim' => 'reconocimiento', 'c' => ['question' => 'Un correo viene de la dirección oficial de una institución, pero te pide pagar a una cuenta personal para no perder un beneficio. ¿Qué concluyes?', 'options' => ['Es seguro porque el remitente es oficial', 'Puede ser un ataque: la cuenta pudo ser suplantada o robada; lo que pide también es una señal', 'Debo pagar rápido', 'Debo responder con mis datos']],
             's' => ['answer' => 1], 'e' => 'El remitente es solo una pista. Una solicitud inusual (pagos a personas, claves, códigos) siempre debe verificarse por otro canal.'],
            ['d' => 3, 'dim' => 'reconocimiento', 'c' => ['question' => '¿Cuál es la mejor forma de decidir si un mensaje es phishing?', 'options' => ['Ver si tiene faltas de ortografía', 'Combinar varias señales: remitente, lo que pide, urgencia, enlace y contexto', 'Ver si tiene logo', 'Preguntar al mismo remitente si es real']],
             's' => ['answer' => 1], 'e' => 'Ninguna señal aislada es infalible. Combinar evidencias y verificar por otro canal es lo más seguro.'],
            ['d' => 1, 'dim' => 'reconocimiento', 'c' => ['question' => 'Un saludo genérico como «Estimado cliente» o «Querido usuario»…', 'options' => ['Siempre es seguro', 'Puede ser una señal, porque el atacante no conoce tu nombre', 'Significa que el mensaje es oficial', 'No importa nunca']],
             's' => ['answer' => 1], 'e' => 'Los envíos masivos de phishing suelen usar saludos genéricos. Aunque, en ataques dirigidos, pueden usar tu nombre real.'],
        ],
    ]);
};
