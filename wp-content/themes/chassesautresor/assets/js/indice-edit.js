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

  // ==============================
  // 🟢 Initialisation des champs
  // ==============================
  document.querySelectorAll('.champ-indice[data-champ]').forEach((bloc) => {
    if (bloc.classList.contains('champ-img')) {
      if (typeof initChampImage === 'function') initChampImage(bloc);
    } else {
      if (typeof initChampTexte === 'function') initChampTexte(bloc);
    }
  });

  initChampConditionnel('acf[indice_cible]', {
    chasse: [],
    enigme: ['#champ-indice-cible-enigmes']
  });
  initChampRadioAjax('acf[indice_cible]', 'indice');

  initChampConditionnel('acf[indice_disponibilite]', {
    immediate: [],
    differe: ['#champ-indice-date']
  });
  initChampRadioAjax('acf[indice_disponibilite]', 'indice');

  document
    .querySelectorAll('#champ-indice-cible-enigmes input[type="checkbox"]')
    .forEach((checkbox) => {
      checkbox.addEventListener('change', () => {
        const bloc = document.getElementById('champ-indice-cible-enigmes');
        const champ = bloc.dataset.champ;
        const postId = bloc.dataset.postId;
        const valeurs = [...bloc.querySelectorAll('input[type="checkbox"]')]
          .filter((el) => el.checked)
          .map((el) => el.value);
        modifierChampSimple(champ, valeurs, postId, 'indice');
      });
    });

  document
    .querySelectorAll('input[name="acf[indice_cible]"]')
    .forEach((radio) => {
      radio.addEventListener('change', () => {
        if (radio.value === 'chasse') {
          const bloc = document.getElementById('champ-indice-cible-enigmes');
          const chasseId = bloc?.dataset.chasseId;
          const postId = bloc?.dataset.postId;
          if (chasseId && postId) {
            modifierChampSimple('indice_cible_objet', [chasseId], postId, 'indice');
          }
        }
      });
    });

  // ==============================
  // 📜 Panneau description (wysiwyg)
  // ==============================
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.ouvrir-panneau-description');
    if (!btn || btn.dataset.cpt !== 'indice') return;
    if (typeof window.openPanel === 'function') {
      window.openPanel('panneau-description-indice');
    }
  });
  document
    .querySelector('#panneau-description-indice .panneau-fermer')
    ?.addEventListener('click', () => {
      if (typeof window.closePanel === 'function') {
        window.closePanel('panneau-description-indice');
      }
    });
}

document.addEventListener('DOMContentLoaded', initIndiceEdit);
