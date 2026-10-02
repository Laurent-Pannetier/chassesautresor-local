document.addEventListener('submit', async event => {
  const form = event.target.closest('.riddle-step-click-form, .riddle-step-text-form');
  if (!form || typeof RiddleStepPlayer === 'undefined') return;
  event.preventDefault();
  const button = form.querySelector('button[type="submit"]');
  const feedback = form.querySelector('.riddle-step-click-form__feedback');
  const data = new FormData(form);
  data.append('action', form.classList.contains('riddle-step-text-form')
    ? 'soumettre_reponse_etape'
    : 'confirmer_etape_enigme');
  button.disabled = true;
  try {
    const response = await fetch(RiddleStepPlayer.ajaxUrl, { method: 'POST', body: data });
    const result = await response.json();
    if (!result.success) throw new Error(result.data?.message || RiddleStepPlayer.error);
    if (result.data.resultat && result.data.resultat !== 'bon') {
      feedback.textContent = RiddleStepPlayer.wrong;
      const counter = document.querySelector('.tentatives-counter .valeur');
      const footer = document.querySelector('.participation-infos .tentatives');
      if (counter) counter.textContent = result.data.compteur;
      if (footer) {
        const maximum = footer.dataset.max || footer.textContent.split('/')[1]?.trim() || '∞';
        footer.dataset.max = maximum;
        footer.textContent = `${RiddleStepPlayer.attemptsLabel} ${result.data.compteur}/${maximum}`;
      }
      const maximum = Number.parseInt(form.dataset.maxFailures || '0', 10);
      if (maximum > 0 && result.data.compteur >= maximum) {
        form.querySelector('input[name="reponse"]')?.setAttribute('disabled', 'disabled');
        button.disabled = true;
        feedback.textContent = RiddleStepPlayer.limitReached;
        return;
      }
      button.disabled = false;
      return;
    }
    window.location.reload();
  } catch (error) {
    feedback.textContent = error.message;
    button.disabled = false;
  }
});
