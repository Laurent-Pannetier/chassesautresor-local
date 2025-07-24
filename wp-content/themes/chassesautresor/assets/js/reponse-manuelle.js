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
          form.reset();
          if (headerPoints && typeof res.data.points !== 'undefined') {
            headerPoints.textContent = res.data.points;
          }
          if (res.data.points < cout) {
            if (input) input.remove();
            if (pointsMsg) {
              pointsMsg.style.display = 'block';
              pointsMsg.textContent = `${cout - res.data.points} points manquants`;
            } else {
              const p = document.createElement('p');
              p.className = 'message-limite';
              p.dataset.points = 'manquants';
              p.textContent = `${cout - res.data.points} points manquants`;
              form.insertBefore(p, form.querySelector('input[name="enigme_id"]'));
            }
            const btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
          }
          feedback.textContent = 'Réponse envoyée !';
          feedback.style.display = 'block';
          hideTimer = setTimeout(() => { feedback.style.display = 'none'; }, 5000);
        } else {
          feedback.textContent = res.data;
          feedback.style.display = 'block';
          hideTimer = setTimeout(() => { feedback.style.display = 'none'; }, 5000);
        }
      });
  });
}

document.addEventListener('DOMContentLoaded', initFormulaireManuel);
