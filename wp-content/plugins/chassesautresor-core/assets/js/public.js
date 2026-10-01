(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('.formulaire-reponse-manuelle');
        if (!form || typeof window.chassesAuTresorCore === 'undefined') {
            return;
        }

        event.preventDefault();
        const feedback = form.parentElement.querySelector('.cat-core-feedback');
        const data = new FormData(form);
        data.append('action', 'soumettre_reponse_manuelle');

        fetch(window.chassesAuTresorCore.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).then(function (response) {
            return response.json();
        }).then(function (response) {
            feedback.textContent = response.success
                ? window.chassesAuTresorCore.successMessage
                : window.chassesAuTresorCore.errorMessage;
            if (response.success) {
                form.reset();
            }
        }).catch(function () {
            feedback.textContent = window.chassesAuTresorCore.errorMessage;
        });
    });
}());
