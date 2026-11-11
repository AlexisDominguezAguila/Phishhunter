/* PhishHunter · Videoteca: reproductor de YouTube bajo demanda + pregunta de repaso */
(function () {
    'use strict';

    var esc = PHUI.esc;
    var apiReady = null;

    function loadYT() {
        if (apiReady) return apiReady;
        apiReady = new Promise(function (resolve, reject) {
            if (window.YT && window.YT.Player) { resolve(); return; }
            window.onYouTubeIframeAPIReady = function () { resolve(); };
            var s = document.createElement('script');
            s.src = 'https://www.youtube.com/iframe_api';
            s.onerror = reject;
            document.head.appendChild(s);
            setTimeout(function () { if (!(window.YT && window.YT.Player)) reject(); }, 8000);
        });
        return apiReady;
    }

    function markWatched(card) {
        if (card.classList.contains('watched')) return;
        card.classList.add('watched');
        PHUI.api('api/arcade.php', { action: 'video_watched', video: parseInt(card.dataset.video, 10) }).then(function (r) {
            if (!r.ok) return;
            var mark = card.querySelector('.vc-mark'); if (mark) mark.hidden = true;
            card.querySelector('.vc-quiz').hidden = false;
            if (!card.classList.contains('solved')) card.querySelector('.vc-state').innerHTML = PHUI.icon('bxs-show') + ' Visto';
            if (r.new) PHUI.toast('¡+' + r.xp + ' XP por ver el video! Ahora responde la pregunta.', 'success', 'bxs-movie-play');
            card.querySelector('.vc-quiz').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    function play(card) {
        var frame = card.querySelector('.yt-frame');
        var id = card.dataset.yt;
        var holder = document.createElement('div');
        holder.id = 'yt-' + card.dataset.video + '-' + Date.now();
        frame.innerHTML = '';
        frame.appendChild(holder);
        // «Ya lo vi» se habilita después de 30 s, por si el video termina fuera de la vista.
        var mark = card.querySelector('.vc-mark');
        if (mark && !card.classList.contains('watched')) {
            setTimeout(function () { mark.disabled = false; }, 30000);
            mark.onclick = function () { markWatched(card); };
        }
        loadYT().then(function () {
            new YT.Player(holder.id, {
                videoId: id, host: 'https://www.youtube-nocookie.com',
                playerVars: { autoplay: 1, rel: 0, modestbranding: 1, playsinline: 1 },
                events: { onStateChange: function (e) { if (e.data === YT.PlayerState.ENDED) markWatched(card); } }
            });
        }).catch(function () {
            frame.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id) + '?autoplay=1&rel=0" title="Video" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
        });
    }

    function bindQuiz(card) {
        card.querySelectorAll('.vc-quiz .option').forEach(function (b) {
            b.addEventListener('click', function () {
                var opts = card.querySelectorAll('.vc-quiz .option');
                opts.forEach(function (x) { x.disabled = true; });
                var i = parseInt(b.dataset.i, 10);
                PHUI.api('api/arcade.php', { action: 'video_answer', video: parseInt(card.dataset.video, 10), answer: i }).then(function (r) {
                    var fb = card.querySelector('.vc-fb');
                    if (!r.ok) { PHUI.toast(r.error || 'Error', 'error'); opts.forEach(function (x) { x.disabled = false; }); return; }
                    if (r.correct) {
                        b.classList.add('right');
                        card.classList.add('solved');
                        card.querySelector('.vc-state').innerHTML = PHUI.icon('bxs-check-circle') + ' Completado';
                        fb.innerHTML = '<p class="fb-inline ok"><i class="bx bxs-party"></i> ¡Correcto! ' + (r.xp ? '+' + r.xp + ' XP. ' : '') + esc(r.explanation) + '</p>';
                        var cel = [];
                        (r.badges || []).forEach(function (x) { cel.push({ kicker: '¡Nueva insignia!', icon: x.icon, name: x.name, description: x.description, color: x.color, reward: '+25 XP · +10 monedas' }); });
                        (r.achievements || []).forEach(function (a) { cel.push({ kicker: '¡Logro desbloqueado!', icon: a.icon, name: a.name, description: a.description, color: '#2BB39A' }); });
                        if (cel.length) PHUI.celebrate(cel); else PHUI.confetti();
                    } else {
                        b.classList.add('wrong');
                        fb.innerHTML = '<p class="fb-inline bad"><i class="bx bx-search-alt"></i> No es esa. ' + esc(r.explanation) + '</p>';
                        setTimeout(function () { opts.forEach(function (x) { if (!x.classList.contains('wrong')) x.disabled = false; }); }, 900);
                    }
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.video-card-item').forEach(function (card) {
            var f = card.querySelector('.yt-facade');
            if (f) f.addEventListener('click', function () { play(card); });
            bindQuiz(card);
        });
    });
})();
