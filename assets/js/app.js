/* PhishHunter · utilidades comunes de interfaz */
(function () {
    'use strict';

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /* Icono de Boxicons: icon('bxs-trophy', 'bx-tada') */
    function icon(name, extra) {
        name = String(name || '');
        if (!/^bx[sl]?-[a-z0-9-]+( bx-[a-z0-9-]+)*$/.test(name)) return name ? '<span class="legacy-icon">' + esc(name) + '</span>' : '';
        return '<i class="bx ' + name + (extra ? ' ' + esc(extra) : '') + '" aria-hidden="true"></i>';
    }

    /* Dirección de un script de la app (usa la URL amigable si el servidor la admite) */
    function link(path) {
        return (window.PH.urls && window.PH.urls[path]) || (window.PH.base + '/' + path);
    }

    function api(path, data) {
        return fetch(link(path), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.PH.csrf },
            body: JSON.stringify(data || {})
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false, error: 'Respuesta inválida del servidor' }; });
        });
    }

    /* Medidor de fuerza de contraseña (solo en el navegador, no se envía) */
    function strength(pw) {
        var score = 0, tips = [];
        if (!pw) return { score: 0, label: 'Escribe una contraseña de práctica', tips: [] };
        var len = pw.length;
        score += Math.min(50, len * 3.2);
        var kinds = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^A-Za-z0-9]/].filter(function (r) { return r.test(pw); }).length;
        score += kinds * 7;
        var words = pw.split(/[\s\-_.]+/).filter(function (w) { return w.length >= 3; }).length;
        if (words >= 3) score += 12;
        var common = ['123456', 'password', 'qwerty', 'contraseña', 'abc123', '111111', 'amauta', 'peru', 'iloveyou', 'admin'];
        if (common.some(function (c) { return pw.toLowerCase().indexOf(c) !== -1; })) { score -= 35; tips.push('Evita palabras o secuencias muy comunes.'); }
        if (/(.)\1{2,}/.test(pw)) { score -= 10; tips.push('Evita repetir el mismo carácter.'); }
        if (/(19|20)\d{2}/.test(pw)) { score -= 8; tips.push('Los años (como tu cumpleaños) son fáciles de adivinar.'); }
        if (len < 12) tips.push('Hazla más larga: 12 caracteres o más.');
        if (kinds < 3) tips.push('Combina mayúsculas, minúsculas, números o signos.');
        if (words < 3 && len < 16) tips.push('Prueba una frase de 3 o 4 palabras sin relación.');
        score = Math.max(0, Math.min(100, Math.round(score)));
        var label = score < 35 ? 'Muy débil ' : score < 55 ? 'Débil ' : score < 75 ? 'Aceptable ' : score < 90 ? 'Fuerte ' : 'Muy fuerte ';
        return { score: score, label: label, tips: tips };
    }

    function meter(pw, barEl, textEl) {
        var s = strength(pw);
        var span = barEl.querySelector('span');
        span.style.width = s.score + '%';
        barEl.dataset.level = s.score < 35 ? '0' : s.score < 55 ? '1' : s.score < 75 ? '2' : '3';
        if (textEl) textEl.textContent = pw ? s.label + (s.tips[0] ? ' · ' + s.tips[0] : '') : 'Tip: usa una frase de 3 o 4 palabras sin relación.';
        return s;
    }

    /* Confeti ligero en canvas */
    function confetti() {
        var c = document.createElement('canvas');
        c.className = 'confetti';
        document.body.appendChild(c);
        var ctx = c.getContext('2d');
        c.width = window.innerWidth; c.height = window.innerHeight;
        var colors = ['#8BC34A', '#FFC83D', '#FF7A59', '#2BB39A', '#E0457B', '#C6E86B'];
        var parts = [];
        for (var i = 0; i < 140; i++) {
            parts.push({ x: Math.random() * c.width, y: -20 - Math.random() * c.height * 0.5, r: 4 + Math.random() * 6,
                vx: -2 + Math.random() * 4, vy: 2 + Math.random() * 4, a: Math.random() * 6, va: -0.2 + Math.random() * 0.4,
                color: colors[i % colors.length] });
        }
        var t0 = performance.now();
        (function frame(t) {
            ctx.clearRect(0, 0, c.width, c.height);
            parts.forEach(function (p) {
                p.x += p.vx; p.y += p.vy; p.a += p.va; p.vy += 0.03;
                ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.a);
                ctx.fillStyle = p.color; ctx.fillRect(-p.r / 2, -p.r / 2, p.r, p.r * 0.6);
                ctx.restore();
            });
            if (t - t0 < 2600) requestAnimationFrame(frame); else c.remove();
        })(t0);
    }

    /* Ventana de celebración (insignias, logros, nivel) */
    function celebrate(list) {
        if (!list || !list.length) return;
        var modal = document.getElementById('celebrate');
        var i = 0;
        function show() {
            var c = list[i];
            modal.innerHTML =
                '<div class="modal-box pop">' +
                '<div class="modal-kicker">' + esc(c.kicker || '¡Nuevo logro!') + '</div>' +
                '<div class="modal-icon" style="--bc:' + esc(c.color || '#8BC34A') + '">' + icon(c.icon || 'bxs-medal', 'bx-tada') + '</div>' +
                '<h2>' + esc(c.name) + '</h2>' +
                '<p>' + esc(c.description || '') + '</p>' +
                (c.reward ? '<div class="modal-reward">' + esc(c.reward) + '</div>' : '') +
                '<button class="btn btn-primary" type="button">' + (i < list.length - 1 ? 'Siguiente' : '¡Genial!') + '</button>' +
                '</div>';
            modal.hidden = false;
            confetti();
            modal.querySelector('button').onclick = function () {
                i++;
                if (i < list.length) show(); else { modal.hidden = true; modal.innerHTML = ''; }
            };
        }
        show();
    }

    function toast(msg, type, ico) {
        var t = document.createElement('div');
        t.className = 'toast toast-' + (type || 'info');
        t.textContent = msg;
        if (ico) t.insertAdjacentHTML('afterbegin', icon(ico) + ' ');
        var n = document.querySelectorAll('.toast:not(.out)').length;
        if (n) t.style.bottom = (24 + n * 54) + 'px';
        document.body.appendChild(t);
        setTimeout(function () { t.classList.add('out'); }, 2600);
        setTimeout(function () { t.remove(); }, 3100);
    }

    /* Carrusel de tarjetas de aprendizaje */
    function lessons() {
        var box = document.getElementById('lesson');
        if (!box) return;
        var cards = box.querySelectorAll('.lesson-card');
        var dots = box.querySelectorAll('.dots i');
        var pos = document.getElementById('lesson-pos');
        var idx = 0;
        function go(n) {
            idx = Math.max(0, Math.min(cards.length - 1, n));
            cards.forEach(function (c, k) { c.classList.toggle('active', k === idx); });
            dots.forEach(function (d, k) { d.classList.toggle('on', k <= idx); });
            if (pos) pos.textContent = idx + 1;
        }
        box.querySelectorAll('[data-lesson]').forEach(function (b) {
            b.addEventListener('click', function () { go(idx + parseInt(b.dataset.lesson, 10)); });
        });
    }

    /* Chispa, la guía flotante */
    function guide() {
        var g = document.getElementById('chispa-guide');
        if (!g) return;
        var tips = [];
        try { tips = JSON.parse(g.dataset.tips || '[]'); } catch (e) { return; }
        var bubble = document.getElementById('cg-bubble'), text = document.getElementById('cg-text');
        var i = 0, key = 'ph-chispa-' + location.pathname;
        function show(n) { i = (n + tips.length) % tips.length; text.innerHTML = tips[i]; bubble.hidden = false; }
        var seen = null;
        try { seen = sessionStorage.getItem(key); } catch (e) { /* sin almacenamiento */ }
        if (!seen) setTimeout(function () { show(0); }, 900);
        document.getElementById('cg-btn').addEventListener('click', function () { if (bubble.hidden) show(i + (text.innerHTML ? 1 : 0)); else bubble.hidden = true; });
        document.getElementById('cg-next').addEventListener('click', function () { show(i + 1); });
        document.getElementById('cg-close').addEventListener('click', function () {
            bubble.hidden = true;
            try { sessionStorage.setItem(key, '1'); } catch (e) { /* sin almacenamiento */ }
        });
    }

    window.PHUI = { esc: esc, api: api, meter: meter, strength: strength, confetti: confetti, celebrate: celebrate, toast: toast, icon: icon, link: link };

    document.addEventListener('DOMContentLoaded', function () {
        lessons();
        guide();
        // Botones «Copiar» (data-copy = id del campo)
        document.querySelectorAll('[data-copy]').forEach(function (b) {
            b.addEventListener('click', function () {
                var el = document.getElementById(b.dataset.copy);
                if (!el) return;
                var done = function () { toast('Copiado al portapapeles', 'success', 'bx-check'); };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(el.value || el.textContent).then(done);
                } else {
                    el.select(); try { document.execCommand('copy'); done(); } catch (e) { /* sin permiso */ }
                }
            });
        });
        if (window.PH_CELEBRATIONS && window.PH_CELEBRATIONS.length) celebrate(window.PH_CELEBRATIONS);
        document.addEventListener('click', function (e) {
            var menu = document.querySelector('.usermenu[open]');
            if (menu && !menu.contains(e.target)) menu.removeAttribute('open');
        });
    });
})();
