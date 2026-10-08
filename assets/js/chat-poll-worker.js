let heartbeatMs = 20000;
let threadMs = 5000;
let heartbeatTimer = null;
let threadTimer = null;
let chatId = null;
let cursor = 0;
let lastSeenUrl = null;
let pollBase = null;

self.onmessage = function (event) {
    const message = event.data;

    if (message.command === 'start') {
        lastSeenUrl = message.lastSeenUrl;
        pollBase = message.pollBase;
        heartbeatMs = message.heartbeatMs || heartbeatMs;
        threadMs = message.threadMs || threadMs;
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
        }
        heartbeat();
        heartbeatTimer = setInterval(heartbeat, heartbeatMs);
    }

    if (message.command === 'watch-thread') {
        chatId = message.chatId;
        cursor = message.after || 0;
        if (threadTimer) {
            clearInterval(threadTimer);
        }
        threadTimer = setInterval(pollThread, threadMs);
    }

    if (message.command === 'set-cursor') {
        cursor = message.after;
    }

    if (message.command === 'stop-thread') {
        if (threadTimer) {
            clearInterval(threadTimer);
            threadTimer = null;
        }
        chatId = null;
        cursor = 0;
    }
};

function requestOptions(extra) {
    return Object.assign({ credentials: 'same-origin', headers: { 'Accept': 'application/json' } }, extra || {});
}

function heartbeat() {
    if (!lastSeenUrl) {
        return;
    }
    fetch(lastSeenUrl, requestOptions({ method: 'POST' }))
        .then(function (response) {
            if (!response.ok) {
                throw new Error('http');
            }
            return response.json();
        })
        .then(function (payload) {
            self.postMessage({ type: 'heartbeat', unread: payload.data.unread, from: payload.data.latest_from });
        })
        .catch(function () {});
}

function pollThread() {
    if (!pollBase || !chatId) {
        return;
    }
    fetch(pollBase + chatId + '?after=' + cursor, requestOptions())
        .then(function (response) {
            if (!response.ok) {
                throw new Error('http');
            }
            return response.json();
        })
        .then(function (payload) {
            const data = payload.data;
            if (data.messages.length) {
                cursor = data.messages[data.messages.length - 1].id;
            }
            self.postMessage({ type: 'thread', data: data });
        })
        .catch(function () {});
}
