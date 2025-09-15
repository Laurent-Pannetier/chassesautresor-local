// ✅ champ-init.js bien chargé
var DEBUG = window.DEBUG || false;
DEBUG && console.log('✅ champ-init.js bien chargé');



// ================================
// 🛠️ Envoi AJAX d'un champ simple (texte, number, boolean)
// ================================
function modifierChampSimple(champ, valeur, postId, cpt = 'enigme') {
  DEBUG && console.log('📤 modifierChampSimple()', { champ, valeur, postId, cpt }); // ⬅️ test

  const action = (cpt === 'enigme') ? 'modifier_champ_enigme' :
    (cpt === 'organisateur') ? 'modifier_champ_organisateur' :
      (cpt === 'indice') ? 'modifier_champ_indice' :
        'modifier_champ_chasse';

  return fetch(ajaxurl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      action,
      champ,
      valeur,
      post_id: postId
    })
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        DEBUG && console.log(`✅ Champ ${champ} enregistré`);
        if (typeof window.onChampSimpleMisAJour === 'function') {
          window.onChampSimpleMisAJour(champ, postId, valeur, cpt, res.data);
        }
        return true; // important : pour pouvoir chaîner dans le .then(...)
      } else {
        console.warn(`⚠️ Échec enregistrement champ ${champ} :`, res?.data);
        return false;
      }
    })
    .catch(err => {
      console.error('❌ Erreur réseau AJAX :', err);
      return false;
    });
}




/// ==============================
// 📝 initChampTexte
// ==============================
function initChampTexte(bloc) {
  if (bloc.classList.contains('champ-desactive')) {
    return; // champ non éditable
  }
  const champ = bloc.dataset.champ;
  const cpt = bloc.dataset.cpt;
  const postId = bloc.dataset.postId;
  const input = bloc.querySelector('.champ-input');
  const boutonEdit = bloc.querySelector('.champ-modifier');
  const boutonSave = bloc.querySelector('.champ-enregistrer');
  const boutonCancel = bloc.querySelector('.champ-annuler');
  const affichage = bloc.querySelector('.champ-affichage') || bloc;
  const edition = bloc.querySelector('.champ-edition');
  const isEditionDirecte = bloc.dataset.direct === 'true';

  const action = (cpt === 'chasse') ? 'modifier_champ_chasse'
    : (cpt === 'enigme') ? 'modifier_champ_enigme'
      : (cpt === 'indice') ? 'modifier_champ_indice'
        : 'modifier_champ_organisateur';

  if (!champ || !cpt || !postId || !input) return;

  let feedback = bloc.querySelector('.champ-feedback');
  if (!feedback) {
    feedback = document.createElement('div');
    feedback.className = 'champ-feedback';
    bloc.appendChild(feedback);
  }

  let status = bloc.querySelector('.champ-status');
  if (!status && input) {
    status = document.createElement('span');
    status.className = 'champ-status';
    input.insertAdjacentElement('afterend', status);
  }

  // ✍️ Édition directe : aucun bouton d'édition/sauvegarde
  if (!boutonSave && !boutonEdit) {
    let timer;
    input.addEventListener('input', () => {
      clearTimeout(timer);
      const brute = input.value.trim();

      timer = setTimeout(() => {
        if (champ === 'email_contact') {
          const isValide = brute === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(brute);
          if (!isValide) {
            feedback.textContent = '⛔ Adresse email invalide';
            feedback.className = 'champ-feedback champ-error';
            return;
          }
        }

        if (champ === 'post_title' && !brute) {
          feedback.textContent = '❌ Le titre est obligatoire.';
          feedback.className = 'champ-feedback champ-error';
          return;
        }

        if (champ === 'enigme_visuel_legende') {
          if (typeof window.mettreAJourLegendeEnigme === 'function') {
            window.mettreAJourLegendeEnigme(brute);
          }
        }

        if (status) {
          status.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>';
        }
        feedback.textContent = '';
        feedback.className = 'champ-feedback';

        let valeurEnvoyee = brute;
        let estVide = !brute;

        if (champ === 'enigme_reponse_bonne') {
          const parts = brute.split(',').map(v => v.trim()).filter(v => v);
          valeurEnvoyee = JSON.stringify(parts);
          estVide = parts.length === 0;
        }

        modifierChampSimple(champ, valeurEnvoyee, postId, cpt).then(success => {
          if (success) {
            bloc.classList.toggle('champ-vide', estVide);
            if (status) {
              status.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
              setTimeout(() => { status.innerHTML = ''; }, 1000);
            }
            feedback.textContent = '';
            feedback.className = 'champ-feedback';
            if (typeof window.mettreAJourResumeInfos === 'function') {
              window.mettreAJourResumeInfos();
            }
          } else {
            if (status) status.innerHTML = '';
            feedback.textContent = 'Erreur lors de l’enregistrement.';
            feedback.className = 'champ-feedback champ-error';
          }
        });
      }, 400);
    });
    return;
  }

  // ✏️ Ouverture édition
  boutonEdit?.addEventListener('click', () => {
    if (affichage?.style) affichage.style.display = 'none';
    if (edition?.style) edition.style.display = 'flex';
    input.focus();

    feedback.textContent = '';
    feedback.className = 'champ-feedback';

    if (champ === 'email_contact') {
      const fallback = window.organisateurData?.defaultEmail || '…';
      const affichageTexte = affichage.querySelector('.champ-valeur');
      if (affichageTexte && input.value.trim() === '') {
        affichageTexte.innerHTML = '<em>' + fallback + '</em>';
      }
    }
  });

  // ❌ Annulation
  boutonCancel?.addEventListener('click', () => {
    if (edition?.style) edition.style.display = 'none';
    if (affichage?.style) affichage.style.display = '';
    feedback.textContent = '';
    feedback.className = 'champ-feedback';
  });

  // ✅ Sauvegarde
  boutonSave?.addEventListener('click', () => {
    const valeur = input.value.trim();
    if (!champ || !postId) return;

    if (champ === 'email_contact') {
      const isValide = valeur === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valeur);
      if (!isValide) {
        feedback.textContent = '⛔ Adresse email invalide';
        feedback.className = 'champ-feedback champ-error';
        return;
      }
    }

    if (champ === 'enigme_visuel_legende') {
      if (typeof window.mettreAJourLegendeEnigme === 'function') {
        window.mettreAJourLegendeEnigme(valeur);
      }
    }

    if (champ === 'post_title') {
      if (!valeur) {
        feedback.textContent = '❌ Le titre est obligatoire.';
        feedback.className = 'champ-feedback champ-error';
        return;
      }
    }

    if (status) {
      status.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>';
    }
    feedback.textContent = '';
    feedback.className = 'champ-feedback';

    modifierChampSimple(champ, valeur, postId, cpt).then(success => {
      if (success) {
        const affichageTexte = affichage.querySelector('.champ-valeur, h1, h2, p, span:not(.champ-obligatoire)');

        if (champ === 'email_contact') {
          const fallbackEmail = window.organisateurData?.defaultEmail || '…';
          const spanValeur = affichage.querySelector('.champ-valeur');
          if (spanValeur) {
            spanValeur.innerHTML = valeur ? valeur : '<em>' + fallbackEmail + '</em>';
          }
        } else if (affichageTexte) {
          affichageTexte.textContent = valeur;
        }

        if (edition?.style) edition.style.display = 'none';
        if (affichage?.style) affichage.style.display = '';
        bloc.classList.toggle('champ-vide', !valeur);

        if (status) {
          status.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
          setTimeout(() => { status.innerHTML = ''; }, 1000);
        }
        feedback.textContent = '';
        feedback.className = 'champ-feedback';

        if (typeof window.mettreAJourResumeInfos === 'function') {
          window.mettreAJourResumeInfos();
        }
      } else {
        if (status) status.innerHTML = '';
        feedback.textContent = 'Erreur lors de l’enregistrement.';
        feedback.className = 'champ-feedback champ-error';
      }
    });
  });

}



// ==============================
// initChampDeclencheur (déclenche ouverture + init JS au clic sur ✏️ résumé)
// ==============================
function initChampDeclencheur(bouton) {
  const champ = bouton.dataset.champ;
  const postId = bouton.dataset.postId;
  const cpt = bouton.dataset.cpt || 'organisateur';

  if (!champ || !postId || !cpt) return;

  bouton.addEventListener('click', () => {
    const bloc = bouton.closest(
      `.champ-${cpt}[data-champ="${champ}"][data-post-id="${postId}"]`
    );

    if (!bloc) return;

    // 🛡️ Sécurité : ignorer si c'est un résumé
    if (bloc.classList.contains('resume-ligne')) return;

    // ✅ Initialiser l’image dynamiquement si besoin
    if (bloc.classList.contains('champ-img') && typeof initChampImage === 'function') {
      initChampImage(bloc);
    }
    // ✅ Cas particulier : clic sur le stylo image
    if (
      bloc.classList.contains('champ-img') &&
      typeof bloc.__ouvrirMedia === 'function' &&
      !bouton.classList.contains('ouvrir-panneau-images')
    ) {
      const estVide = bloc.classList.contains('champ-vide');
      if (estVide) {
        bloc.__ouvrirMedia();
        return; // rien d’autre à faire si aucune illustration
      }
    }


    // 🎯 Simuler clic sur vrai bouton si présent
    const vraiBouton = [...bloc.querySelectorAll('.champ-modifier')].find(b => b !== bouton);
    if (vraiBouton) vraiBouton.click();
  });
}


// ================================
// 💰 Affichage conditionnel des boutons d'édition coût
// ================================
function initAffichageBoutonsCout() {
  document.querySelectorAll('.champ-cout-points .champ-input').forEach(input => {
    const container = input.closest('.champ-enigme');
    if (!container) return; // 🔐 sécurise
    const boutons = container.querySelector('.champ-inline-actions');
    if (!boutons) return;

    // Sauvegarde la valeur initiale
    input.dataset.valeurInitiale = input.value.trim();

    /// Avant d'ajouter les événements
    boutons.style.transition = 'none';
    boutons.style.opacity = '0';
    boutons.style.visibility = 'hidden';

    // Ensuite (petit timeout pour réactiver les transitions après masquage immédiat)
    setTimeout(() => {
      boutons.style.transition = 'opacity 0.3s ease, visibility 0.3s ease';
    }, 50);

    input.addEventListener('input', () => {
      let val = input.value.trim();
      if (val === '') val = '0'; // Vide = 0

      const initiale = input.dataset.valeurInitiale;
      if (val !== initiale) {
        boutons.style.opacity = '1';
        boutons.style.visibility = 'visible';
      } else {
        boutons.style.opacity = '0';
        boutons.style.visibility = 'hidden';
      }
    });

    input.addEventListener('blur', () => {
      if (input.value.trim() === '') {
        input.value = '0';
      }
    });
  });
}
initAffichageBoutonsCout();


// ================================
// 💰 Initialisation affichage coût en points (Gratuit / Payant) — multi-CPT
// ================================
function initChampCoutPoints() {
  document.querySelectorAll('.champ-cout-points').forEach(bloc => {
    if (bloc.classList.contains('champ-desactive')) {
      return; // champ verrouillé
    }
    const input = bloc.querySelector('.champ-input.champ-cout[type="number"]');
    const checkbox = bloc.querySelector('input[type="checkbox"]');
    if (!input || !checkbox) return;

    const postId = bloc.dataset.postId;
    const champ = bloc.dataset.champ;
    const cpt = bloc.dataset.cpt;
    if (!postId || !champ || !cpt) return;

    let timerDebounce;
    let ancienneValeur = input.value.trim();

    const enregistrerCout = () => {
      clearTimeout(timerDebounce);
      timerDebounce = setTimeout(() => {
        let valeur = parseInt(input.value.trim(), 10);
        if (isNaN(valeur) || valeur < 0) valeur = 0;
        input.value = valeur;
        modifierChampSimple(champ, valeur, postId, cpt);

        if (typeof window.onCoutPointsUpdated === 'function') {
          window.onCoutPointsUpdated(bloc, champ, valeur, postId, cpt);
        }
      }, 500);
    };

    // ✅ état initial : disable si gratuit
    const valeurInitiale = parseInt(input.value.trim(), 10);
    if (valeurInitiale === 0) {
      input.disabled = true;
      checkbox.checked = false;
    } else {
      input.disabled = false;
      checkbox.checked = true;
    }

    input.addEventListener('input', enregistrerCout);

    checkbox.addEventListener('change', () => {
      if (checkbox.checked) {
        const valeur = parseInt(ancienneValeur, 10);
        input.value = valeur > 0 ? valeur : 10;
        input.disabled = false;
      } else {
        ancienneValeur = input.value.trim();
        input.value = 0;
        input.disabled = true;
      }
      enregistrerCout();
    });
  });
}
document.addEventListener('DOMContentLoaded', initChampCoutPoints);


/**
 * Initialisation d’un champ conditionnel basé sur un groupe de radios.
 * 
 * @param {string} nomChamp       → nom de l’attribut name (ex. "acf[enigme_acces_condition]")
 * @param {Object} correspondance → mapping { valeurRadio: [selectors à afficher] }
 */
function initChampConditionnel(nomChamp, correspondance) {
  const radios = document.querySelectorAll(`input[name="${nomChamp}"]`);
  if (!radios.length) {
    console.warn('❌ Aucun input trouvé pour', nomChamp);
    return;
  }

  function toutMasquer() {
    const tousSelectors = new Set(Object.values(correspondance).flat());
    tousSelectors.forEach(sel => {
      document.querySelectorAll(sel).forEach(el => el.classList.add('cache'));
    });
  }

  function mettreAJourAffichageCondition() {
    const valeur = [...radios].find(r => r.checked)?.value;
    DEBUG && console.log(`🔁 ${nomChamp} → valeur sélectionnée :`, valeur);

    toutMasquer();

    const selectorsAAfficher = correspondance[valeur];
    if (selectorsAAfficher) {
      DEBUG && console.log(`✅ Affiche :`, selectorsAAfficher);
      selectorsAAfficher.forEach(sel => {
        document.querySelectorAll(sel).forEach(el => el.classList.remove('cache'));
      });
    } else {
      console.warn('⚠️ Aucune correspondance prévue pour :', valeur);
    }
  }

  radios.forEach(r =>
    r.addEventListener('change', () => {
      DEBUG && console.log('🖱️ Changement détecté →', r.value);
      mettreAJourAffichageCondition();
    })
  );

  mettreAJourAffichageCondition();
}


// ==============================
// 📩 Enregistrement dynamique – Champs radio simples
// ==============================
function initChampRadioAjax(nomChamp, cpt = 'enigme') {
  const radios = document.querySelectorAll(`input[name="${nomChamp}"]`);
  if (!radios.length) return;

  radios.forEach(radio => {
    radio.addEventListener('change', function () {
      const bloc = radio.closest('[data-champ]');
      const champ = bloc?.dataset.champ;
      const postId = bloc?.dataset.postId;

      if (!champ || !postId) return;

      modifierChampSimple(champ, this.value, postId, cpt);
    });
  });
}


// ==============================
// 🔘 Initialisation des cases à cocher true_false
// ==============================
function initChampBooleen(bloc) {
  const champ = bloc.dataset.champ;
  const cpt = bloc.dataset.cpt;
  const postId = bloc.dataset.postId;
  const checkbox = bloc.querySelector('input[type="checkbox"]');
  if (!champ || !cpt || !postId || !checkbox) return;

  checkbox.addEventListener('change', () => {
    const valeur = checkbox.checked ? 1 : 0;
    modifierChampSimple(champ, valeur, postId, cpt);
  });
}

// ==============================
// 👆 Zone de clic étendue sur l'affichage des champs
// ==============================

function initZoneClicEdition(bouton) {
  const zone = bouton.closest('li') || bouton.closest('[data-champ]');
  if (!zone) return;


  zone.style.cursor = 'pointer';

  zone.addEventListener('click', (e) => {

    if (
      e.target.closest('.champ-modifier') ||
      e.target.closest('.champ-annuler') ||
      e.target.closest('.champ-enregistrer') ||
      e.target.closest('.icone-info') ||
      e.target.closest('input, select, textarea, a')
    ) {
      return;
    }

    bouton.click();
  });
}

function initZonesClicEdition() {
  document.querySelectorAll('.champ-modifier').forEach(initZoneClicEdition);

}
window.initZonesClicEdition = initZonesClicEdition;
