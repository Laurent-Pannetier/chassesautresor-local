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
      .then(r => r.json())
      .then(res => {
        if (feedback) feedback.style.display = 'none';
        if (res.success) {
          if (res.data.resultat === 'variante' && res.data.message) {
            if (feedback) {
              feedback.textContent = res.data.message;
              feedback.style.display = 'block';
            }
            form.reset();
          } else {
            location.reload();
          }
        } else {
          if (feedback) {
            feedback.textContent = res.data;
            feedback.style.display = 'block';
          }
        }
      });
  });
});
