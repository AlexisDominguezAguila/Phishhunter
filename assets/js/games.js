/* PhishHunter · motor de minijuegos
 * Las respuestas se envían al servidor, que corrige y devuelve la
 * retroalimentación. El navegador nunca conoce las soluciones de antemano.
 */
(function () {
    'use strict';

    var G = window.PH_GAME;
    var esc = PHUI.esc;
    var $ = function (id) { return document.getElementById(id); };

    var state = { attempt: null, items: [], idx: 0, score: 0, answered: 0, busy: false };

    var CHANNELS = {
        email:    { icon: '<i class="bx bxs-envelope"></i>', label: 'Correo electrónico' },
        sms:      { icon: '<i class="bx bxs-message-rounded-dots"></i>', label: 'Mensaje de texto (SMS)' },
        whatsapp: { icon: '<i class="bx bxl-whatsapp"></i>', label: 'WhatsApp' },
        social:   { icon: '<i class="bx bxs-camera"></i>', label: 'Mensaje en redes sociales' },
        poster:   { icon: '<i class="bx bxs-megaphone"></i>', label: 'Afiche en la calle' }
    };

    /* ------------------------------------------------------------------
     *  Flujo general
 * ---------------------------------------------------------------- */

    function start() {
        if (state.busy) return;
        state.busy = true;
        $('start-btn').disabled = true;
        PHUI.api('api/game.php', { action: 'start', game_id: G.id }).then(function (r) {
            state.busy = false;
            if (!r.ok) { PHUI.toast(r.error || 'No se pudo iniciar', 'error'); $('start-btn').disabled = false; return; }
            state.attempt = r.attempt_id;
            state.items = r.items;
            state.idx = 0; state.score = 0; state.answered = 0;
            $('intro').hidden = true;
            $('result').hidden = true;
            $('stage').hidden = false;
            showItem();
        });
    }

    function progress() {
        var total = state.items.length;
        $('pp-bar').style.width = Math.round(state.answered / total * 100) + '%';
        $('pp-text').textContent = state.answered + '/' + total;
        $('pp-score').textContent = Math.round(state.score);
    }

    function showItem() {
        progress();
        var item = state.items[state.idx];
        var stage = $('stage');
        $('feedback').hidden = true;
        stage.className = 'stage stage-' + G.type + ' enter';
        stage.innerHTML = '<div class="case-num">Caso ' + (state.idx + 1) + ' de ' + state.items.length +
            difficultyTag(item.difficulty) + '</div>' + RENDER[G.type](item.content, item);
        BIND[G.type] && BIND[G.type](stage, item);
        setTimeout(function () { stage.classList.remove('enter'); }, 400);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function difficultyTag(d) {
        var t = { 1: ['Básico', 'd1'], 2: ['Intermedio', 'd2'], 3: ['Avanzado', 'd3'] }[d] || ['Básico', 'd1'];
        return ' <span class="diff ' + t[1] + '">' + t[0] + '</span>';
    }

    function submit(answer, after) {
        if (state.busy) return;
        state.busy = true;
        var item = state.items[state.idx];
        $('stage').classList.add('locked');
        PHUI.api('api/game.php', { action: 'answer', attempt_id: state.attempt, item_id: item.id, answer: answer }).then(function (r) {
            state.busy = false;
            if (!r.ok) { PHUI.toast(r.error || 'Error al enviar', 'error'); $('stage').classList.remove('locked'); return; }
            state.answered++;
            state.score += r.points * 10;
            progress();
            if (after) after(r);
            showFeedback(r, answer);
        });
    }

    function showFeedback(r, answer) {
        var fb = r.feedback;
        var ok = r.correct;
        var partial = !ok && r.points > 0;
        var box = $('feedback');
        var title = ok ? pick(['¡Excelente!', '¡Bien visto!', '¡Correcto!', '¡Así se hace!']) :
                    partial ? '¡Casi!' : pick(['¡Uy! Esta vez no', 'No era la opción segura', 'Casi… revisemos']);
        var extra = EXTRA[G.type] ? EXTRA[G.type](fb, answer) : '';
        box.className = 'feedback ' + (ok ? 'fb-ok' : partial ? 'fb-partial' : 'fb-bad');
        box.innerHTML =
            '<div class="fb-head"><img class="fb-mascot" src="' + esc(window.PH.base + '/assets/img/mascot/chispa-' + (ok ? 'feliz' : partial ? 'piensa' : 'alerta') + '.svg') + '" alt="Chispa"><span class="fb-emoji">' + (ok ? '<i class="bx bxs-party"></i>' : partial ? '<i class="bx bxs-hand"></i>' : '<i class="bx bx-search-alt"></i>') + '</span>' +
            '<div><h3>' + title + '</h3><span class="fb-points">+' + Math.round(r.points * 10) + ' pts</span></div></div>' +
            (fb.consequence ? '<p class="fb-consequence">' + esc(fb.consequence) + '</p>' : '') +
            extra +
            '<p class="fb-explain"><i class="bx bxs-bulb"></i> ' + esc(fb.explanation) + '</p>' +
            '<button class="btn btn-primary btn-lg" id="next-btn" type="button">' +
            (state.idx < state.items.length - 1 ? 'Siguiente caso <i class="bx bx-right-arrow-alt"></i>' : 'Ver resultados <i class="bx bxs-flag-checkered"></i>') + '</button>';
        box.hidden = false;
        setTimeout(function () { box.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }, 60);
        $('next-btn').onclick = next;
        $('next-btn').focus({ preventScroll: true });
    }

    function next() {
        if (state.idx < state.items.length - 1) { state.idx++; showItem(); } else { finish(); }
    }

    function finish() {
        state.busy = true;
        $('next-btn') && ($('next-btn').disabled = true);
        PHUI.api('api/game.php', { action: 'finish', attempt_id: state.attempt }).then(function (r) {
            state.busy = false;
            if (!r.ok) { PHUI.toast(r.error || 'Error al terminar', 'error'); return; }
            $('stage').hidden = true;
            $('feedback').hidden = true;
            showResult(r);
        });
    }

    function showResult(r) {
        var box = $('result');
        var msg = r.percent === 100 ? '¡Partida perfecta! Nadie te pesca. <i class="bx bx-target-lock"></i>' :
                  r.passed ? '¡Aprobaste! Cada caso te hace más difícil de engañar.' :
                  'Aún no apruebas, pero cada error es una pista aprendida. ¡Inténtalo otra vez!';
        var stars = '';
        for (var i = 1; i <= 3; i++) stars += '<i class="bx bxs-star' + (i <= r.stars ? ' on' : '') + '" style="animation-delay:' + (i * 0.25) + 's"></i>';

        var extras = '';
        if (r.mission) extras += '<div class="res-chip res-mission"><i class="bx bxs-map-alt"></i> ¡Misión completada! <b>' + esc(r.mission.title) + '</b></div>';
        if (r.level_up) extras += '<div class="res-chip res-level">' + PHUI.icon(r.level_up.icon, 'bx-tada') + ' ¡Subiste al nivel ' + r.level_up.level + ': ' + esc(r.level_up.name) + '!</div>';

        box.innerHTML =
            '<div class="res-stars">' + stars + '</div>' +
            '<h1>' + (r.passed ? '¡Misión cumplida!' : 'Sigue entrenando') + '</h1>' +
            '<p class="lead">' + msg + '</p>' +
            '<div class="res-grid">' +
                '<div><b>' + r.percent + '%</b><span>puntaje</span></div>' +
                '<div><b>' + r.correct + '/' + r.total + '</b><span>aciertos</span></div>' +
                '<div><b>+' + r.xp + '</b><span>XP</span></div>' +
                '<div><b>+' + r.coins + '</b><span>monedas <i class="bx bxs-coin"></i></span></div>' +
            '</div>' +
            (r.improved ? '' : '<p class="muted small">No superaste tu mejor puntaje, así que recibes XP de práctica (40 %).</p>') +
            extras +
            '<div class="res-actions">' +
                '<button class="btn btn-soft btn-lg" type="button" id="again-btn"><i class="bx bx-repeat"></i> Jugar otra vez</button>' +
                '<a class="btn btn-primary btn-lg" href="' + esc(G.missionUrl) + '">Volver a la misión <i class="bx bx-right-arrow-alt"></i></a>' +
            '</div>';
        box.hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        $('again-btn').onclick = function () { $('result').hidden = true; $('intro').hidden = false; $('start-btn').disabled = false; };

        // Actualiza los contadores del encabezado
        if (r.user) {
            var c = document.querySelector('.chip-coins b'); if (c) c.textContent = r.user.coins;
            var s = document.querySelector('.chip-streak b'); if (s) s.textContent = r.user.streak;
        }

        var cel = [];
        if (r.level_up) cel.push({ kicker: '¡Subiste de nivel!', icon: r.level_up.icon, name: 'Nivel ' + r.level_up.level + ': ' + r.level_up.name, description: 'Tu experiencia como cazador sigue creciendo.', color: '#FFC83D' });
        (r.badges || []).forEach(function (b) { cel.push({ kicker: '¡Nueva insignia!', icon: b.icon, name: b.name, description: b.description, color: b.color, reward: '+25 XP · +10 monedas' }); });
        (r.achievements || []).forEach(function (a) { cel.push({ kicker: '¡Logro desbloqueado!', icon: a.icon, name: a.name, description: a.description, color: '#2BB39A', reward: (a.xp ? '+' + a.xp + ' XP · ' : '') + '+' + a.coins + ' monedas' }); });
        if (cel.length) setTimeout(function () { PHUI.celebrate(cel); }, 900);
        else if (r.passed) PHUI.confetti();
    }

    function pick(a) { return a[Math.floor(Math.random() * a.length)]; }

    function linkify(text) {
        return esc(text).replace(/\n/g, '<br>');
    }

    /* ------------------------------------------------------------------
     *  Maquetas de mensajes
 * ---------------------------------------------------------------- */

    function messageMock(c) {
        var ch = CHANNELS[c.channel] || CHANNELS.email;
        var h = '<div class="msg msg-' + esc(c.channel) + '">';
        h += '<div class="msg-channel">' + ch.icon + ' ' + ch.label + '</div>';
        if (c.channel === 'email') {
            h += '<div class="mail-head">' +
                '<div class="mail-avatar">' + esc((c.from_name || '?').charAt(0)) + '</div>' +
                '<div><b>' + esc(c.from_name) + '</b> <span class="mail-addr">&lt;' + esc(c.from) + '&gt;</span>' +
                '<div class="mail-subject">' + esc(c.subject || '') + '</div></div>' +
                '<span class="mail-time">' + esc(c.time || '') + '</span></div>';
            h += '<div class="mail-body">' + linkify(c.body) + '</div>';
        } else {
            h += '<div class="chat-head"><div class="mail-avatar">' + esc((c.from_name || '?').charAt(0)) + '</div>' +
                '<div><b>' + esc(c.from_name) + '</b><small>' + esc(c.from) + '</small></div></div>';
            h += '<div class="bubble">' + linkify(c.body) +
                (c.link ? '<div class="bubble-link">' + esc(c.link.text) + '</div>' : '') +
                '<span class="bubble-time">' + esc(c.time || '') + '</span></div>';
        }
        if (c.channel === 'email' && c.link) {
            h += '<div class="mail-link"><span class="fake-btn">' + esc(c.link.text) + '</span>' +
                '<span class="hover-url"><i class="bx bx-link"></i> Al pasar el mouse: <code>' + esc(c.link.url) + '</code></span></div>';
        } else if (c.link) {
            h += '<div class="mail-link"><span class="hover-url"><i class="bx bx-link"></i> El enlace lleva a: <code>' + esc(c.link.url) + '</code></span></div>';
        }
        if (c.attachment) h += '<div class="attach"><i class="bx bx-paperclip"></i> ' + esc(c.attachment) + '</div>';
        return h + '</div>';
    }

    function optionsList(options, cls) {
        var letters = 'ABCDEF';
        return '<div class="options ' + (cls || '') + '">' + options.map(function (o, i) {
            return '<button type="button" class="option" data-i="' + i + '"><span class="opt-letter">' + letters[i] + '</span><span>' + esc(o) + '</span></button>';
        }).join('') + '</div>';
    }

    function bindOptions(stage) {
        stage.querySelectorAll('.option').forEach(function (b) {
            b.addEventListener('click', function () {
                stage.querySelectorAll('.option').forEach(function (x) { x.disabled = true; });
                b.classList.add('chosen');
                var i = parseInt(b.dataset.i, 10);
                submit(i, function (r) {
                    stage.querySelectorAll('.option').forEach(function (x) {
                        var k = parseInt(x.dataset.i, 10);
                        if (k === r.feedback.answer) x.classList.add('right');
                        else if (k === i) x.classList.add('wrong');
                    });
                });
            });
        });
    }

    /* ------------------------------------------------------------------
     *  Renderizadores por tipo de juego
 * ---------------------------------------------------------------- */

    var RENDER = {
        classify: function (c) {
            return '<div class="case">' + messageMock(c) + '</div>' +
                '<div class="decide"><p class="decide-q">¿Qué es este mensaje?</p>' +
                '<button class="btn btn-danger btn-lg" data-a="phishing" type="button"><i class="bx bx-anchor"></i> Es phishing</button>' +
                '<button class="btn btn-success btn-lg" data-a="legit" type="button"><i class="bx bxs-check-circle"></i> Es legítimo</button></div>';
        },

        spot: function (c) {
            var ch = CHANNELS[c.channel] || CHANNELS.email;
            var h = '<div class="spot-help"><span><i class="bx bxs-torch"></i> Toca las partes sospechosas. Hay <b>' + c.count + '</b> señales.</span>' +
                '<span class="spot-count">Marcadas: <b id="spot-n">0</b></span></div>';
            h += '<div class="msg msg-' + esc(c.channel) + ' spot-msg"><div class="msg-channel">' + ch.icon + ' ' + ch.label + '</div>';
            h += '<div class="spot-head">' + c.header.map(function (f) {
                return '<div class="spot-field"><span class="sf-label">' + esc(f.label) + ':</span> <span class="seg" data-id="' + esc(f.id) + '">' + esc(f.text) + '</span></div>';
            }).join('') + '</div><div class="spot-body">';
            c.body.forEach(function (s, i) {
                if (s.p && i > 0) h += '<br><br>';
                h += '<span class="seg" data-id="' + esc(s.id) + '">' + esc(s.text) + '</span> ';
            });
            h += '</div>';
            if (c.button) h += '<div class="spot-btn"><span class="seg fake-btn" data-id="' + esc(c.button.id) + '">' + esc(c.button.text) + '</span>' +
                '<small class="hover-url"><i class="bx bx-link"></i> ' + esc(c.button.url) + '</small></div>';
            if (c.attachment) h += '<div class="attach"><span class="seg" data-id="' + esc(c.attachment.id) + '"><i class="bx bx-paperclip"></i> ' + esc(c.attachment.name) + '</span></div>';
            h += '</div><div class="decide"><button class="btn btn-primary btn-lg" id="spot-ok" type="button">Confirmar señales</button></div>';
            return h;
        },

        url: function (c) {
            return '<div class="case"><div class="url-context"><i class="bx bxs-envelope"></i> ' + esc(c.context) + '</div>' +
                '<div class="browser"><div class="browser-dots"><i></i><i></i><i></i></div>' +
                '<div class="browser-bar"><span class="lock">' + (c.url.indexOf('https') === 0 ? '<i class="bx bxs-lock-alt"></i>' : '<i class="bx bxs-error"></i>') + '</span>' +
                '<span class="browser-url" id="the-url">' + esc(c.url) + '</span></div></div>' +
                '<p class="muted small center">Pista: busca lo que está justo antes de la primera «/» después de «://».</p></div>' +
                '<div class="decide"><p class="decide-q">¿Entrarías a esta dirección?</p>' +
                '<button class="btn btn-success btn-lg" data-a="seguro" type="button"><i class="bx bxs-check-circle"></i> Sí, es segura</button>' +
                '<button class="btn btn-danger btn-lg" data-a="sospechoso" type="button"><i class="bx bxs-error"></i> No, es sospechosa</button></div>';
        },

        scenario: function (c) {
            return '<div class="case scenario"><h2>' + esc(c.title) + '</h2><p class="situation">' + esc(c.situation) + '</p>' +
                (c.message ? '<div class="sc-message"><small>' + esc(c.message.from) + '</small><div class="bubble">' + esc(c.message.text) + '</div></div>' : '') +
                '<p class="decide-q">' + esc(c.question || '¿Qué harías?') + '</p>' + optionsList(c.options) + '</div>';
        },

        chat: function (c) {
            return '<div class="phone"><div class="phone-head"><span class="ph-avatar">' + PHUI.icon(c.contact.avatar) + '</span>' +
                '<div><b>' + esc(c.contact.name) + '</b><small>' + esc(c.contact.sub) + '</small></div></div>' +
                '<div class="phone-body" id="chat-body"></div>' +
                '<div class="phone-replies" id="chat-replies" hidden><p class="decide-q">Elige tu respuesta:</p>' +
                optionsList(c.options, 'replies') + '</div></div>';
        },

        choice: function (c) {
            return '<div class="case"><h2 class="question">' + esc(c.question) + '</h2>' + optionsList(c.options) + '</div>';
        },

        quiz: function (c) {
            return '<div class="case quiz"><h2 class="question">' + esc(c.question) + '</h2>' + optionsList(c.options) + '</div>';
        },

        sort: function (c) {
            return '<div class="sort-area">' +
                '<div class="bin bin-safe" data-a="seguro"><span><i class="bx bxs-check-circle"></i></span><b>Seguro</b></div>' +
                '<div class="sort-card" id="sort-card" draggable="true"><span class="sc-icon">' + PHUI.icon(c.icon) + '</span><p>' + esc(c.card) + '</p>' +
                '<small class="muted">Arrástrala o usa los botones</small></div>' +
                '<div class="bin bin-risk" data-a="riesgoso"><span><i class="bx bxs-error"></i></span><b>Riesgoso</b></div>' +
                '</div><div class="decide">' +
                '<button class="btn btn-success btn-lg" data-a="seguro" type="button"><i class="bx bxs-check-circle"></i> Seguro</button>' +
                '<button class="btn btn-danger btn-lg" data-a="riesgoso" type="button"><i class="bx bxs-error"></i> Riesgoso</button></div>';
        }
    };

    function bindDecide(stage, onAnswer) {
        stage.querySelectorAll('.decide [data-a]').forEach(function (b) {
            b.addEventListener('click', function () {
                stage.querySelectorAll('.decide button').forEach(function (x) { x.disabled = true; });
                b.classList.add('chosen');
                onAnswer(b.dataset.a);
            });
        });
    }

    var BIND = {
        classify: function (stage) { bindDecide(stage, function (a) { submit(a); }); },
        url: function (stage) {
            bindDecide(stage, function (a) {
                submit(a, function (r) { highlightUrl(r.feedback.real_domain); });
            });
        },
        scenario: bindOptions,
        choice: bindOptions,
        quiz: bindOptions,

        spot: function (stage) {
            var n = 0;
            stage.querySelectorAll('.seg').forEach(function (s) {
                s.setAttribute('role', 'button');
                s.tabIndex = 0;
                var toggle = function () {
                    if (stage.classList.contains('locked')) return;
                    s.classList.toggle('marked');
                    n = stage.querySelectorAll('.seg.marked').length;
                    $('spot-n').textContent = n;
                };
                s.addEventListener('click', toggle);
                s.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
            });
            $('spot-ok').addEventListener('click', function () {
                var sel = Array.prototype.map.call(stage.querySelectorAll('.seg.marked'), function (s) { return s.dataset.id; });
                if (!sel.length) { PHUI.toast('Marca al menos una señal antes de confirmar.'); return; }
                $('spot-ok').disabled = true;
                submit(sel, function (r) {
                    var flags = r.feedback.flags;
                    stage.querySelectorAll('.seg').forEach(function (s) {
                        var isFlag = flags.indexOf(s.dataset.id) !== -1;
                        var marked = s.classList.contains('marked');
                        if (isFlag && marked) s.classList.add('hit');
                        else if (isFlag) s.classList.add('missed');
                        else if (marked) s.classList.add('false');
                    });
                });
            });
        },

        chat: function (stage, item) {
            var body = $('chat-body');
            var msgs = item.content.messages;
            var i = 0;
            (function nextMsg() {
                if (i >= msgs.length) { $('chat-replies').hidden = false; return; }
                var typing = document.createElement('div');
                typing.className = 'cb cb-' + msgs[i].from + ' typing';
                typing.innerHTML = '<span></span><span></span><span></span>';
                body.appendChild(typing);
                setTimeout(function () {
                    typing.classList.remove('typing');
                    typing.textContent = msgs[i].text;
                    i++;
                    body.scrollTop = body.scrollHeight;
                    nextMsg();
                }, msgs[i].from === 'me' ? 350 : 750);
            })();
            stage.querySelectorAll('.option').forEach(function (b) {
                b.addEventListener('click', function () {
                    stage.querySelectorAll('.option').forEach(function (x) { x.disabled = true; });
                    var i2 = parseInt(b.dataset.i, 10);
                    var mine = document.createElement('div');
                    mine.className = 'cb cb-me';
                    mine.textContent = item.content.options[i2];
                    body.appendChild(mine);
                    body.scrollTop = body.scrollHeight;
                    submit(i2, function (r) {
                        stage.querySelectorAll('.option').forEach(function (x) {
                            var k = parseInt(x.dataset.i, 10);
                            if (k === r.feedback.answer) x.classList.add('right');
                            else if (k === i2) x.classList.add('wrong');
                        });
                        mine.classList.add(r.correct ? 'cb-good' : 'cb-bad');
                    });
                });
            });
        },

        sort: function (stage) {
            var card = $('sort-card');
            var send = function (a) {
                if (stage.classList.contains('locked')) return;
                stage.querySelectorAll('.decide button').forEach(function (x) { x.disabled = true; });
                card.classList.add(a === 'seguro' ? 'fly-left' : 'fly-right');
                submit(a, function (r) {
                    stage.querySelector('.bin[data-a="' + r.feedback.answer + '"]').classList.add('bin-right');
                });
            };
            bindDecide(stage, send);
            card.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/plain', 'card'); card.classList.add('dragging'); });
            card.addEventListener('dragend', function () { card.classList.remove('dragging'); });
            stage.querySelectorAll('.bin').forEach(function (bin) {
                bin.addEventListener('dragover', function (e) { e.preventDefault(); bin.classList.add('over'); });
                bin.addEventListener('dragleave', function () { bin.classList.remove('over'); });
                bin.addEventListener('drop', function (e) { e.preventDefault(); bin.classList.remove('over'); send(bin.dataset.a); });
                bin.addEventListener('click', function () { send(bin.dataset.a); });
            });
        }
    };

    function highlightUrl(realDomain) {
        var el = $('the-url');
        if (!el || !realDomain) return;
        var url = el.textContent;
        var m = url.match(/^(https?:\/\/)([^\/]+)(.*)$/);
        if (!m) return;
        var host = m[2];
        var dom = realDomain.split(' ')[0];
        var pos = host.lastIndexOf(dom);
        if (pos === -1) return;
        el.innerHTML = '<span class="u-proto">' + esc(m[1]) + '</span>' +
            (pos > 0 ? '<span class="u-sub">' + esc(host.slice(0, pos)) + '</span>' : '') +
            '<span class="u-domain">' + esc(host.slice(pos)) + '</span>' +
            '<span class="u-path">' + esc(m[3]) + '</span>';
    }

    /* Información adicional en la retroalimentación */
    var EXTRA = {
        classify: function (fb) {
            var verdict = fb.answer === 'phishing' ? '<b class="tag-bad"><i class="bx bx-anchor"></i> Era phishing</b>' : '<b class="tag-good"><i class="bx bxs-check-circle"></i> Era legítimo</b>';
            return '<div class="fb-verdict">' + verdict + '</div>' + signalList(fb.signals, fb.answer === 'phishing' ? 'Señales que lo delatan' : 'Por qué es confiable');
        },
        url: function (fb) {
            return '<div class="fb-verdict">' + (fb.answer === 'seguro' ? '<b class="tag-good"><i class="bx bxs-check-circle"></i> Dirección segura</b>' : '<b class="tag-bad"><i class="bx bxs-error"></i> Dirección sospechosa</b>') +
                ' <span>Dominio real: <code class="u-domain">' + esc(fb.real_domain) + '</code></span></div>' +
                '<div class="url-legend"><span class="u-proto">protocolo</span> <span class="u-sub">subdominio (decoración)</span> <span class="u-domain">dominio real</span> <span class="u-path">ruta</span></div>';
        },
        spot: function (fb) {
            var h = '<div class="fb-verdict">Encontraste <b>' + fb.hits + '</b> de ' + fb.flags.length + ' señales' +
                (fb.wrong ? ' · <span class="tag-bad">' + fb.wrong + ' marcada(s) de más</span>' : '') + '</div><ul class="signals">';
            fb.flags.forEach(function (f) { if (fb.reasons[f]) h += '<li><i class="bx bxs-flag-alt"></i> ' + esc(fb.reasons[f]) + '</li>'; });
            return h + '</ul><div class="url-legend"><span class="seg hit">encontrada</span> <span class="seg missed">faltó</span> <span class="seg false">no era señal</span></div>';
        },
        sort: function (fb) {
            return '<div class="fb-verdict">' + (fb.answer === 'seguro' ? '<b class="tag-good"><i class="bx bxs-check-circle"></i> Es una práctica segura</b>' : '<b class="tag-bad"><i class="bx bxs-error"></i> Es una práctica riesgosa</b>') + '</div>';
        }
    };

    function signalList(list, title) {
        if (!list || !list.length) return '';
        return '<div class="signals-title">' + esc(title) + ':</div><ul class="signals">' +
            list.map(function (s) { return '<li>' + esc(s) + '</li>'; }).join('') + '</ul>';
    }

    /* Taller de contraseñas en la introducción del juego «choice» */
    function workshop() {
        if (G.type !== 'choice') return;
        var intro = $('intro');
        var box = document.createElement('div');
        box.className = 'workshop';
        box.innerHTML = '<h3><i class="bx bxs-flask"></i> Taller de claves</h3><p class="muted small">Prueba ideas de contraseñas y mira su fuerza. <b>No escribas tu contraseña real.</b> Nada de lo que escribas aquí se envía ni se guarda.</p>' +
            '<input type="text" id="ws-input" placeholder="Ej.: Tortuga-Lapiz-Volcan-Mate9" autocomplete="off" spellcheck="false">' +
            '<div class="pw-meter" id="ws-meter"><span></span></div><ul class="ws-tips" id="ws-tips"></ul>';
        intro.insertBefore(box, $('start-btn'));
        var input = $('ws-input');
        input.addEventListener('input', function () {
            var s = PHUI.meter(input.value, $('ws-meter'));
            $('ws-tips').innerHTML = input.value ? '<li><b>' + esc(s.label) + '</b></li>' + s.tips.map(function (t) { return '<li>' + esc(t) + '</li>'; }).join('') : '';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        workshop();
        $('start-btn').addEventListener('click', start);
        window.addEventListener('beforeunload', function (e) {
            if (state.attempt && state.answered > 0 && state.answered < state.items.length) { e.preventDefault(); e.returnValue = ''; }
        });
    });
})();
