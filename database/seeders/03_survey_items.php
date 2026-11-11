<?php
/**
 * Seeder: instrumentos de la tesis.
 *  - Prevención del phishing: 24 ítems de frecuencia (Nunca=1 … Siempre=5)
 *      Reconocimiento de señales (1–8), Toma de decisiones seguras (9–17),
 *      Prácticas de protección digital (18–24).
 *  - Valoración de la gamificación: 9 ítems de acuerdo (1–5), 3 por dimensión.
 */
return function (PDO $pdo): void {
    $prevention = [
        // Reconocimiento de señales
        [1,  'reconocimiento', 'Remitente que solicita contraseña', 'Cuando un mensaje me pide mi contraseña, reviso con atención quién es el remitente real.'],
        [2,  'reconocimiento', 'Dirección web extraña', 'Me doy cuenta cuando la dirección web de un enlace tiene letras cambiadas, números o palabras extrañas.'],
        [3,  'reconocimiento', 'Presión para actuar de inmediato', 'Reconozco como sospechosos los mensajes que me presionan a actuar de inmediato (por ejemplo: «tu cuenta se cerrará hoy»).'],
        [4,  'reconocimiento', 'Enlace e institución coincidentes', 'Compruebo que el enlace de un mensaje corresponda realmente a la institución que dice enviarlo.'],
        [5,  'reconocimiento', 'Premios que solicitan datos', 'Desconfío de los mensajes que anuncian premios o sorteos y me piden datos personales.'],
        [6,  'reconocimiento', 'Archivos adjuntos inesperados', 'Considero sospechosos los archivos adjuntos que no esperaba recibir, aunque parezcan venir de alguien conocido.'],
        [7,  'reconocimiento', 'Código QR superpuesto', 'Antes de escanear un código QR de un afiche o cartel, reviso que no esté pegado encima del original.'],
        [8,  'reconocimiento', 'Mensaje convincente pero falso', 'Tengo presente que un mensaje bien redactado y con logotipos oficiales también puede ser falso.'],
        // Toma de decisiones seguras
        [9,  'decisiones', 'Acceso por aplicación oficial', 'Ingreso a mis cuentas desde la aplicación o la página oficial, en lugar de usar el enlace que me llega en un mensaje.'],
        [10, 'decisiones', 'Confirmación por otro medio', 'Cuando un conocido me pide dinero, datos o códigos por mensaje, lo confirmo llamándolo o por otro medio.'],
        [11, 'decisiones', 'URL oficial antes de iniciar sesión', 'Antes de iniciar sesión, verifico que la dirección de la página sea la oficial.'],
        [12, 'decisiones', 'Premio que exige un pago', 'Rechazo los premios o beneficios que me piden hacer un pago o una recarga para recibirlos.'],
        [13, 'decisiones', 'Verificación de identidad', 'Verifico la identidad de quien me escribe antes de responder una solicitud inusual.'],
        [14, 'decisiones', 'Reporte al responsable escolar', 'Informo a un docente o responsable de la institución cuando recibo un mensaje sospechoso.'],
        [15, 'decisiones', 'Comunicación de descarga sospechosa', 'Si descargué o abrí algo sospechoso, lo comunico de inmediato a un adulto o responsable.'],
        [16, 'decisiones', 'Evitar responder al phishing', 'Evito responder o reenviar los mensajes que considero phishing.'],
        [17, 'decisiones', 'Contraseñas distintas y seguras', 'Uso contraseñas distintas y seguras para cada una de mis cuentas.'],
        // Prácticas de protección digital
        [18, 'proteccion', 'Verificación en dos pasos', 'Activo la verificación en dos pasos en las cuentas que lo permiten.'],
        [19, 'proteccion', 'Privacidad de códigos', 'Mantengo en privado los códigos de verificación que recibo por SMS o correo, sin compartirlos con nadie.'],
        [20, 'proteccion', 'Dirección completa antes de contraseña', 'Reviso la dirección completa de la página antes de escribir mi contraseña.'],
        [21, 'proteccion', 'Actualización de equipo y navegador', 'Mantengo actualizados el sistema de mi celular o computadora y el navegador.'],
        [22, 'proteccion', 'No entregar contraseña a administrador', 'No entrego mi contraseña aunque quien me la pida diga ser administrador, soporte técnico o docente.'],
        [23, 'proteccion', 'Cierre de sesión en equipo compartido', 'Cierro sesión cuando uso una computadora compartida o de la sala de cómputo.'],
        [24, 'proteccion', 'Reporte y recomendaciones', 'Reporto los mensajes fraudulentos en la plataforma y comparto recomendaciones de seguridad con otras personas.'],
    ];

    $gamification = [
        [1, 'motivacion',  'Misiones motivadoras', 'Las misiones y retos de PhishHunter me motivaron a seguir participando.'],
        [2, 'motivacion',  'Puntos e insignias', 'Los puntos, niveles e insignias hicieron que quisiera mejorar mis resultados.'],
        [3, 'motivacion',  'Involucramiento', 'Me sentí involucrado(a) y atento(a) durante las actividades.'],
        [4, 'diseno',      'Instrucciones claras', 'Las instrucciones de los juegos fueron claras y fáciles de entender.'],
        [5, 'diseno',      'Facilidad de uso', 'La plataforma fue fácil de usar y de navegar.'],
        [6, 'diseno',      'Retroalimentación útil', 'El diseño y los mensajes de retroalimentación me ayudaron a comprender mis errores.'],
        [7, 'aprendizaje', 'Reconocer phishing', 'Los juegos me ayudaron a reconocer mejor los mensajes de phishing.'],
        [8, 'aprendizaje', 'Utilidad diaria', 'Lo aprendido me será útil para proteger mis cuentas en la vida diaria.'],
        [9, 'aprendizaje', 'Recomendación', 'Recomendaría esta forma de aprender a otros estudiantes.'],
    ];

    $st = $pdo->prepare('INSERT INTO survey_items (instrument, number, dimension, short_label, statement) VALUES (?,?,?,?,?)');
    foreach ($prevention as $p)   $st->execute(['prevention', $p[0], $p[1], $p[2], $p[3]]);
    foreach ($gamification as $g) $st->execute(['gamification', $g[0], $g[1], $g[2], $g[3]]);
};
