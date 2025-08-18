function initFormulaireManuel() {
  const form = document.querySelector('.formulaire-reponse-manuelle');
  if (!form) return;
  const feedback = form.nextElementSibling;
  const input = form.querySelector('textarea[name="reponse_manuelle"]');
  const pointsMsg = form.querySelector('.message-limite');
  const badgeCout = form.querySelector('.badge-cout');
  const headerPoints = document.querySelector('.zone-points .points-value');
  const cout = badgeCout ? parseInt(badgeCout.textContent, 10) : 0;
  let hideTimer = null;

  const i18n = window.REPONSE_MANUELLE_I18N || {};
  const txtSuccess = i18n.success || 'Tentative bien reçue.';
  const txtProcessing = i18n.processing || '⏳ Votre tentative est en cours de traitement.';

  form.addEventListener('submit', e => {
    e.preventDefault();
    const data = new URLSearchParams(new FormData(form));
    data.append('action', 'soumettre_reponse_manuelle');

    fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: data
    })
      .then(async r => {
        const text = await r.text();
        try { return JSON.parse(text); } catch (e) {
          if (feedback) {
            feedback.textContent = 'Erreur serveur';
            feedback.style.display = 'block';
            hideTimer = setTimeout(() => { feedback.style.display = 'none'; }, 5000);
          }
          throw e;
        }
      })
      .then(res => {
        if (!feedback) return;
        if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
        feedback.style.display = 'none';

        if (res.success) {
          if (headerPoints && typeof res.data.points !== 'undefined') {
            headerPoints.textContent = res.data.points;
          }

          const msgProcessing = document.createElement('p');
          msgProcessing.className = 'message-joueur-statut';
          msgProcessing.textContent = txtProcessing;

          const msgSuccess = document.createElement('p');
          msgSuccess.className = 'message-feedback-success';
          msgSuccess.textContent = txtSuccess;

          if (feedback) feedback.remove();
          const parent = form.parentNode;
          parent.insertBefore(msgSuccess, form);
          parent.insertBefore(msgProcessing, form);
          form.remove();

          setTimeout(() => { msgSuccess.remove(); }, 5000);
        } else {
          feedback.textContent = res.data;
          feedback.style.display = 'block';
          hideTimer = setTimeout(() => { feedback.style.display = 'none'; }, 5000);
        }
      });
  });
}

document.addEventListener('DOMContentLoaded', initFormulaireManuel);
