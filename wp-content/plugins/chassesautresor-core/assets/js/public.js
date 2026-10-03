(function () {
    'use strict';

    function request(data) {
        return fetch(window.chassesAuTresorCore.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).then(function (response) {
            return response.json();
        });
    }

    function feedbackFor(form) {
        const container = form.closest('.participation') || form.parentElement;
        return container.querySelector('.cat-core-feedback');
    }

    document.addEventListener('submit', function (event) {
        const form = event.target.closest(
            '.formulaire-reponse-manuelle, .formulaire-reponse-automatique'
        );
        if (!form || typeof window.chassesAuTresorCore === 'undefined') {
            return;
        }

        event.preventDefault();
        const feedback = feedbackFor(form);
        const data = new FormData(form);
        const automatic = form.classList.contains('formulaire-reponse-automatique');
        data.append('action', automatic ? 'soumettre_reponse_automatique' : 'soumettre_reponse_manuelle');

        request(data).then(function (response) {
            if (automatic && response.success && response.data.message) {
                feedback.textContent = response.data.message;
            } else {
                feedback.textContent = response.success
                    ? window.chassesAuTresorCore.successMessage
                    : window.chassesAuTresorCore.errorMessage;
            }
            if (response.success) {
                form.reset();
            }
        }).catch(function () {
            feedback.textContent = window.chassesAuTresorCore.errorMessage;
        });
    });

    function showHint(link, display) {
        const data = new FormData();
        data.append('action', 'debloquer_indice');
        data.append('indice_id', link.dataset.indiceId);
        data.append('nonce', window.chassesAuTresorCore.hintNonce);
        request(data).then(function (response) {
            if (!response.success) {
                display.textContent = window.chassesAuTresorCore.errorMessage;
                return;
            }
            display.innerHTML = '<button type="button" class="indice-close">'
                + window.chassesAuTresorCore.close + '</button>' + response.data.html;
            link.dataset.unlocked = '1';
            link.classList.remove('indice-link--locked');
            link.classList.add('indice-link--unlocked');
        }).catch(function () {
            display.textContent = window.chassesAuTresorCore.errorMessage;
        });
    }

    document.addEventListener('click', function (event) {
        if (typeof window.chassesAuTresorCore === 'undefined') {
            return;
        }
        const close = event.target.closest('.indice-close');
        if (close) {
            close.parentElement.replaceChildren();
            return;
        }
        const confirm = event.target.closest('.cat-core-unlock-hint');
        if (confirm) {
            const zone = confirm.closest('.zone-indices');
            const link = zone.querySelector('.indice-link[data-indice-id="' + confirm.dataset.indiceId + '"]');
            showHint(link, zone.querySelector('.indice-display'));
            return;
        }
        const link = event.target.closest('.indice-link');
        if (!link) {
            return;
        }

        event.preventDefault();
        const zone = link.closest('.zone-indices');
        const display = zone.querySelector('.indice-display');
        if (link.dataset.unlocked === '1' || link.dataset.cout === '0') {
            showHint(link, display);
            return;
        }
        display.replaceChildren();
        const message = document.createElement('p');
        message.textContent = window.chassesAuTresorCore.unlockHint + ' — ' + link.dataset.cout + ' pts';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'cat-core-unlock-hint';
        button.dataset.indiceId = link.dataset.indiceId;
        button.textContent = window.chassesAuTresorCore.unlockHint;
        display.append(message, button);
    });
}());
