(function () {
    let ctx = null;

    function unlock() {
        if (ctx === null) {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (Ctx) {
                ctx = new Ctx();
            }
        }
        if (ctx && ctx.state === 'suspended') {
            ctx.resume();
        }
    }

    function play() {
        if (!ctx || ctx.state !== 'running') {
            return;
        }
        const now = ctx.currentTime;
        const master = ctx.createGain();
        master.gain.value = 0.85;
        master.connect(ctx.destination);

        function syllable(fStart, fEnd, at, dur, peak) {
            const start = now + at;
            [[1, peak], [2, peak * 0.3]].forEach(function (harmonic) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(fStart * harmonic[0], start);
                osc.frequency.exponentialRampToValueAtTime(fEnd * harmonic[0], start + dur);
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(harmonic[1], start + 0.03);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + dur);
                osc.connect(gain);
                gain.connect(master);
                osc.start(start);
                osc.stop(start + dur + 0.03);
            });
        }

        syllable(650, 610, 0.0, 0.30, 0.22);
        syllable(510, 470, 0.34, 0.50, 0.24);
    }

    window.ChatSound = { unlock: unlock, play: play };
})();
