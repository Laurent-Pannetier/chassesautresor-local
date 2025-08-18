function initCssCompilerCard() {
    const btn = document.getElementById('toggle-css-compiler');
    if (!btn || typeof compilCssCard === 'undefined') {
        return;
    }

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        btn.disabled = true;
        fetch(compilCssCard.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: `action=cta_toggle_css_compiler&nonce=${encodeURIComponent(compilCssCard.nonce)}`,
        })
            .then((resp) => resp.json())
            .then((data) => {
                if (data.success) {
                    btn.textContent = data.data.active
                        ? compilCssCard.text_deactivate
                        : compilCssCard.text_activate;
                } else {
                    alert('Erreur : ' + data.data);
                }
            })
            .catch(() => {
                alert('Erreur AJAX');
            })
            .finally(() => {
                btn.disabled = false;
            });
    });
}

document.addEventListener('DOMContentLoaded', initCssCompilerCard);
document.addEventListener('myaccountSectionLoaded', (e) => {
    if (e.detail.section === 'outils') {
        initCssCompilerCard();
    }
});
