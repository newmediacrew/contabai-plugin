(function () {
    const cfg = window.contabaiChatConfig || {};
    if (!window.Worker || !cfg.worker) {
        return;
    }

    let worker;
    try {
        worker = new Worker(cfg.worker);
    } catch (e) {
        return;
    }
    window.chatWorker = worker;
    let previousUnread = null;

    function unlock() { if (window.ChatSound) { window.ChatSound.unlock(); } }
    document.addEventListener('click', unlock);
    document.addEventListener('keydown', unlock);

    worker.addEventListener('message', function (e) {
        const msg = e.data;

        if (msg.type === 'heartbeat') {
            const badges = document.querySelectorAll('.js-chat-unread');
            badges.forEach(function (badge) {
                badge.textContent = msg.unread;
                badge.style.display = msg.unread === 0 ? 'none' : 'inline-flex';
            });

            if (previousUnread !== null && msg.unread > previousUnread) {
                const label = msg.from
                    ? (cfg.newMessageFrom || 'New message from %s').replace('%s', function () { return msg.from; })
                    : (cfg.newMessage || 'New message');
                if (typeof contabaiToast === 'function') {
                    contabaiToast(label, 'success');
                }
                if (window.ChatSound) {
                    window.ChatSound.play();
                }
            }
            previousUnread = msg.unread;
        }

        if (msg.type === 'thread') {
            window.dispatchEvent(new CustomEvent('chat-poll', { detail: msg.data }));
        }
    });

    worker.postMessage({
        command: 'start',
        lastSeenUrl: cfg.lastSeenUrl,
        pollBase: cfg.pollBase,
        heartbeatMs: cfg.heartbeatMs || 20000,
        threadMs: cfg.threadMs || 5000,
    });
})();
