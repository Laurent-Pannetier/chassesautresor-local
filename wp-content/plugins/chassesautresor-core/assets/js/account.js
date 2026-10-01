(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        const close = event.target.closest('.message-close[data-key]');
        if (close && typeof window.chassesAuTresorAccount !== 'undefined') {
            const data = new FormData();
            data.append('action', 'cta_dismiss_message');
            data.append('key', close.dataset.key);
            fetch(window.chassesAuTresorAccount.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            }).then(function (response) {
                return response.json();
            }).then(function (response) {
                if (response.success) {
                    close.closest('p').remove();
                }
            });
            return;
        }
        const link = event.target.closest('[data-account-section]');
        if (!link || typeof window.chassesAuTresorAccount === 'undefined') {
            return;
        }
        const target = document.getElementById('cat-core-account-section');
        if (!target) {
            return;
        }

        event.preventDefault();
        target.textContent = window.chassesAuTresorAccount.loading;
        const url = new URL(window.chassesAuTresorAccount.ajaxUrl, window.location.origin);
        url.searchParams.set('action', 'cta_load_admin_section');
        url.searchParams.set('section', link.dataset.accountSection);
        url.searchParams.set('nonce', window.chassesAuTresorAccount.nonce);
        fetch(url.toString(), { credentials: 'same-origin' })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                if (!response.success) {
                    throw new Error('account_section');
                }
                target.innerHTML = response.data.html;
                window.history.replaceState({}, '', link.href);
            })
            .catch(function () {
                target.textContent = window.chassesAuTresorAccount.error;
            });
    });
}());
