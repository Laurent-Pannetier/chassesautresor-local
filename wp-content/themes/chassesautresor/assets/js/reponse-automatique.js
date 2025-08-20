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
  const badgeCout = form.querySelector('.badge-cout');
  const headerPoints = document.querySelector('.zone-points .points-value');
  let hideTimer = null;

  form.addEventListener('submit', e => {
    e.preventDefault();
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
            const sectionGagnants = document.querySelector('.enigme-gagnants');
            const enigmeIdInput = form.querySelector('input[name="enigme_id"]');
            if (sectionGagnants && enigmeIdInput) {
              const dataW = new URLSearchParams();
              dataW.append('action', 'enigme_recuperer_gagnants');
              dataW.append('enigme_id', enigmeIdInput.value);
              fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: dataW
              })
                .then(r => r.json())
                .then(r => {
                  if (r.success) {
                    sectionGagnants.innerHTML = r.data.html;
                    const bloc = document.querySelector('.menu-lateral__accordeons .accordeon-bloc');
                    const toggle = bloc ? bloc.querySelector('.accordeon-toggle') : null;
                    const contenu = bloc ? bloc.querySelector('.accordeon-contenu') : null;
                    if (toggle && contenu) {
                      toggle.setAttribute('aria-expanded', 'true');
                      contenu.classList.remove('accordeon-ferme');
                    }
                  }
                });
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
