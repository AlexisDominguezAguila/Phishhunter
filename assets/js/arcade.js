/* PhishHunter · Zona de refuerzo
 * Memorama, Relaciona, Arma tu clave y Simulador de oficina.
 * El servidor corrige todo: el navegador no conoce parejas, trampas ni respuestas.
 */
(function () {
    'use strict';

    var G = window.PH_ARCADE;
    var esc = PHUI.esc;
    var $ = function (id) { return document.getElementById(id); };
    var S = { attempt: null, data: null, busy: false };

    function api(action, data) {
        data = data || {};
        data.action = action;
        if (S.attempt) data.attempt = S.attempt;
        return PHUI.api('api/arcade.php', data);
    }

    function mascot(mood, text, cls) {
        return '<div class="chispa-say ' + (cls || '') + '"><img class="mascot" src="' + esc(G.mascot + mood + '.svg') + '" alt="Chispa">' +
            '<div class="chispa-bubble"><b>Chispa</b><p>' + text + '</p></div></div>';
    }

    function progress(done, total, extra) {
        $('pp-bar').style.width = Math.round(done / Math.max(1, total) * 100) + '%';
        $('pp-text').textContent = done + '/' + total;
        $('pp-extra').innerHTML = extra || '';
    }

    function shuffle(a) {
        for (var i = a.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = a[i]; a[i] = a[j]; a[j] = t; }
        return a;
    }

    function start() {
        if (S.busy) return;
        S.busy = true;
        $('start-btn').disabled = true;
        S.attempt = null;
        api('start', { game: G.slug }).then(function (r) {
            S.busy = false;
            if (!r.ok) { PHUI.toast(r.error || 'No se pudo iniciar', 'error'); $('start-btn').disabled = false; return; }
            S.attempt = r.attempt_id;
            S.data = r.data;
            $('intro').hidden = true;
            $('result').hidden = true;
            var stage = $('stage');
            stage.hidden = false;
            stage.className = 'stage arcade-stage stage-' + G.type;
            GAMES[G.type].init(stage, r.data);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function finish() {
        if (S.busy) return;
        S.busy = true;
        api('finish').then(function (r) {
            S.busy = false;
            if (!r.ok) { PHUI.toast(r.error || 'No se pudo terminar', 'error'); return; }
            S.attempt = null;
            if (G.type === 'office') stopOffice();
            $('stage').hidden = true;
            showResult(r);
        });
    }

    /* ==================================================================
     *  1. MEMORAMA
 * ================================================================ */
    var MEMORY = {
        init: function (stage, d) {
            var m = this;
            m.pairs = d.pairs; m.found = 0; m.misses = 0; m.lock = false; m.first = null;
            stage.innerHTML =
                '<div class="mem-layout">' +
                    '<div class="mem-grid" id="mem-grid">' + d.cards.map(function (c) {
                        return '<button type="button" class="mem-card side-' + esc(c.side) + '" data-t="' + esc(c.t) + '" aria-label="Carta boca abajo">' +
                            '<span class="mem-inner"><span class="mem-back"><img src="' + esc(G.mascot + 'hola.svg') + '" alt=""></span>' +
                            '<span class="mem-front"></span></span></button>';
                    }).join('') + '</div>' +
                    '<aside class="mem-side" id="mem-side">' + mascot('hola', 'Encuentra las parejas. Las cartas verdes son <b>' + (G.slug === 'memorama-claves' ? 'contraseñas o hábitos' : 'términos') + '</b> y las amarillas son <b>' + (G.slug === 'memorama-claves' ? 'diagnósticos' : 'definiciones') + '</b>.') +
                    '<div class="mem-learned" id="mem-learned"></div></aside>' +
                '</div>';
            m.update();
            stage.querySelectorAll('.mem-card').forEach(function (b) {
                b.addEventListener('click', function () { m.flip(b); });
            });
        },
        update: function () {
            var free = Math.max(0, this.pairs - this.misses);
            progress(this.found, this.pairs, '<i class="bx bxs-x-circle"></i> <b>' + this.misses + '</b> ' + (this.misses === 1 ? 'fallo' : 'fallos') + (free ? ' · <span class="muted small">' + free + ' de cortesía</span>' : ''));
        },
        flip: function (b) {
            var m = this;
            if (m.lock || S.busy || b.classList.contains('flipped')) return;
            S.busy = true;
            api('flip', { token: b.dataset.t }).then(function (r) {
                S.busy = false;
                if (!r.ok) { PHUI.toast(r.error || 'Error', 'error'); return; }
                b.querySelector('.mem-front').textContent = r.text;
                b.classList.add('flipped');
                b.setAttribute('aria-label', r.text);
                if (!r.pair) { m.first = b; return; }
                var a = m.first; m.first = null;
                if (r.match) {
                    m.found++;
                    a.classList.add('matched'); b.classList.add('matched');
                    var txt = [a, b].map(function (x) { return x.querySelector('.mem-front').textContent; });
                    var li = document.createElement('div');
                    li.className = 'learned-item';
                    li.innerHTML = '<b>' + esc(txt[0]) + '</b> <i class="bx bx-transfer-alt"></i> ' + esc(txt[1]) + '<small><i class="bx bxs-bulb"></i> ' + esc(r.explanation) + '</small>';
                    $('mem-learned').prepend(li);
                    m.update();
                    if (r.done) setTimeout(finish, 900);
                } else {
                    m.misses = r.misses;
                    m.update();
                    m.lock = true;
                    a.classList.add('nope'); b.classList.add('nope');
                    setTimeout(function () {
                        [a, b].forEach(function (x) { x.classList.remove('flipped', 'nope'); x.querySelector('.mem-front').textContent = ''; x.setAttribute('aria-label', 'Carta boca abajo'); });
                        m.lock = false;
                    }, 1300);
                }
            });
        }
    };

    /* ==================================================================
     *  2. RELACIONA
 * ================================================================ */
    var COLORS = ['#5E9B1E', '#E3553A', '#2BB39A', '#D4A017', '#E0457B', '#5B6CFF'];

    var MATCH = {
        init: function (stage, d) {
            this.rounds = d.rounds; this.idx = 0; this.score = 0;
            this.stage = stage;
            this.show();
        },
        show: function () {
            var m = this, r = m.rounds[m.idx];
            m.links = {}; m.sel = null; m.checked = false; m.result = null;
            progress(m.idx, m.rounds.length, '<i class="bx bxs-star"></i> <b>' + Math.round(m.score) + '</b> pts');
            m.stage.innerHTML =
                '<div class="case-num">Ronda ' + (m.idx + 1) + ' de ' + m.rounds.length + '</div>' +
                '<h2 class="question">' + esc(r.prompt) + '</h2>' +
                '<div class="match-board" id="mboard"><svg class="match-lines" id="mlines"></svg>' +
                    '<div class="match-col" id="mleft">' + r.left.map(function (x) { return '<button type="button" class="match-item left" data-id="' + esc(x.id) + '">' + esc(x.text) + '<i class="dot"></i></button>'; }).join('') + '</div>' +
                    '<div class="match-col" id="mright">' + r.right.map(function (x) { return '<button type="button" class="match-item right" data-id="' + esc(x.id) + '"><i class="dot"></i>' + esc(x.text) + '</button>'; }).join('') + '</div>' +
                '</div>' +
                '<div class="decide"><button class="btn btn-soft" type="button" id="m-reset"><i class="bx bx-reset"></i> Borrar uniones</button>' +
                '<button class="btn btn-primary btn-lg" type="button" id="m-check" disabled>Comprobar</button></div>' +
                '<div id="m-feedback"></div>';
            m.stage.querySelectorAll('.match-item.left').forEach(function (b) {
                b.addEventListener('click', function () { if (!m.checked) m.pick(b); });
                b.addEventListener('pointerdown', function (e) { if (!m.checked) m.dragStart(e, b); });
            });
            m.stage.querySelectorAll('.match-item.right').forEach(function (b) {
                b.addEventListener('click', function () { if (!m.checked && m.sel) m.link(m.sel, b.dataset.id); });
            });
            $('m-reset').onclick = function () { if (m.checked) return; m.links = {}; m.sel = null; m.paint(); };
            $('m-check').onclick = function () { m.check(); };
            window.onresize = function () { m.paint(); };
            m.paint();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        pick: function (b) {
            this.sel = this.sel === b.dataset.id ? null : b.dataset.id;
            this.paint();
        },
        link: function (l, r) {
            var m = this;
            Object.keys(m.links).forEach(function (k) { if (m.links[k] === r) delete m.links[k]; });
            m.links[l] = r;
            m.sel = null;
            m.paint();
        },
        dragStart: function (e, b) {
            var m = this;
            var board = $('mboard'), svg = $('mlines');
            var moved = false;
            var tmp = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            var p0 = m.anchor(b, 'right');
            tmp.setAttribute('x1', p0.x); tmp.setAttribute('y1', p0.y); tmp.setAttribute('x2', p0.x); tmp.setAttribute('y2', p0.y);
            tmp.setAttribute('class', 'tmp');
            function move(ev) {
                moved = true;
                var br = board.getBoundingClientRect();
                if (!tmp.parentNode) svg.appendChild(tmp);
                tmp.setAttribute('x2', ev.clientX - br.left); tmp.setAttribute('y2', ev.clientY - br.top);
            }
            function up(ev) {
                document.removeEventListener('pointermove', move);
                document.removeEventListener('pointerup', up);
                if (tmp.parentNode) tmp.remove();
                if (!moved) return;
                var el = document.elementFromPoint(ev.clientX, ev.clientY);
                var target = el && el.closest('.match-item.right');
                if (target) m.link(b.dataset.id, target.dataset.id);
            }
            document.addEventListener('pointermove', move);
            document.addEventListener('pointerup', up);
        },
        anchor: function (el, side) {
            var br = $('mboard').getBoundingClientRect(), r = el.getBoundingClientRect();
            return { x: (side === 'right' ? r.right : r.left) - br.left, y: r.top + r.height / 2 - br.top };
        },
        paint: function () {
            var m = this, svg = $('mlines');
            if (!svg) return;
            var board = $('mboard');
            svg.setAttribute('width', board.clientWidth); svg.setAttribute('height', board.clientHeight);
            svg.innerHTML = '';
            var lefts = m.stage.querySelectorAll('.match-item.left');
            m.stage.querySelectorAll('.match-item').forEach(function (x) { x.classList.remove('sel', 'linked'); x.style.removeProperty('--lc'); });
            lefts.forEach(function (lb, i) {
                var id = lb.dataset.id;
                if (m.sel === id) lb.classList.add('sel');
                if (!m.links[id]) return;
                var rb = m.stage.querySelector('.match-item.right[data-id="' + m.links[id] + '"]');
                var color = COLORS[i % COLORS.length];
                if (m.result) color = m.result[id].ok ? '#3F7412' : '#E3553A';
                lb.classList.add('linked'); rb.classList.add('linked');
                lb.style.setProperty('--lc', color); rb.style.setProperty('--lc', color);
                var a = m.anchor(lb, 'right'), b = m.anchor(rb, 'left');
                svg.insertAdjacentHTML('beforeend', '<line x1="' + a.x + '" y1="' + a.y + '" x2="' + b.x + '" y2="' + b.y + '" stroke="' + color + '"' + (m.result && !m.result[id].ok ? ' stroke-dasharray="6 6"' : '') + '/>');
            });
            if (m.result) {
                lefts.forEach(function (lb) {
                    var res = m.result[lb.dataset.id];
                    if (res.ok) return;
                    var rb = m.stage.querySelector('.match-item.right[data-id="' + res.right + '"]');
                    var a = m.anchor(lb, 'right'), b = m.anchor(rb, 'left');
                    svg.insertAdjacentHTML('beforeend', '<line class="fix" x1="' + a.x + '" y1="' + a.y + '" x2="' + b.x + '" y2="' + b.y + '" stroke="#3F7412"/>');
                });
            }
            var btn = $('m-check');
            if (btn) btn.disabled = m.checked || Object.keys(m.links).length < lefts.length;
        },
        check: function () {
            var m = this, r = m.rounds[m.idx];
            if (S.busy) return;
            S.busy = true;
            m.checked = true;
            $('m-check').disabled = true;
            api('match', { item: r.id, links: m.links }).then(function (res) {
                S.busy = false;
                if (!res.ok) { PHUI.toast(res.error || 'Error', 'error'); m.checked = false; m.paint(); return; }
                m.result = res.result;
                m.score += res.points * 10;
                m.stage.querySelectorAll('.match-item.left').forEach(function (lb) {
                    lb.classList.add(res.result[lb.dataset.id].ok ? 'ok' : 'bad');
                });
                m.paint();
                progress(m.idx + 1, m.rounds.length, '<i class="bx bxs-star"></i> <b>' + Math.round(m.score) + '</b> pts');
                var last = m.idx >= m.rounds.length - 1;
                $('m-feedback').innerHTML = '<div class="feedback ' + (res.correct ? 'fb-ok' : res.points > 0 ? 'fb-partial' : 'fb-bad') + '">' +
                    mascot(res.correct ? 'feliz' : 'piensa', (res.correct ? '¡Todo conectado! ' : 'Acertaste ' + res.hits + ' de ' + res.total + '. Las líneas verdes muestran las uniones correctas. ') + esc(res.explanation), 'in-feedback') +
                    '<button class="btn btn-primary btn-lg" id="m-next" type="button">' + (last ? 'Ver resultados <i class="bx bxs-flag-checkered"></i>' : 'Siguiente ronda <i class="bx bx-right-arrow-alt"></i>') + '</button></div>';
                $('m-next').onclick = function () { m.result = null; if (last) finish(); else { m.idx++; m.show(); } };
                $('m-next').scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }
    };

    /* ==================================================================
     *  3. ARMA TU CLAVE
 * ================================================================ */
    function cardKind(t) {
        if (/^\d+$/.test(t)) return 'number';
        if (!/[A-Za-zÁÉÍÓÚáéíóúÑñÜü0-9]/.test(t)) return 'symbol';
        return 'word';
    }

    var BUILDER = {
        init: function (stage, d) {
            this.rounds = d.rounds; this.idx = 0; this.score = 0; this.stage = stage;
            this.show();
        },
        show: function () {
            var b = this, r = b.rounds[b.idx];
            b.bar = []; b.done = false;
            progress(b.idx, b.rounds.length, '<i class="bx bxs-star"></i> <b>' + Math.round(b.score) + '</b> pts');
            b.stage.innerHTML =
                '<div class="case-num">Ronda ' + (b.idx + 1) + ' de ' + b.rounds.length + '</div>' +
                '<div class="builder-brief"><h2>' + esc(r.goal) + '</h2><p class="profile"><i class="bx bxs-id-card"></i> <b>Tu perfil:</b> ' + esc(r.profile) + '</p></div>' +
                '<div class="pw-bar" id="pw-bar" aria-label="Tu contraseña"><span class="pw-empty">Toca o arrastra cartas aquí</span></div>' +
                '<div class="pw-preview"><code id="pw-text">—</code><div class="pw-meter" id="pw-meter"><span></span></div><small id="pw-label" class="muted"></small></div>' +
                '<ul class="pw-rules" id="pw-rules"></ul>' +
                '<div class="deck" id="deck">' + r.cards.map(function (c) {
                    return '<button type="button" class="pcard k-' + cardKind(c.text) + '" draggable="true" data-id="' + esc(c.id) + '">' + esc(c.text) + '</button>';
                }).join('') + '</div>' +
                '<div class="decide"><button class="btn btn-soft" type="button" id="b-clear"><i class="bx bx-reset"></i> Vaciar</button>' +
                '<button class="btn btn-primary btn-lg" type="button" id="b-check" disabled>Comprobar clave</button></div>' +
                '<div id="b-feedback"></div>';
            var bar = $('pw-bar');
            b.stage.querySelectorAll('.pcard').forEach(function (c) {
                c.addEventListener('click', function () { if (!b.done) b.toggle(c.dataset.id); });
                c.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/plain', c.dataset.id); c.classList.add('dragging'); });
                c.addEventListener('dragend', function () { c.classList.remove('dragging'); });
            });
            bar.addEventListener('dragover', function (e) { e.preventDefault(); bar.classList.add('over'); });
            bar.addEventListener('dragleave', function () { bar.classList.remove('over'); });
            bar.addEventListener('drop', function (e) {
                e.preventDefault(); bar.classList.remove('over');
                var id = e.dataTransfer.getData('text/plain');
                if (!b.done && id && b.bar.indexOf(id) === -1) { b.bar.push(id); b.render(); }
            });
            $('b-clear').onclick = function () { if (!b.done) { b.bar = []; b.render(); } };
            $('b-check').onclick = function () { b.check(); };
            b.render();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        toggle: function (id) {
            var i = this.bar.indexOf(id);
            if (i === -1) this.bar.push(id); else this.bar.splice(i, 1);
            this.render();
        },
        text: function (id) { return this.stage.querySelector('.pcard[data-id="' + id + '"]').textContent; },
        render: function () {
            var b = this, bar = $('pw-bar'), deck = $('deck');
            b.stage.querySelectorAll('.pcard').forEach(function (c) {
                var inBar = b.bar.indexOf(c.dataset.id) !== -1;
                if (inBar && c.parentNode !== bar) bar.appendChild(c);
                if (!inBar && c.parentNode !== deck) deck.appendChild(c);
            });
            b.bar.forEach(function (id) { bar.appendChild(b.stage.querySelector('.pcard[data-id="' + id + '"]')); });
            bar.querySelector('.pw-empty').hidden = b.bar.length > 0;
            var pw = b.bar.map(function (id) { return b.text(id); }).join('');
            $('pw-text').textContent = pw || '—';
            var s = PHUI.meter(pw, $('pw-meter'));
            $('pw-label').textContent = pw ? s.label : '';
            var kinds = { word: 0, number: 0, symbol: 0 };
            b.bar.forEach(function (id) { kinds[cardKind(b.text(id))]++; });
            var rules = [[kinds.word >= 3, '3 palabras o más'], [kinds.number >= 1, 'Un número'], [kinds.symbol >= 1, 'Un signo'], [pw.length >= 14, '14 caracteres o más (' + pw.length + ')'], [null, 'Sin datos personales ni claves débiles (¡revisa tu perfil!)']];
            $('pw-rules').innerHTML = rules.map(function (r) { return '<li class="' + (r[0] === null ? 'q' : r[0] ? 'y' : 'n') + '">' + (r[0] === null ? '<i class="bx bx-question-mark"></i>' : r[0] ? '<i class="bx bxs-check-circle"></i>' : '<i class="bx bx-square"></i>') + ' ' + esc(r[1]) + '</li>'; }).join('');
            $('b-check').disabled = b.done || !b.bar.length;
        },
        check: function () {
            var b = this, r = b.rounds[b.idx];
            if (S.busy) return;
            S.busy = true; b.done = true; $('b-check').disabled = true;
            api('build', { item: r.id, cards: b.bar }).then(function (res) {
                S.busy = false;
                if (!res.ok) { PHUI.toast(res.error || 'Error', 'error'); b.done = false; b.render(); return; }
                b.score += res.points * 10;
                (res.traps || []).forEach(function (id) { var c = b.stage.querySelector('.pcard[data-id="' + id + '"]'); if (c) c.classList.add('trap'); });
                progress(b.idx + 1, b.rounds.length, '<i class="bx bxs-star"></i> <b>' + Math.round(b.score) + '</b> pts');
                var last = b.idx >= b.rounds.length - 1;
                var trapMsg = res.trap_texts.length ? '¡Cuidado! Usaste ' + res.trap_texts.map(function (t) { return '«' + esc(t) + '»'; }).join(', ') + ': son datos de tu perfil o claves de las listas filtradas. Cualquiera podría adivinarlos. ' : '';
                $('b-feedback').innerHTML = '<div class="feedback ' + (res.correct ? 'fb-ok' : res.points > 0 ? 'fb-partial' : 'fb-bad') + '">' +
                    mascot(res.correct ? 'fiesta' : res.trap_texts.length ? 'alerta' : 'piensa', (res.correct ? '¡Clave blindada! Larga, sin datos personales y fácil de recordar. ' : trapMsg || 'Casi: revisa las reglas que faltaron. ') + '<br><code>' + esc(res.password) + '</code>', 'in-feedback') +
                    '<ul class="signals">' + res.rules.map(function (x) { return '<li>' + (x.ok ? '<i class="bx bxs-check-circle"></i> ' : '<i class="bx bxs-x-circle"></i> ') + esc(x.text) + '</li>'; }).join('') + '</ul>' +
                    '<p class="muted small">Recuerda: esta clave es solo de práctica. Crea la tuya con la misma receta, pero con palabras que solo tú elijas.</p>' +
                    '<button class="btn btn-primary btn-lg" id="b-next" type="button">' + (last ? 'Ver resultados <i class="bx bxs-flag-checkered"></i>' : 'Siguiente ronda <i class="bx bx-right-arrow-alt"></i>') + '</button></div>';
                $('b-next').onclick = function () { if (last) finish(); else { b.idx++; b.show(); } };
                $('b-next').scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }
    };

    /* ==================================================================
     *  4. SIMULADOR DE OFICINA
 * ================================================================ */
    var CH = {
        email:    { ico: 'bxs-envelope', label: 'Correo' },
        chat:     { ico: 'bxs-message-rounded-dots', label: 'Chat interno' },
        whatsapp: { ico: 'bxl-whatsapp', label: 'WhatsApp' },
        sms:      { ico: 'bxs-mobile', label: 'SMS' },
        phone:    { ico: 'bxs-phone-call', label: 'Llamada' }
    };
    var ACT = {
        atender:   ['<i class="bx bxs-check-circle"></i>', 'Atender / hacer lo que pide'],
        verificar: ['<i class="bx bxs-phone-call"></i>', 'Verificar por otro canal'],
        reportar:  ['<i class="bx bxs-flag-alt"></i>', 'Reportar como sospechoso'],
        archivar:  ['<i class="bx bxs-archive"></i>', 'Archivar / ignorar'],
        entregar:  ['<i class="bx bxs-key"></i>', 'Ingresaste tus datos']
    };
    var officeTimer = null, clockTimer = null;

    function stopOffice() { clearTimeout(officeTimer); clearInterval(clockTimer); }

    var OFFICE = {
        init: function (stage, d) {
            var o = this;
            o.d = d; o.stage = stage;
            o.arrived = 0; o.handled = {}; o.docsSent = {}; o.open = null; o.minutes = 8 * 60 + 30;
            stage.innerHTML =
                '<div class="office">' +
                    '<div class="os-bar"><span><i class="bx bxs-business"></i> <b>' + esc(d.company) + '</b></span><span class="muted">' + esc(d.role) + '</span><span class="os-clock" id="os-clock"></span></div>' +
                    '<div class="os-body">' +
                        '<aside class="os-note"><div class="sticky"><b><i class="bx bxs-pin"></i> Nota de ' + esc(d.boss) + '</b>' + d.note.map(function (l) { return '<p>' + esc(l) + '</p>'; }).join('') + '</div>' +
                            mascot('hola', '¡Primer día! Completa los documentos y atiende los mensajes con calma. Nadie te apura: <b>piensa antes de hacer clic</b>.', 'os-mascot') + '</aside>' +
                        '<section class="os-docs"><h3><i class="bx bxs-folder-open"></i> Mis tareas</h3><div id="os-docs"></div></section>' +
                        '<section class="os-inbox"><h3><i class="bx bxs-inbox"></i> Bandeja <span class="os-count" id="os-count">0</span></h3><ul class="os-list" id="os-list"></ul>' +
                            '<div class="os-viewer" id="os-viewer"><p class="muted center">Selecciona un mensaje para leerlo.</p></div></section>' +
                    '</div>' +
                    '<div class="decide"><button class="btn btn-primary btn-lg" type="button" id="os-end" disabled><i class="bx bxs-flag-checkered"></i> Terminar turno</button><p class="muted small" id="os-end-hint"></p></div>' +
                '</div>' +
                '<div class="modal" id="fake-web" hidden></div>';
            o.renderDocs();
            o.tickClock();
            clockTimer = setInterval(function () { o.minutes += 1; o.tickClock(); }, 6000);
            o.arrive(); o.arrive();
            o.schedule(18000);
            $('os-end').onclick = function () { finish(); };
            o.status();
        },
        tickClock: function () {
            var h = Math.floor(this.minutes / 60), m = this.minutes % 60;
            $('os-clock').innerHTML = PHUI.icon('bxs-time-five') + ' ' + ((h - 1) % 12 + 1) + ':' + (m < 10 ? '0' : '') + m + (h < 12 ? ' a. m.' : ' p. m.');
        },
        schedule: function (ms) {
            var o = this;
            clearTimeout(officeTimer);
            if (o.arrived >= o.d.messages.length) return;
            officeTimer = setTimeout(function () { o.arrive(); o.schedule(20000); }, ms);
        },
        arrive: function () {
            var o = this;
            if (o.arrived >= o.d.messages.length) return;
            var m = o.d.messages[o.arrived++];
            var li = document.createElement('li');
            li.className = 'os-msg unread new';
            li.dataset.id = m.id;
            li.innerHTML = '<span class="os-ch">' + PHUI.icon(CH[m.channel].ico) + '</span><div><b>' + esc(m.from_name) + '</b><small>' + esc(m.subject || m.body.slice(0, 48) + '…') + '</small></div><span class="os-state"></span>';
            li.onclick = function () { o.read(m.id); };
            $('os-list').prepend(li);
            setTimeout(function () { li.classList.remove('new'); }, 900);
            PHUI.toast('Nuevo ' + CH[m.channel].label.toLowerCase() + ': ' + m.from_name, 'info', CH[m.channel].ico);
            o.status();
        },
        msg: function (id) { return this.d.messages.filter(function (m) { return m.id === id; })[0]; },
        read: function (id) {
            var o = this, m = o.msg(id);
            o.open = id;
            o.stage.querySelectorAll('.os-msg').forEach(function (x) { x.classList.toggle('active', x.dataset.id === id); });
            var li = o.stage.querySelector('.os-msg[data-id="' + id + '"]');
            li.classList.remove('unread');
            var h = '<div class="os-read ch-' + esc(m.channel) + '"><div class="os-read-head"><span class="os-ch">' + PHUI.icon(CH[m.channel].ico) + '</span><div><b>' + esc(m.from_name) + '</b>' +
                '<small>' + esc(m.from) + '</small></div><span class="muted small">' + CH[m.channel].label + '</span></div>';
            if (m.subject) h += '<div class="os-subject">' + esc(m.subject) + '</div>';
            h += '<div class="os-text">' + esc(m.body).replace(/\n/g, '<br>') + '</div>';
            if (m.link) h += '<button type="button" class="os-link" id="os-link"><i class="bx bx-link"></i> ' + esc(m.link.text) + '</button><small class="hover-url">Al pasar el mouse: <code>' + esc(m.link.url) + '</code></small>';
            if (m.attachment) h += '<div class="attach"><i class="bx bx-paperclip"></i> ' + esc(m.attachment) + '</div>';
            h += '</div>';
            var done = o.handled[id];
            if (done) {
                h += '<div class="os-done">Decisión registrada: <b>' + ACT[done][0] + ' ' + ACT[done][1] + '</b>. Verás el resultado al terminar el turno.</div>';
            } else {
                h += '<div class="os-actions"><p class="decide-q">¿Qué haces con este mensaje?</p>' +
                    ['atender', 'verificar', 'reportar', 'archivar'].map(function (a) {
                        return '<button type="button" class="btn btn-soft os-act act-' + a + '" data-a="' + a + '">' + ACT[a][0] + ' ' + ACT[a][1] + '</button>';
                    }).join('') + '</div>';
            }
            $('os-viewer').innerHTML = h;
            if (!done) {
                $('os-viewer').querySelectorAll('.os-act').forEach(function (b) {
                    b.onclick = function () {
                        if (b.dataset.a === 'atender' && m.link && m.login) o.fakeWeb(m);
                        else o.decide(m.id, b.dataset.a);
                    };
                });
            }
            var link = $('os-link');
            if (link) link.onclick = function () {
                if (o.handled[id]) { PHUI.toast('Ya tomaste una decisión sobre este mensaje.'); return; }
                if (m.login) o.fakeWeb(m); else o.decide(m.id, 'atender');
            };
            if (window.innerWidth < 960) $('os-viewer').scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        fakeWeb: function (m) {
            var o = this, box = $('fake-web');
            var host = m.link.url.replace(/^https?:\/\//, '');
            box.innerHTML = '<div class="modal-box fake-browser">' +
                '<div class="browser"><div class="browser-dots"><i></i><i></i><i></i></div><div class="browser-bar"><span class="lock"><i class="bx bxs-lock-alt"></i></span><span class="browser-url">' + esc(m.link.url) + '</span></div></div>' +
                '<div class="fake-page"><div class="fake-logo">' + esc(host.split('/')[0].split('.')[0].replace(/-/g, ' ')) + '</div>' +
                '<h3>Inicia sesión para continuar</h3>' +
                '<form id="fake-form" autocomplete="off"><label>Usuario o correo<input type="text" name="u" placeholder="usuario@correo.com" autocomplete="off"></label>' +
                '<label>Contraseña<input type="password" name="p" placeholder="••••••••" autocomplete="new-password"></label>' +
                '<p class="muted small"><i class="bx bxs-error"></i> Simulación: no escribas datos reales. Nada se envía ni se guarda.</p>' +
                '<button class="btn btn-primary btn-block" type="submit">Ingresar</button></form>' +
                '<button class="btn btn-ghost btn-block" type="button" id="fake-close"><i class="bx bx-x"></i> Cerrar esta página</button></div></div>';
            box.hidden = false;
            $('fake-form').onsubmit = function (e) {
                e.preventDefault();
                box.querySelector('.fake-page').innerHTML = '<div class="fake-loading"><span class="spinner"></span><p>Verificando…</p></div>';
                setTimeout(function () {
                    box.querySelector('.fake-page').innerHTML = '<h3>Error 503</h3><p>El servicio no está disponible. Inténtalo más tarde.</p><button class="btn btn-soft btn-block" type="button" id="fake-close">Cerrar</button>';
                    $('fake-close').onclick = function () { box.hidden = true; box.innerHTML = ''; };
                }, 1400);
                o.decide(m.id, 'entregar');
            };
            $('fake-close').onclick = function () { box.hidden = true; box.innerHTML = ''; o.decide(m.id, 'atender'); };
        },
        decide: function (id, act) {
            var o = this;
            if (o.handled[id] || S.busy) return;
            S.busy = true;
            api('office', { msg: id, act: act }).then(function (r) {
                S.busy = false;
                if (!r.ok) { PHUI.toast(r.error || 'Error', 'error'); return; }
                o.handled[id] = act;
                var li = o.stage.querySelector('.os-msg[data-id="' + id + '"]');
                li.classList.add('handled');
                li.querySelector('.os-state').innerHTML = ACT[act][0];
                if (o.open === id) o.read(id);
                o.status();
                var pending = o.pending();
                if (!pending && o.arrived < o.d.messages.length) o.schedule(3500);
            });
        },
        pending: function () {
            var o = this, n = 0;
            for (var i = 0; i < o.arrived; i++) if (!o.handled[o.d.messages[i].id]) n++;
            return n;
        },
        renderDocs: function () {
            var o = this;
            var firstOpen = false;
            $('os-docs').innerHTML = o.d.docs.map(function (doc) {
                var sent = o.docsSent[doc.id];
                var open = !sent && !firstOpen;
                if (open) firstOpen = true;
                return '<details class="os-doc ' + (sent ? 'sent' : '') + '"' + (open ? ' open' : '') + '><summary><span>' + PHUI.icon(doc.icon) + '</span> ' + esc(doc.title) +
                    '<span class="os-doc-state">' + (sent ? '<i class="bx bx-check"></i> Enviado' : 'Pendiente') + '</span></summary>' +
                    '<form class="os-form" data-doc="' + esc(doc.id) + '">' + doc.fields.map(function (f) {
                        var val = sent ? (sent[f.id] || '') : '';
                        var input = f.type === 'select'
                            ? '<select name="' + esc(f.id) + '"' + (sent ? ' disabled' : '') + '><option value="">— Elige —</option>' + f.options.map(function (op) { return '<option' + (op === val ? ' selected' : '') + '>' + esc(op) + '</option>'; }).join('') + '</select>'
                            : '<input type="text" name="' + esc(f.id) + '" value="' + esc(val) + '" autocomplete="off"' + (sent ? ' disabled' : '') + '>';
                        return '<label>' + esc(f.label) + input + '</label>';
                    }).join('') + (sent ? '' : '<button class="btn btn-primary btn-sm" type="submit"><i class="bx bxs-send"></i> Enviar documento</button>') + '</form></details>';
            }).join('');
            $('os-docs').querySelectorAll('.os-form').forEach(function (f) {
                f.onsubmit = function (e) {
                    e.preventDefault();
                    var vals = {}, empty = false;
                    Array.prototype.forEach.call(f.elements, function (el) { if (el.name) { vals[el.name] = el.value; if (!el.value.trim()) empty = true; } });
                    if (empty) { PHUI.toast('Completa todos los campos antes de enviar.'); return; }
                    if (S.busy) return;
                    S.busy = true;
                    api('doc', { doc: f.dataset.doc, values: vals }).then(function (r) {
                        S.busy = false;
                if (!r.ok) { PHUI.toast(r.error || 'Error', 'error'); return; }
                        o.docsSent[f.dataset.doc] = vals;
                        PHUI.toast('Documento enviado', 'success', 'bxs-send');
                        o.renderDocs();
                        o.status();
                    });
                };
            });
        },
        status: function () {
            var o = this;
            var docs = Object.keys(o.docsSent).length, nd = o.d.docs.length;
            var handled = Object.keys(o.handled).length, nm = o.d.messages.length;
            var unread = o.stage.querySelectorAll('.os-msg.unread').length;
            $('os-count').textContent = unread;
            $('os-count').hidden = !unread;
            progress(docs + handled, nd + nm, '<i class="bx bxs-file"></i> ' + docs + '/' + nd + ' · <i class="bx bxs-inbox"></i> ' + handled + '/' + nm);
            var ready = docs === nd && handled === nm;
            $('os-end').disabled = !ready;
            $('os-end-hint').textContent = ready ? '¡Todo listo! Termina tu turno para ver el informe.' :
                'Para terminar: envía todos los documentos y decide qué hacer con cada mensaje' + (o.arrived < nm ? ' (aún llegarán más mensajes).' : '.');
        }
    };

    var GAMES = { memory: MEMORY, match: MATCH, builder: BUILDER, office: OFFICE };

    /* ==================================================================
     *  RESULTADO
 * ================================================================ */
    function officeReport(rep) {
        var name = { atender: 'Atender', verificar: 'Verificar', reportar: 'Reportar', archivar: 'Archivar', entregar: 'Entregó datos' };
        var h = '<div class="report"><h2><i class="bx bx-clipboard"></i> Informe del turno</h2>' +
            '<div class="res-grid small-grid"><div><b>' + rep.msg_percent + '%</b><span>mensajes</span></div><div><b>' + rep.doc_percent + '%</b><span>documentos</span></div>' +
            '<div><b>' + rep.fell + '</b><span>engaños en los que caíste</span></div></div>';
        if (rep.leaked) h += '<div class="alert alert-error"><i class="bx bxs-key"></i> Escribiste tus datos en una página falsa. En la vida real, cambia tu contraseña de inmediato y avisa a TI.</div>';
        if (rep.money_lost) h += '<div class="alert alert-error"><i class="bx bxs-dollar-circle"></i> Pusiste en la orden de pago la cuenta que mandó el estafador. Ante un cambio de cuenta bancaria, siempre se confirma por teléfono.</div>';
        h += '<table class="report-table"><thead><tr><th>Mensaje</th><th>Era</th><th>Tu decisión</th><th>Lo ideal</th><th></th></tr></thead><tbody>' +
            rep.messages.map(function (m) {
                var icon = m.points >= 1 ? '<i class="bx bxs-check-circle"></i>' : m.points > 0 ? '<i class="bx bxs-error-circle"></i>' : '<i class="bx bxs-x-circle"></i>';
                return '<tr class="' + (m.points >= 1 ? 'good' : m.points > 0 ? 'mid' : 'bad') + '"><td><b>' + esc(m.from_name) + '</b><small>' + esc(m.subject) + '</small><p class="fbline">' + esc(m.feedback) + '</p></td>' +
                    '<td>' + (m.phishing ? '<span class="tag-bad"><i class="bx bx-anchor"></i> Engaño</span>' : '<span class="tag-good"><i class="bx bx-check"></i> Legítimo</span>') + '</td>' +
                    '<td>' + (m.action ? esc(name[m.action]) : '—') + '</td><td>' + esc(name[m.best]) + '</td><td>' + icon + '</td></tr>';
            }).join('') + '</tbody></table>';
        h += '<div class="report-docs">' + rep.docs.map(function (d) {
            return '<div class="rd"><b>' + PHUI.icon(d.icon) + ' ' + esc(d.title) + '</b>' + d.fields.map(function (f) {
                return '<div class="' + (f.ok ? 'ok' : 'bad') + '">' + (f.ok ? '<i class="bx bxs-check-circle"></i>' : '<i class="bx bxs-x-circle"></i>') + ' ' + esc(f.label) + ': <code>' + esc(f.value || '—') + '</code>' + (f.ok ? '' : ' <i class="bx bx-right-arrow-alt"></i> <code>' + esc(f.answer) + '</code>') + '</div>';
            }).join('') + '</div>';
        }).join('') + '</div></div>';
        return h;
    }

    function showResult(r) {
        var box = $('result');
        var mood = r.grand_prize ? 'fiesta' : r.passed ? 'feliz' : 'piensa';
        var msg = r.grand_prize ? '¡GANASTE EL GRAN PREMIO! Nadie pudo engañarte en la oficina.' :
            r.percent === 100 ? '¡Perfecto! Eres imparable.' :
            r.passed ? '¡Aprobaste! Tu instinto anti-phishing está cada vez más afilado.' :
            'Todavía no apruebas, pero ya sabes dónde estaban las trampas. ¡Inténtalo otra vez!';
        if (G.type === 'office' && !r.grand_prize && !r.grand_before && r.report) {
            msg += r.report.fell ? ' Para el gran premio necesitas no caer en ningún engaño.' : (r.percent < r.pass_percent ? ' Para el gran premio necesitas ' + r.pass_percent + ' % o más.' : '');
        }
        var stars = '';
        for (var i = 1; i <= 3; i++) stars += '<i class="bx bxs-star' + (i <= r.stars ? ' on' : '') + '" style="animation-delay:' + (i * 0.25) + 's"></i>';
        var summary = '';
        if (G.type === 'memory') summary = '<p class="muted small">Fallos: ' + r.summary.misses + ' (tenías ' + r.summary.free + ' de cortesía).</p>';
        box.innerHTML =
            (r.grand_prize ? '<div class="grand-banner"><i class="bx bxs-trophy"></i> GRAN PREMIO <i class="bx bxs-trophy"></i><small>+300 XP · +150 <i class="bx bxs-coin"></i> · avatar «Campeón de oficina», marco diamante y título «Guardián de la oficina»</small></div>' : '') +
            '<div class="res-stars">' + stars + '</div>' +
            mascot(mood, esc(msg), 'res-mascot') +
            '<div class="res-grid">' +
                '<div><b>' + r.percent + '%</b><span>puntaje</span></div>' +
                '<div><b>+' + r.xp + '</b><span>XP</span></div>' +
                '<div><b>+' + r.coins + '</b><span>monedas <i class="bx bxs-coin"></i></span></div>' +
            '</div>' + summary +
            (r.improved ? '' : '<p class="muted small">No superaste tu mejor puntaje: recibes XP de práctica (30 %).</p>') +
            (r.level_up ? '<div class="res-chip res-level">' + PHUI.icon(r.level_up.icon, 'bx-tada') + ' ¡Subiste al nivel ' + r.level_up.level + ': ' + esc(r.level_up.name) + '!</div>' : '') +
            (r.report ? officeReport(r.report) : '') +
            '<div class="res-actions">' +
                '<button class="btn btn-soft btn-lg" type="button" id="again-btn"><i class="bx bx-repeat"></i> Jugar otra vez</button>' +
                (r.grand_prize ? '<a class="btn btn-soft btn-lg" href="' + esc(PHUI.link('rewards.php')) + '"><i class="bx bxs-gift"></i> Equipar mis premios</a>' : '') +
                '<a class="btn btn-primary btn-lg" href="' + esc(G.backUrl) + '">Volver a la zona <i class="bx bx-right-arrow-alt"></i></a>' +
            '</div>';
        box.hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        $('again-btn').onclick = function () { box.hidden = true; $('intro').hidden = false; $('start-btn').disabled = false; progress(0, 1, ''); $('pp-text').textContent = ''; };
        if (r.user) {
            var c = document.querySelector('.chip-coins b'); if (c) c.textContent = r.user.coins;
            var s = document.querySelector('.chip-streak b'); if (s) s.textContent = r.user.streak;
        }
        var cel = [];
        if (r.grand_prize) cel.push({ kicker: '¡GRAN PREMIO!', icon: '', name: 'Empleado(a) del año', description: 'Completaste el turno sin caer en ningún engaño. Tus premios exclusivos ya están en la tienda.', color: '#5B6CFF', reward: '+300 XP · +150 ' });
        if (r.level_up) cel.push({ kicker: '¡Subiste de nivel!', icon: r.level_up.icon, name: 'Nivel ' + r.level_up.level + ': ' + r.level_up.name, description: 'Tu experiencia como cazador sigue creciendo.', color: '#FFC83D' });
        (r.badges || []).forEach(function (b) { cel.push({ kicker: '¡Nueva insignia!', icon: b.icon, name: b.name, description: b.description, color: b.color, reward: '+25 XP · +10 monedas' }); });
        (r.achievements || []).forEach(function (a) { cel.push({ kicker: '¡Logro desbloqueado!', icon: a.icon, name: a.name, description: a.description, color: '#2BB39A', reward: (a.xp ? '+' + a.xp + ' XP · ' : '') + '+' + a.coins + ' monedas' }); });
        if (cel.length) setTimeout(function () { PHUI.celebrate(cel); }, 800);
        else if (r.passed) PHUI.confetti();
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('start-btn').addEventListener('click', start);
        window.addEventListener('beforeunload', function (e) {
            if (S.attempt && !$('stage').hidden) { e.preventDefault(); e.returnValue = ''; }
        });
    });
})();
