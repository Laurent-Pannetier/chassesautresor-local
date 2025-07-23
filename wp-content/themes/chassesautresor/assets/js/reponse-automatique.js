document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.formulaire-reponse-auto');
  if (!form) return;
  const feedback = document.querySelector('.reponse-feedback');

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
            setTimeout(() => { feedback.style.display = 'none'; }, 5000);
          }
          throw e;
        }
      })
      .then(res => {
        if (!feedback) return;
        feedback.style.display = 'none';

        if (res.success) {
          form.reset();

          if (res.data.resultat === 'variante') {
            if (res.data.message) {
              feedback.textContent = res.data.message;
              feedback.style.display = 'block';
            }
          } else if (res.data.resultat === 'bon') {
            feedback.textContent = 'Bonne réponse !';
            feedback.style.display = 'block';
            form.remove();
          } else {
            feedback.textContent = 'Mauvaise réponse';
            feedback.style.display = 'block';
            setTimeout(() => { feedback.style.display = 'none'; }, 5000);
          }
        } else {
          feedback.textContent = res.data;
          feedback.style.display = 'block';
          setTimeout(() => { feedback.style.display = 'none'; }, 5000);
        }
      });
  });
});
