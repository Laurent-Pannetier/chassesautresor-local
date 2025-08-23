// Gestion de la modale d'indice

document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.querySelector('.indice-modal-overlay');
  if (!overlay) return;

  const dateInput = overlay.querySelector('#indice_date_disponibilite');
  const stateSelect = overlay.querySelector('[name="indice_cache_etat_systeme"]');
  const validateBtn = overlay.querySelector('.indice-valider');
  const radiosDisponibilite = overlay.querySelectorAll('input[name="indice_disponibilite"]');
  const dateWrapper = dateInput ? dateInput.closest('.champ-date-disponibilite') : null;

  // Pré-remplissage si vide
  if (dateInput && !dateInput.value) {
    const now = new Date();
    dateInput.value = now.toISOString().slice(0,16);
  }

  // Empêcher la suppression du champ date & heure
  if (dateInput) {
    let previous = dateInput.value;
    dateInput.addEventListener('input', () => {
      if (!dateInput.value) {
        dateInput.value = previous;
      } else {
        previous = dateInput.value;
      }
    });
  }

  // Gestion de la disponibilité immédiate
  function updateDisponibilite() {
    const checked = overlay.querySelector('input[name="indice_disponibilite"]:checked');
    if (!checked) return;
    if (checked.value === 'immediate') {
      if (dateWrapper) {
        dateWrapper.style.display = 'none';
      }
    } else {
      if (dateWrapper) {
        dateWrapper.style.display = '';
      }
    }
  }
  radiosDisponibilite.forEach(r => r.addEventListener('change', updateDisponibilite));
  updateDisponibilite();

  // Activation / désactivation du bouton valider
  function updateValidate() {
    if (!validateBtn || !stateSelect) return;
    const v = stateSelect.value;
    validateBtn.disabled = (v === 'invalide' || v === 'desactive');
  }
  stateSelect?.addEventListener('change', updateValidate);
  updateValidate();
});

