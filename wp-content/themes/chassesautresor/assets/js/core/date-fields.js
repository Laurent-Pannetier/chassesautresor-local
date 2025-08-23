document.addEventListener('DOMContentLoaded', () => {
  // On cible de manière plus large les champs de date pour prendre en charge
  // les inputs générés dynamiquement ou ceux dont le type peut varier (text,
  // date, datetime-local...). L'important est qu'ils possèdent la classe
  // `.champ-date-edit`.
  document.querySelectorAll('input.champ-date-edit').forEach(initChampDate);
});




// ==============================
// 📅 Formatage des dates Y-m-d ➔ d/m/Y
// ==============================
function formatDateFr(dateStr) {
  console.log('[formatDateFr] input=', dateStr);
  if (!dateStr) return '';
  if (dateStr.includes('T')) {
    const [datePart] = dateStr.split('T');
    const parts = datePart.split('-');
    if (parts.length !== 3) return dateStr;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
  }
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  return `${parts[2]}/${parts[1]}/${parts[0]}`;
}



// ==============================
// 📅 Mise à jour affichage Date Fin
// ==============================
function mettreAJourAffichageDateFin() {
  console.log('[mettreAJourAffichageDateFin]');
  const spanDateFin = document.querySelector('.chasse-date-plage .date-fin');
  const inputDateFin = document.getElementById('chasse-date-fin');
  const checkboxIllimitee = document.getElementById('duree-illimitee');

  if (!spanDateFin || !inputDateFin || !checkboxIllimitee) return;

  if (checkboxIllimitee.checked) {
    spanDateFin.textContent = 'Illimitée';
  } else {
    spanDateFin.textContent = formatDateFr(inputDateFin.value);
  }
}
// ==============================
// 📅 initChampDate
// ==============================
function initChampDate(input) {
  console.log('⏱️ Attachement initChampDate à', input, '→ ID:', input.id);

  if (input.disabled) {
    return;
  }

  const bloc = input.closest('[data-champ]');
  const champ = bloc?.dataset.champ;
  const postId = bloc?.dataset.postId;
  const cpt = bloc?.dataset.cpt || 'chasse';

  if (!champ || !postId) return;

  let status = bloc?.querySelector('.champ-status');
  if (!status) {
    status = document.createElement('span');
    status.className = 'champ-status';
    input.insertAdjacentElement('afterend', status);
  }

  // 🕒 Pré-remplissage si vide
  if (!input.value && bloc.dataset.date) {
    const dateInit = bloc.dataset.date;
    if (/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/.test(dateInit)) {
      input.value = dateInit;
    }
  }

  let saving = false;

  const enregistrer = () => {
    if (saving) return;
    saving = true;
    const valeurBrute = input.value.trim();
    console.log('[🧪 initChampDate]', champ, '| valeur saisie :', valeurBrute, '| previous :', input.dataset.previous);
    const regexDate = /^\d{4}-\d{2}-\d{2}$/;
    const regexDateTime = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/;
    if (!regexDate.test(valeurBrute) && !regexDateTime.test(valeurBrute)) {
      console.warn(`❌ Date invalide (${champ}) :`, valeurBrute);
      input.value = input.dataset.previous || '';
      return;
    }

    let valeur = valeurBrute;
    if (regexDateTime.test(valeurBrute) && input.type === 'datetime-local') {
      valeur = valeurBrute.replace('T', ' ') + ':00';
    }

    if (cpt === 'chasse' && typeof window.validerDatesAvantEnvoi === 'function') {
      let type = '';
      if (champ.endsWith('_date_debut')) type = 'debut';
      if (champ.endsWith('_date_fin')) type = 'fin';
      if (type && !window.validerDatesAvantEnvoi(type)) {
        input.value = input.dataset.previous || '';
        return;
      }
    }

    if (status) {
      status.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>';
    }

    const afterSave = success => {
      saving = false;
      if (success) {
        if (status) {
          status.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
          setTimeout(() => { status.innerHTML = ''; }, 1000);
        }
        input.dataset.previous = valeurBrute;
        if (typeof window.onDateFieldUpdated === 'function') {
          window.onDateFieldUpdated(input, valeurBrute);
        }
      } else {
        if (status) status.innerHTML = '';
        input.value = input.dataset.previous || '';
      }
    };

    if (
      cpt === 'chasse' &&
      typeof window.enregistrerDatesChasse === 'function' &&
      (champ.endsWith('_date_debut') || champ.endsWith('_date_fin'))
    ) {
      console.log('[initChampDate] appel enregistrerDatesChasse pour', champ);
      window.enregistrerDatesChasse().then(afterSave);
    } else {
      modifierChampSimple(champ, valeur, postId, cpt).then(afterSave);
    }
  };

  input.addEventListener('change', enregistrer);

  // Certains navigateurs ne déclenchent pas toujours l'évènement "change" après
  // sélection dans le datepicker. On ajoute donc un fallback sur "blur" si la
  // valeur a effectivement été modifiée.
  input.addEventListener('blur', () => {
    if (saving) return;
    if (input.value.trim() !== (input.dataset.previous || '')) {
      enregistrer();
    }
  });

  if (typeof window.onDateFieldUpdated === 'function') {
    const valeurInit = input.value?.trim() || ''; // 🔹 protection + fallback vide
    window.onDateFieldUpdated(input, valeurInit);
  }
  input.dataset.previous = input.value?.trim() || '';

}
