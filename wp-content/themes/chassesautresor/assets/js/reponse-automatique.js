function timeUntilMidnight() {
  const now = new Date();
  const midnight = new Date();
  midnight.setHours(24, 0, 0, 0);
  const diff = midnight - now;
  const h = Math.floor(diff / 3600000);
  const m = Math.floor((diff % 3600000) / 60000);
  return `${h}h et ${m}mn avant réactivation`;
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.formulaire-reponse-auto');
  if (!form) return;
  const feedback = document.querySelector('.reponse-feedback');
  const compteur = document.querySelector('.tentatives-counter');
  const compteurValeur = compteur ? compteur.querySelector('.valeur') : null;
  const input = form.querySelector('input[name="reponse"]');
  const limiteMsg = document.querySelector('.message-limite');
  const badgeCout = form.querySelector('.cout-points-badge');
  const soldeInfo = form.querySelector('.cout-points-solde');
  const headerPoints = document.querySelector('.zone-points .points-value');
  const cout = parseInt(form.dataset.cout || '0', 10);
  const SEUIL_CONFIRM_PTS = 200;
  const SEUIL_CONFIRM_PCT = 50;
  let hideTimer = null;

  form.addEventListener('submit', e => {
    e.preventDefault();
    const soldeActuel = parseInt(headerPoints ? headerPoints.textContent : form.dataset.solde || '0', 10);
    if (cout >= SEUIL_CONFIRM_PTS || (soldeActuel > 0 && (cout * 100) / soldeActuel > SEUIL_CONFIRM_PCT)) {
      const soldeApres = soldeActuel - cout;
      if (!window.confirm(`Confirmer l’envoi ? Cette tentative coûtera ${cout} pts. Solde après : ${soldeApres} pts.`)) {
        return;
      }
    }

    const data = new URLSearchParams(new FormData(form));
    data.append('action', 'soumettre_reponse_automatique');

    fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: data
    })
      .then(async r => {
        const text = await r.text();
        try {
          return JSON.parse(text);
        } catch (e) {
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
        if (hideTimer) {
          clearTimeout(hideTimer);
          hideTimer = null;
        }
        feedback.style.display = 'none';

        if (res.success) {
          form.reset();
          if (headerPoints && typeof res.data.points !== 'undefined') {
            headerPoints.textContent = res.data.points;
            form.dataset.solde = res.data.points;
          }

          if (soldeInfo && typeof res.data.points !== 'undefined') {
            const soldeApres = res.data.points - cout;
            soldeInfo.textContent = `Solde : ${res.data.points} → ${soldeApres} pts`;
          }

          if (res.data.resultat === 'variante') {
            if (res.data.message) {
              feedback.textContent = res.data.message;
              feedback.style.display = 'block';
            }
          } else if (res.data.resultat === 'bon') {
            feedback.textContent = 'Bonne réponse !';
            feedback.style.display = 'block';
            form.remove();
            if (badgeCout) badgeCout.remove();
            if (compteur) {
              compteur.remove();
            }
            const currentMenuItem = document.querySelector('.enigme-menu li.active');
            if (currentMenuItem) {
              currentMenuItem.classList.remove('non-engagee', 'bloquee', 'en-attente');
              currentMenuItem.classList.add('succes');
            }
          } else {
            feedback.textContent = 'Mauvaise réponse';
            feedback.style.display = 'block';
            hideTimer = setTimeout(() => { feedback.style.display = 'none'; }, 5000);
          }

          if (compteur && typeof res.data.compteur !== 'undefined') {
            const max = parseInt(compteur.dataset.max || '0', 10);
            if (compteurValeur) {
              compteurValeur.textContent = `${res.data.compteur}/${max}`;
            } else {
              compteur.textContent = `Tentatives quotidiennes ${res.data.compteur}/${max}`;
            }
            if (max && res.data.compteur >= max) {
              if (input) input.remove();
              if (limiteMsg) {
                limiteMsg.style.display = 'block';
              } else {
                const p = document.createElement('p');
                p.className = 'message-limite';
                p.dataset.tentatives = 'epuisees';
                p.textContent = 'tentatives quotidiennes épuisées';
                form.insertBefore(p, form.querySelector('input[name="enigme_id"]'));
              }
              const btn = form.querySelector('button[type="submit"]');
              if (btn) {
                btn.disabled = true;
                btn.textContent = timeUntilMidnight();
              }
            }
          }

          if (badgeCout && typeof res.data.points !== 'undefined' && cout > 0) {
            let txt = `-${cout} pts débités • Solde : ${res.data.points} pts`;
            if (compteur && typeof res.data.compteur !== 'undefined') {
              const max = parseInt(compteur.dataset.max || '0', 10);
              txt += ` • Tentatives : ${res.data.compteur}/${max}`;
            }
            feedback.textContent = feedback.textContent ? `${feedback.textContent} — ${txt}` : txt;
            feedback.style.display = 'block';
          }
        } else {
          feedback.textContent = res.data;
          feedback.style.display = 'block';
          hideTimer = setTimeout(() => { feedback.style.display = 'none'; }, 5000);
          if (res.data === 'tentatives_epuisees') {
            if (input) input.remove();
            if (limiteMsg) {
              limiteMsg.style.display = 'block';
            } else {
              const p = document.createElement('p');
              p.className = 'message-limite';
              p.dataset.tentatives = 'epuisees';
              p.textContent = 'tentatives quotidiennes épuisées';
              form.insertBefore(p, form.querySelector('input[name="enigme_id"]'));
            }
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
              btn.disabled = true;
              btn.textContent = timeUntilMidnight();
            }
          }
        }
      });
  });
});
