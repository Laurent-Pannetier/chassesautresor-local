function initIndiceEdit() {
  if (typeof initZonesClicEdition === 'function') initZonesClicEdition();

  const boutonsToggle = document.querySelectorAll(
    '#toggle-mode-edition-indice, .toggle-mode-edition-indice'
  );
  const panneauEdition = document.querySelector('.edition-panel-indice');

  const toggleEdition = () => {
    document.body.classList.toggle('edition-active-indice');
    document.body.classList.toggle('panneau-ouvert');
    document.body.classList.toggle('mode-edition');
  };

  boutonsToggle.forEach((btn) => {
    btn.addEventListener('click', toggleEdition);
  });

  panneauEdition?.querySelector('.panneau-fermer')?.addEventListener('click', () => {
    document.body.classList.remove('edition-active-indice');
    document.body.classList.remove('panneau-ouvert');
    document.body.classList.remove('mode-edition');
    document.activeElement?.blur();
  });

  const params = new URLSearchParams(window.location.search);
  if (params.get('edition') === 'open' && boutonsToggle.length > 0) {
    boutonsToggle[0].click();
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initIndiceEdit);
} else {
  initIndiceEdit();
}
