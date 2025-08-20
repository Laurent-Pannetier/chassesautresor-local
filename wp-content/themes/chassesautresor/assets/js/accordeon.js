document.querySelectorAll('.accordeon-bloc').forEach(bloc => {
  const toggle = bloc.querySelector('.accordeon-toggle');
  const contenu = bloc.querySelector('.accordeon-contenu');

  if (!toggle || !contenu) return;

  // Synchronise l’état initial
  const estOuvert = toggle.getAttribute('aria-expanded') === 'true';
  contenu.classList.toggle('accordeon-ferme', !estOuvert);

  toggle.addEventListener('click', () => {
    const estActuellementOuvert = toggle.getAttribute('aria-expanded') === 'true';

    // Ferme tous les autres blocs
    document.querySelectorAll('.accordeon-bloc').forEach(otherBloc => {
      const otherToggle = otherBloc.querySelector('.accordeon-toggle');
      const otherContenu = otherBloc.querySelector('.accordeon-contenu');

      if (!otherToggle || !otherContenu) return;

      otherToggle.setAttribute('aria-expanded', 'false');
      otherContenu.classList.add('accordeon-ferme');
    });

    // Ouvre uniquement si ce n’était pas déjà ouvert
    if (!estActuellementOuvert) {
      toggle.setAttribute('aria-expanded', 'true');
      contenu.classList.remove('accordeon-ferme');
    }
  });

  toggle.addEventListener('mouseenter', () => {
    toggle.classList.add('is-hovered');
  });

  toggle.addEventListener('mouseleave', () => {
    toggle.classList.remove('is-hovered');
  });
});

function openHelpModal(title, message) {
  const overlay = document.createElement('div');
  overlay.className = 'help-modal-overlay';
  overlay.innerHTML = `
    <div class="help-modal" role="dialog" aria-modal="true">
      <button type="button" class="help-modal-close" aria-label="${wp.i18n.__('Fermer', 'chassesautresor-com')}">&times;</button>
      <h2 class="help-modal-title">${title}</h2>
      <div class="help-modal-content"></div>
    </div>`;
  document.body.appendChild(overlay);

  const content = overlay.querySelector('.help-modal-content');
  message.split('\n').forEach((line) => {
    const p = document.createElement('p');
    p.textContent = line;
    content.appendChild(p);
  });

  const close = () => overlay.remove();
  overlay.querySelector('.help-modal-close').addEventListener('click', close);
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) close();
  });
}

document.querySelectorAll('.stat-help').forEach((btn) => {
  btn.addEventListener('click', () => {
    const message = btn.dataset.message;
    const title = btn.dataset.title || '';
    if (message) {
      openHelpModal(title, message);
    }
  });
});
