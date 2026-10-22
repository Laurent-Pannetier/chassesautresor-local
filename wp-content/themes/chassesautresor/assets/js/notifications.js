(function () {
    'use strict';

    var config = window.catNotifications || {};

    function request(action, data) {
        var body = new URLSearchParams();
        body.set('action', action);
        body.set('nonce', config.nonce || '');
        Object.keys(data || {}).forEach(function (key) {
            body.set(key, String(data[key]));
        });

        return window.fetch(config.ajaxUrl || '', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString()
        }).then(function (response) {
            return response.json();
        });
    }

    function updateUnreadBadge(count) {
        document.querySelectorAll('.app-nav__badge').forEach(function (badge) {
            if (count <= 0) {
                badge.remove();
                return;
            }
            badge.textContent = count > 99 ? '99+' : String(count);
        });
    }

    function bindCenter() {
        document.querySelectorAll('[data-notification-read]').forEach(function (button) {
            button.addEventListener('click', function () {
                var item = button.closest('[data-notification-id]');
                var id = item ? item.getAttribute('data-notification-id') : '';
                request('cat_notification_mark_read', {notification_id: id}).then(function (payload) {
                    if (!payload.success || !item) {
                        return;
                    }
                    item.classList.remove('is-unread');
                    button.remove();
                    updateUnreadBadge(Number(payload.data.unread || 0));
                });
            });
        });

        var readAll = document.querySelector('[data-notifications-read-all]');
        if (!readAll) {
            return;
        }
        readAll.addEventListener('click', function () {
            request('cat_notification_mark_read', {all: 1}).then(function (payload) {
                if (!payload.success) {
                    return;
                }
                document.querySelectorAll('.notifications-list__item').forEach(function (item) {
                    item.classList.remove('is-unread');
                });
                document.querySelectorAll('[data-notification-read]').forEach(function (button) {
                    button.remove();
                });
                readAll.remove();
                updateUnreadBadge(0);
            });
        });
    }

    function acknowledge(id) {
        return request('cat_notification_overlay_seen', {notification_id: id});
    }

    function showOverlay(notification) {
        var seenRequest = acknowledge(notification.id);
        var overlay = document.createElement('div');
        overlay.className = 'notification-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'notification-overlay-title');

        var card = document.createElement('div');
        card.className = 'notification-overlay__card';
        if (notification.sourceType === 'trophy') {
            card.classList.add('notification-overlay__card--trophy');
        }

        if (notification.imageUrl) {
            var image = document.createElement('img');
            image.className = 'notification-overlay__image';
            image.src = notification.imageUrl;
            image.alt = '';
            card.appendChild(image);
        }

        var eyebrow = document.createElement('p');
        eyebrow.className = 'notification-overlay__eyebrow';
        eyebrow.textContent = notification.sourceType === 'trophy'
            ? (config.labels.trophy || '')
            : (config.labels.notification || '');
        card.appendChild(eyebrow);

        var title = document.createElement('h2');
        title.id = 'notification-overlay-title';
        title.textContent = notification.title || '';
        card.appendChild(title);

        if (notification.body) {
            var body = document.createElement('p');
            body.className = 'notification-overlay__body';
            body.textContent = notification.body;
            card.appendChild(body);
        }

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'bouton-principal';
        close.textContent = config.labels.close || '';
        close.addEventListener('click', function () {
            overlay.remove();
            seenRequest.finally(loadNextOverlay);
        });
        card.appendChild(close);
        overlay.appendChild(card);
        document.body.appendChild(overlay);
        close.focus();
    }

    function loadNextOverlay() {
        request('cat_notification_overlay_next', {}).then(function (payload) {
            if (!payload.success || !payload.data.notification) {
                return;
            }
            showOverlay(payload.data.notification);
        });
    }

    if (!config.ajaxUrl || !config.nonce) {
        return;
    }
    bindCenter();
    loadNextOverlay();
})();
