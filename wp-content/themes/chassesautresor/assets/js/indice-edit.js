// ✅ indice-edit.js
var DEBUG = window.DEBUG || false;
DEBUG && console.log('✅ indice-edit.js chargé');

let boutonsToggle;
let panneauEdition;

function initIndiceEdit() {
  if (typeof initZonesClicEdition === 'function') initZonesClicEdition();

  boutonsToggle = document.querySelectorAll(
    '#toggle-mode-edition-indice, .toggle-mode-edition-indice'
  );
  panneauEdition = document.querySelector('.edition-panel-indice');

  const toggleEdition = () => {
    document.body.classList.toggle('edition-active-indice');
    document.body.classList.toggle('panneau-ouvert');
    document.body.classList.toggle('mode-edition');
  };

  boutonsToggle.forEach((btn) => {
    btn.addEventListener('click', toggleEdition);
  });

  panneauEdition?.querySelector('.panneau-fermer')?.addEventListener('click', () => {
    document.body.classList.remove('edition-active-indice', 'panneau-ouvert', 'mode-edition');
    document.activeElement?.blur();
  });

  const params = new URLSearchParams(window.location.search);
  const doitOuvrir = params.get('edition') === 'open';
  if (doitOuvrir && boutonsToggle.length > 0) {
    boutonsToggle[0].click();
    DEBUG && console.log('🔧 Ouverture auto du panneau édition indice via ?edition=open');
  }
}

document.addEventListener('DOMContentLoaded', initIndiceEdit);
