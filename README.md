# PhishHunter · Aprende, juega, protégete

**Versión 1.0.0 (agosto de 2026)** · Licencia [MIT](LICENSE)

Plataforma web gamificada en **PHP + MySQL** para enseñar la prevención del phishing a estudiantes de secundaria. Es la versión utilizada en la intervención de la tesis *«Gamificación y prevención del phishing en estudiantes de secundaria de una institución rural de Frías, Piura, 2026 (ODS 4)»*, de Alexis José Dominguez Aguila y Randdy Samhir Ojeda Marchan (Universidad Tecnológica del Perú).

> **Datos personales.** Este repositorio contiene solo el código y el contenido educativo (misiones, casos simulados y cuestionarios). No incluye la base de datos de la intervención ni datos de estudiantes. Al instalarlo se crea una base vacía. La base anonimizada de los resultados de la tesis está en la carpeta [`datos/`](datos/).

## Base de datos

No es necesario importar ningún archivo `.sql`: `install.php` (o `php database/seed.php`) crea la base de datos `db_phishhunter`, sus tablas a partir de `database/schema.sql` y carga el contenido desde `database/seeders/` (niveles, insignias, ítems del cuestionario y las seis misiones con sus casos).

## Configuración

Copia `config/config.example.php` como `config/config.php` y ajusta los datos de conexión de tu servidor MySQL.


## Instalación en WAMP

1. Copia (o clona) esta carpeta en `C:\wamp64\www\game`.
2. Enciende WAMP (ícono verde). Requiere PHP 7.4 o superior y MySQL 5.7+ / MariaDB 10.3+.
3. Abre **http://localhost/game/install.php** y pulsa **Instalar ahora**.
   Se crea la base de datos `db_phishhunter` (usuario `root`, sin contraseña), sus tablas y todos los seeders.
   - Alternativa por consola: `cd C:\wamp64\www\game` y luego `C:\wamp64\bin\php\php8.x.x\php.exe database\seed.php`
4. Entra a **http://localhost/game/**

Los datos de conexión están en `config/config.php`.

### Cuentas

El registro libre está desactivado. Solo existe la cuenta del docente; los estudiantes se registran desde **Panel docente → Estudiantes** (uno a uno o pegando una lista completa), donde también se editan, se les restablece la contraseña o se eliminan. Cada estudiante puede cambiar su contraseña en «Mi progreso».

| Rol | Usuario | Contraseña |
|---|---|---|
| Docente (panel de resultados) | `admin` | `admin123` |

## Cómo se alinea con la tesis

| Tesis | En el juego |
|---|---|
| Pretest / postest (24 ítems, escala Nunca–Siempre) | `evaluation.php?type=pretest` antes de la misión 1; `postest` al completar las 6 misiones |
| Dimensiones: reconocimiento (1–8), decisiones (9–17), protección (18–24) | Mismos ítems, puntajes por dimensión y niveles Bajo/Medio/Alto con los intervalos de la Tabla 5.1 |
| Valoración de la gamificación (9 ítems, 3 dimensiones) | Parte 2 del postest |
| 6 sesiones de ~45 min | 6 misiones que se desbloquean en orden |
| Misiones, retos, puntos, niveles, insignias, retroalimentación | XP, 8 niveles, monedas, estrellas, 20 insignias, 22 logros, rachas, tabla de clasificación y tienda de avatares |
| «No premiar solo la rapidez» | Sin temporizadores; los puntos dependen de acertar el análisis |
| Retroalimentación centrada en el proceso | Cada caso explica las señales, el dominio real o la consecuencia de la decisión |
| Pequeñas pruebas de aprendizaje | Una mini-prueba objetiva al final de cada misión (se desbloquea al aprobar la práctica) |

### Misiones y minijuegos

1. **¿Qué es el phishing?** — *¿Phishing o legítimo?* + mini-prueba
2. **Señales de alerta** — *Caza de señales* (tocar remitente, urgencia, adjuntos, QR…) + mini-prueba
3. **Detective de enlaces** — *Inspector de enlaces* (dominio real, subdominios trampa, acortadores) + mini-prueba
4. **Decide con cabeza fría** — *¿Qué harías?* (escenarios con consecuencias) + mini-prueba
5. **Protege tus cuentas** — *Forja de claves* (con medidor de fuerza), *Seguro o riesgoso* (arrastrar) + mini-prueba
6. **Operación Anzuelo Roto** — *Chat trampa*, *Casos difíciles* + prueba final

Las respuestas se corrigen en el servidor (`includes/game.php`): el navegador nunca recibe las soluciones antes de responder.

## Panel docente (`/admin`)

- Comparación pretest–postest de pares relacionados: medias, diferencia, prueba t, p y d<sub>z</sub> de Cohen, global y por dimensión.
- Distribución de niveles por medición, promedio por ítem (como las tablas 5.6 y 5.7), valoración de la gamificación.
- Avance por misión y desempeño en minijuegos y mini-pruebas; filtro por grado.
- Exportación CSV (separador `;`, compatible con Excel/SPSS/JASP): puntajes, respuestas ítem por ítem y resultados de juegos.
- Lista de estudiantes con acceso a su perfil y restablecimiento de contraseña.

## Estructura

```
admin/            Panel docente, estudiantes, exportación CSV
api/game.php      API JSON de partidas (iniciar, responder, terminar)
assets/           CSS, JS (app.js, games.js) e imágenes
config/           Configuración de la base de datos
database/         schema.sql, seed.php y seeders/ (contenido del juego y cuenta docente)
includes/         Arranque, motor de gamificación, evaluación, estadística y plantilla
*.php             Páginas: inicio, registro, misiones, juego, evaluación, perfil, clasificación, recompensas,
                  zona de refuerzo (arcade.php, arcade_play.php), videoteca (videos.php) y upgrade.php
```

Para añadir casos nuevos, edita los archivos de `database/seeders/` y vuelve a ejecutar la instalación (reinicia los datos).

## Zona de refuerzo, videoteca y Chispa (actualización)

**Si ya tenías el juego instalado**, no reinstales (borraría a los estudiantes). Entra como docente y abre
**http://localhost/game/upgrade.php** → «Actualizar ahora» (o por consola: `php database/upgrade.php`).
Solo crea tablas nuevas y carga el contenido; conserva usuarios, progreso y evaluaciones.

| Novedad | Detalle |
|---|---|
| 🤖 **Chispa** | Guía original (SVG en `assets/img/mascot/`) que acompaña en el panel, las misiones, la retroalimentación y los juegos nuevos. |
| 🎬 **Videoteca** | 14 videos de YouTube (INCIBE, OSI, PantallasAmigas…) por misión, con pregunta de repaso. +10 XP al verlos, +15 XP si aciertas a la primera. Insignia «Cinéfilo seguro». Requiere internet. |
| 🃏 **Memorama del cazador** | Términos ↔ definiciones (se desbloquea con la misión 1). |
| 🔗 **Relaciona y protege** | Unir con líneas: señales, canales, acciones seguras, dominios (misión 2). |
| 🔑 **Memorama de claves** | Contraseña ↔ diagnóstico (misión 4). |
| 🧩 **Arma tu clave** | Puzle de cartas: armar una contraseña fuerte evitando cartas trampa con datos personales (misión 5). |
| 🏢 **Simulador de oficina** | Completar documentos mientras llegan correos, chats, SMS y llamadas del jefe, compañeros y amigos; algunos son engaños (fraude del CEO, cambio de cuenta bancaria, falso soporte, páginas de login falsas). Con 85 % o más y sin caer en ningún engaño se gana el **gran premio**: +300 XP, +150 monedas, avatar, marco y título exclusivos e insignia «Empleado del año» (misión 6). |

- La Zona de refuerzo es **complementaria**: no cambia las 6 misiones ni el acceso al postest. El panel docente muestra sus resultados aparte.
- Todo se corrige en el servidor (`includes/arcade.php`, `api/arcade.php`).
- Para cambiar videos, cartas, parejas o mensajes de la oficina, edita `database/arcade_content.php` y vuelve a ejecutar `upgrade.php`.

## Varios docentes, grupos y súper admin (actualización)

Para actualizar una instalación existente **sin borrar datos**: entra como `admin` y abre `upgrade.php` → «Actualizar contenido» (o `php database/upgrade.php`). La cuenta `admin` pasa a ser **súper admin**; no tiene colegio ni grupos propios (si le quedaban estudiantes de una versión anterior, muévelos a un docente con «Editar» en Estudiantes).

| Rol | Qué puede hacer |
|---|---|
| **Súper admin** (`admin`) | Crear, editar, habilitar/deshabilitar y restablecer contraseñas de docentes (menú **Docentes**). Recibir en la **Bandeja** las solicitudes de «Soy docente nuevo» y los mensajes de los docentes; crear la cuenta con un clic y enviar las credenciales temporales por correo o WhatsApp. Ver los resultados de todos (filtrando por docente). **Registrar estudiantes a nombre de un docente** (Estudiantes → «Destino: docente › grupo»), mover estudiantes entre docentes y administrar los grupos, grados e institución de cada docente (menú **Grupos**). No tiene colegio ni estudiantes propios. |
| **Docente** | Elegir sus grados (1.º a 5.º), crear grupos **experimentales** o **de control**, registrar/editar/eliminar a sus estudiantes y ver solo sus resultados y exportaciones. Escribir al súper admin en **Mensajes**. |
| **Estudiante** | Grupo experimental: juega las misiones y el postest se abre al completarlas. Grupo control: solo responde el pretest y el postest (que el docente habilita en **Grupos**); al terminar el postest se le abre el juego. |

- En el login, «Soy docente nuevo» envía una solicitud (nombre, correo, celular, institución y grados) a la Bandeja del súper admin.
- Los docentes creados con contraseña temporal deben cambiarla al ingresar por primera vez.
- Un docente deshabilitado no puede ingresar; sus estudiantes siguen jugando.
- El panel de resultados filtra por grado, grupo y tipo de grupo, e incluye la comparación **experimental vs. control** (ganancia post − pre, t de Welch y d de Cohen). Las exportaciones CSV incluyen docente, grupo y tipo de grupo.


## URLs amigables y hosting

Las direcciones ya no muestran «.php»: `/refuerzo`, `/clasificacion`, `/mision/que-es-phishing`, `/panel/estudiantes`…
La tabla completa está en `includes/routes.php` (es el único lugar que hay que tocar para renombrar una ruta).

| Página | URL | Página | URL |
|---|---|---|---|
| Inicio | `/` | Panel · resultados | `/panel` |
| Ingresar / salir | `/ingresar`, `/salir` | Panel · estudiantes | `/panel/estudiantes` |
| Soy docente nuevo | `/soy-docente` | Panel · grupos | `/panel/grupos` |
| Misiones | `/misiones`, `/mision/{misión}` | Panel · mensajes | `/panel/mensajes` |
| Minijuego | `/jugar/{juego}` | Panel · exportar CSV | `/panel/exportar` |
| Pretest / postest | `/evaluacion/pretest`, `/evaluacion/postest` | Súper admin · docentes | `/panel/docentes` |
| Zona de refuerzo | `/refuerzo`, `/refuerzo/{juego}` | Súper admin · bandeja | `/panel/bandeja` |
| Videoteca | `/videos` | Instalar / actualizar | `/instalar`, `/actualizar` |
| Clasificación | `/clasificacion` | API (JSON) | `/api/juego`, `/api/refuerzo` |
| Recompensas | `/recompensas` | | |
| Mi progreso | `/progreso` (docente: `/progreso/{id}`) | | |

- **Apache (WAMP y casi todos los hostings compartidos, cPanel):** funciona con el `.htaccess` de la raíz. Solo requiere `mod_rewrite` (en WAMP: icono > Apache > Módulos > `rewrite_module`).
- **Nginx:** `location / { try_files $uri $uri/ /router.php?$query_string; }`
- **Prueba rápida con PHP:** `php -S localhost:8000 router.php`
- Si el servidor no permite reescribir URLs, la app sigue funcionando con las direcciones «.php» (se detecta sola). Para forzarlo, en `config/config.php`: `define('PRETTY_URLS', true);` o `false`.
- Los enlaces antiguos con «.php» (marcadores, capturas de la tesis) redirigen solos a la dirección nueva.
- Las páginas que no existen muestran un aviso 404 con Chispa.


## Seguridad

La instalación crea la cuenta de súper administrador `admin` / `admin123`. Cámbiala inmediatamente desde «Mi progreso» si publicas la plataforma en un servidor.

## Licencia

Distribuido bajo la licencia MIT. Consulta el archivo [LICENSE](LICENSE).
