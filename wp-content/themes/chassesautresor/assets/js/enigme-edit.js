// ✅ enigme-edit.js
var DEBUG = window.DEBUG || false;
DEBUG && console.log('✅ enigme-edit.js chargé');

let boutonToggle;
let panneauEdition;



function initEnigmeEdit() {
  if (typeof initZonesClicEdition === 'function') initZonesClicEdition();
  boutonToggle = document.getElementById('toggle-mode-edition-enigme');
  panneauEdition = document.querySelector('.edition-panel-enigme');

  // ==============================
  // 🛠️ Contrôles panneau principal
  // ==============================
  boutonToggle?.addEventListener('click', () => {
    document.body.classList.toggle('edition-active-enigme');
    document.body.classList.toggle('panneau-ouvert');
    document.body.classList.toggle('mode-edition');
  });


  panneauEdition?.querySelector('.panneau-fermer')?.addEventListener('click', () => {
    document.body.classList.remove('edition-active-enigme');
    document.body.classList.remove('panneau-ouvert');
    document.body.classList.remove('mode-edition');
    document.activeElement?.blur();
  });


  // ==============================
  // 🧭 Déclencheur automatique
  // ==============================
  const params = new URLSearchParams(window.location.search);
  const doitOuvrir = params.get('edition') === 'open';
  const tab = params.get('tab');
  if (doitOuvrir && boutonToggle) {
    boutonToggle.click();
    if (tab) {
      const btn = panneauEdition?.querySelector(`.edition-tab[data-target="enigme-tab-${tab}"]`);
      btn?.click();
    }
    DEBUG && console.log('🔧 Ouverture auto du panneau édition énigme via ?edition=open');
  }


  // ==============================
  // 🟢 Initialisation des champs
  // ==============================
  document.querySelectorAll('.champ-enigme[data-champ]').forEach((bloc) => {
    const champ = bloc.dataset.champ;

    if (bloc.classList.contains('champ-img') && champ !== 'enigme_visuel_image') {
      if (typeof initChampImage === 'function') initChampImage(bloc);
    } else {
      if (typeof initChampTexte === 'function') initChampTexte(bloc);
    }
  });

  document.querySelectorAll('.stat-help').forEach((btn) => {
    btn.addEventListener('click', () => {
      const message = btn.dataset.message;
      if (message) {
        alert(message);
      }
    });
  });


  // ==============================
  // 🧩 Affichage conditionnel – Champs radio
  // ==============================
  initChampConditionnel('acf[enigme_mode_validation]', {
    'aucune': [],
    'manuelle': ['.champ-cout-points', '.champ-nb-tentatives'],
    'automatique': ['.champ-groupe-reponse-automatique', '.champ-cout-points', '.champ-nb-tentatives']
  });

  // ==============================
  // 📨 Onglet Tentatives – affichage selon mode de validation
  // ==============================
  const radiosValidation = document.querySelectorAll('input[name="acf[enigme_mode_validation]"]');
  const tabTentatives = panneauEdition?.querySelector('.edition-tab[data-target="enigme-tab-soumission"]');
  const contenuTentatives = document.getElementById('enigme-tab-soumission');

  function toggleTentativesTab(mode) {
    const afficher = mode !== 'aucune';
    if (tabTentatives) {
      tabTentatives.style.display = afficher ? '' : 'none';
      if (!afficher && tabTentatives.classList.contains('active')) {
        panneauEdition?.querySelector('.edition-tab[data-target="enigme-tab-param"]')?.click();
      }
    }
    if (!afficher && contenuTentatives) {
      contenuTentatives.style.display = 'none';
      contenuTentatives.classList.remove('active');
    }
  }

  const radioChecked = document.querySelector('input[name="acf[enigme_mode_validation]"]:checked');
  const modeInitial = radioChecked ? radioChecked.value : 'aucune';
  toggleTentativesTab(modeInitial);

  radiosValidation.forEach((radio) => {
    radio.addEventListener('change', (e) => {
      toggleTentativesTab(e.target.value);
    });
  });


  // ==============================
  // 🧠 Explication – Mode de validation de l’énigme
  // ==============================
  const explicationValidation = {
    manuelle: wp.i18n.__(
      "Validation manuelle : Le joueur rédige une réponse libre. Vous validez ou invalidez manuellement " +
        "sa tentative depuis votre espace personnel. Un email et un message d'alerte vous avertit de chaque nouvelle soumission.",
      "chassesautresor-com"
    ),
    automatique: wp.i18n.__(
      "Validation automatique : Le joueur devra saisir une réponse exacte. Celle-ci sera automatiquement vérifiée " +
        "selon les critères définis (réponse attendue, casse, variantes).",
      "chassesautresor-com"
    ),
  };

  document.querySelectorAll('.validation-aide').forEach((btn) => {
    btn.addEventListener('click', () => {
      const mode = btn.dataset.mode;
      const message = explicationValidation[mode];
      if (message) {
        alert(message);
      }
    });
  });

  const explicationTentatives = wp.i18n.__(
    "Nombre maximum de tentatives quotidiennes d'un joueur\nMode payant : tentatives illimitées.\nMode gratuit : maximum 24 tentatives par jour.",
    "chassesautresor-com"
  );

  document.querySelectorAll('.tentatives-aide').forEach((btn) => {
    btn.addEventListener('click', () => {
      alert(explicationTentatives);
    });
  });

  const explicationVariantes = wp.i18n.__(
    "Les variantes sont des réponses alternatives qui ne sont pas considérées comme bonnes, mais affichent un message en retour " +
      "(libre à vous d'y mettre de l'aide, un lien, un crypto ou ce que vous voulez)",
    "chassesautresor-com"
  );

  document.querySelectorAll('.variantes-aide').forEach((btn) => {
    btn.addEventListener('click', () => {
      alert(explicationVariantes);
    });
  });


  // ==============================
  // 🧰 Déclencheurs de résumé
  // ==============================
  document.querySelectorAll('.edition-panel-enigme .champ-modifier[data-champ]').forEach((btn) => {
    if (typeof initChampDeclencheur === 'function') initChampDeclencheur(btn);
  });


  // ==============================
  // 📜 Panneau description (wysiwyg)
  // ==============================
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.ouvrir-panneau-description');
    if (!btn || btn.dataset.cpt !== 'enigme') return;

    if (typeof window.openPanel === 'function') {
      window.openPanel('panneau-description-enigme');
    }
  });
  document.querySelector('#panneau-description-enigme .panneau-fermer')?.addEventListener('click', () => {
    if (typeof window.closePanel === 'function') {
      window.closePanel('panneau-description-enigme');
    }
  });


  // ==============================
  // 🧪 Panneau variantes (réponses alternatives)
  // ==============================
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.ouvrir-panneau-variantes');
    if (!btn || btn.dataset.cpt !== 'enigme') return;

    const panneau = document.getElementById('panneau-variantes-enigme');
    if (!panneau) return;

    document.querySelectorAll('.panneau-lateral.ouvert, .panneau-lateral-liens.ouvert').forEach((p) => {
      p.classList.remove('ouvert');
      p.setAttribute('aria-hidden', 'true');
    });

    panneau.classList.add('ouvert');
    document.body.classList.add('panneau-ouvert');
    panneau.setAttribute('aria-hidden', 'false');
  });
  document.querySelector('#panneau-variantes-enigme .panneau-fermer')?.addEventListener('click', () => {
    const panneau = document.getElementById('panneau-variantes-enigme');
    panneau.classList.remove('ouvert');
    document.body.classList.remove('panneau-ouvert');
    panneau.setAttribute('aria-hidden', 'true');
  });


  // ==============================
  // 💰 Affichage dynamique tentatives (message coût)
  // ==============================
  const blocCout = document.querySelector('[data-champ="enigme_tentative.enigme_tentative_cout_points"]');
  if (blocCout && typeof window.onCoutPointsUpdated === 'function') {
    const champ = blocCout.dataset.champ;
    const valeur = parseInt(blocCout.querySelector('.champ-input')?.value || '0', 10);
    const postId = blocCout.dataset.postId;
    const cpt = blocCout.dataset.cpt;

    window.onCoutPointsUpdated(blocCout, champ, valeur, postId, cpt);
  }


  // ==============================
  // 🔐 Champ bonne réponse – Limite 75 caractères + message d’alerte
  // ==============================
  const bloc = document.querySelector('[data-champ="enigme_reponse_bonne"]');
  if (bloc) {
    const input = bloc.querySelector('.champ-input');
    if (input) {
      let alerte = bloc.querySelector('.message-limite');
      if (!alerte) {
        alerte = document.createElement('p');
        alerte.className = 'message-limite';
        alerte.style.color = 'var(--color-editor-error)';
        alerte.style.fontSize = '0.85em';
        alerte.style.margin = '4px 0 0 5px';
        alerte.style.display = 'none';
        input.insertAdjacentElement('afterend', alerte);
      }

      input.setAttribute('maxlength', '75');

      input.addEventListener('input', () => {
        const longueur = input.value.length;

        if (longueur > 75) input.value = input.value.slice(0, 75);

        if (longueur >= 75) {
          alerte.textContent = '75 caractères maximum atteints.';
          alerte.style.display = '';
        } else {
          alerte.textContent = '';
          alerte.style.display = 'none';
        }
      });
    }
  }


  document.querySelectorAll('[data-champ="enigme_reponse_casse"]').forEach(bloc => {
    if (typeof initChampBooleen === 'function') initChampBooleen(bloc);
  });
  initChampNbTentatives();
  initChampRadioAjax('acf[enigme_mode_validation]');
  mettreAJourCartesStats();
  const enigmeId = panneauEdition?.dataset.postId;

  document.querySelectorAll('input[name="acf[enigme_mode_validation]"]').forEach(radio => {
    radio.addEventListener('change', () => {
      if (enigmeId) {
        forcerRecalculStatutEnigme(enigmeId);
      }
      mettreAJourCartesStats();
    });
  });

  const coutInput = document.querySelector('[data-champ="enigme_tentative.enigme_tentative_cout_points"] .champ-input');
  const coutCheckbox = document.getElementById('cout-gratuit-enigme');
  if (coutInput) {
    ['input', 'change'].forEach(evt => coutInput.addEventListener(evt, mettreAJourCartesStats));
  }
  if (coutCheckbox) {
    coutCheckbox.addEventListener('change', mettreAJourCartesStats);
  }

  initChampPreRequis();
  initChampSolution();
  initSolutionInline();
  const paramsMaj = new URLSearchParams(window.location.search);
  if (paramsMaj.get('maj') === 'solution' && !paramsMaj.has('tab')) {
    ouvrirPanneauSolution();
  }
  initChampConditionnel('acf[enigme_acces_condition]', {
    'immediat': [], // pas d'affichage spécifique pour l'accès immédiat
    'date_programmee': ['#champ-enigme-date'],
    'pre_requis': ['#champ-enigme-pre-requis']
  });
  initChampRadioAjax('acf[enigme_acces_condition]');
  appliquerEtatGratuitEnLive(); // ✅ Synchronise état initial de "Gratuit"

  if (enigmeId) {
    document.querySelectorAll('input[name="acf[enigme_acces_condition]"]').forEach(radio => {
      radio.addEventListener('change', () => {
        forcerRecalculStatutEnigme(enigmeId);
      });
    });
  }

  initPanneauVariantes();
  initPagerTentatives();

  function forcerRecalculStatutEnigme(postId) {
    fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'forcer_recalcul_statut_enigme',
        post_id: postId
      })
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          DEBUG && console.log('🔄 Statut système de l’énigme recalculé');
          mettreAJourCTAValidationChasse(postId);
        } else {
          console.warn('⚠️ Échec recalcul statut énigme :', res.data);
        }
      });
  }

  function mettreAJourCTAValidationChasse(postId) {
    const conteneur = document.getElementById('cta-validation-chasse');
    if (!conteneur) return;

    fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'actualiser_cta_validation_chasse',
        enigme_id: postId
      })
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          conteneur.innerHTML = res.data?.html || '';
        } else {
          console.warn('⚠️ CTA validation non mis à jour :', res.data);
        }
      })
      .catch(err => console.error('❌ Erreur réseau CTA validation', err));
  }
  window.forcerRecalculStatutEnigme = forcerRecalculStatutEnigme;
  window.mettreAJourCTAValidationChasse = mettreAJourCTAValidationChasse;


  (() => {
    const $cout = document.querySelector('.champ-cout');
    const $checkbox = document.getElementById('cout-gratuit-enigme');

    if (!$cout || !$checkbox) return;

    const raw = $cout.value;
    const trimmed = raw.trim();
    const valeur = trimmed === '' ? null : parseInt(trimmed, 10);

    DEBUG && console.log('[INIT GRATUIT] valeur brute =', raw, '| valeur interprétée =', valeur);

    const estGratuit = valeur === 0;

    $checkbox.checked = estGratuit;
    $cout.disabled = estGratuit;

    // 🔄 Mettre à jour le message sur les tentatives après init coût
    if (typeof window.mettreAJourMessageTentatives === 'function') {
      window.mettreAJourMessageTentatives();
    }
  })();

  const boutonSupprimer = document.getElementById('bouton-supprimer-enigme');
  if (boutonSupprimer) {
    boutonSupprimer.addEventListener('click', () => {
      const postId = panneauEdition?.dataset.postId;
      if (!postId) return;

      if (!confirm('Voulez-vous vraiment supprimer cette énigme ?')) return;

      fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'supprimer_enigme',
          post_id: postId
        })
      })
        .then(r => r.json())
        .then(res => {
          if (res.success && res.data?.redirect) {
            window.location.href = res.data.redirect;
          } else {
            alert('Échec suppression : ' + (res.data || 'inconnue'));
          }
        })
        .catch(() => alert('Erreur réseau'));
    });
  }

  initEnigmeReorder();

}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEnigmeEdit);
} else {
  initEnigmeEdit();
}

// ================================
// 🖼️ Panneau images galerie (ACF gallery)
// ================================
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.ouvrir-panneau-images');
  if (!btn || btn.dataset.cpt !== 'enigme') return;

  const panneau = document.getElementById('panneau-images-enigme');
  if (!panneau) return;

  const postId = btn.dataset.postId;
  if (!postId) return;

  // ❌ Ne PAS ouvrir le panneau ici

  fetch(ajaxurl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      action: 'desactiver_htaccess_enigme',
      post_id: postId
    })
  })
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.warn(`⚠️ Désactivation htaccess échouée ou inutile : ${data.data}`);
        return;
      }

      DEBUG && console.log(`🔓 htaccess désactivé pour énigme ${postId}`);

      // ✅ Ouverture du panneau uniquement maintenant
      if (typeof window.openPanel === 'function') {
        window.openPanel('panneau-images-enigme');
      }
    })
    .catch(err => {
      console.error('❌ Erreur réseau AJAX htaccess :', err);
    });
});



// ==============================
// 🔐 Restauration htaccess à la fermeture du panneau images
// ==============================
document.querySelector('#panneau-images-enigme .panneau-fermer')?.addEventListener('click', () => {
  if (typeof window.closePanel === 'function') {
    window.closePanel('panneau-images-enigme');
  }

  const postId = document.querySelector('.edition-panel-enigme')?.dataset.postId;
  if (postId) {
    fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'reactiver_htaccess_immediat_enigme',
        post_id: postId
      })
    }).then(r => r.json())
      .then(res => {
        if (res.success) {
          DEBUG && console.log(`🔒 htaccess restauré immédiatement pour énigme ${postId}`);
        } else {
          console.warn('⚠️ Erreur restauration htaccess immédiate :', res.data);
        }
      });
  }
});
// ================================
// 🔢 Initialisation champ enigme_tentative_max (tentatives/jour)
// ================================
function initChampNbTentatives() {
  const bloc = document.querySelector('[data-champ="enigme_tentative.enigme_tentative_max"]');
  if (!bloc) return;

  const input = bloc.querySelector('.champ-input');
  const postId = bloc.dataset.postId;
  const champ = bloc.dataset.champ;
  const cpt = bloc.dataset.cpt || 'enigme';

  let timerDebounce;

  function mettreAJourAideTentatives() {
    const coutInput = document.querySelector('[data-champ="enigme_tentative.enigme_tentative_cout_points"] .champ-input');
    if (!coutInput) return;

    const cout = parseInt(coutInput.value.trim(), 10);
    const estGratuit = isNaN(cout) || cout === 0;
    const valeur = parseInt(input.value.trim(), 10);

    if (estGratuit) {
      input.max = 24;
      if (valeur > 24) {
        input.value = '24';
      }
    } else {
      input.removeAttribute('max');
    }
  }

  // 💾 Enregistrement avec limite si nécessaire
  input.addEventListener('input', () => {
    clearTimeout(timerDebounce);

    let valeur = parseInt(input.value.trim(), 10);

    // 🔐 Forcer affichage visuel et valeur logique à 1 min
    if (isNaN(valeur) || valeur < 1) {
      valeur = 1;
      input.value = '1';
    }

    const coutInput = document.querySelector('[data-champ="enigme_tentative.enigme_tentative_cout_points"] .champ-input');
    const cout = parseInt(coutInput?.value.trim() || '0', 10);
    const estGratuit = isNaN(cout) || cout === 0;

    if (estGratuit && valeur > 24) {
      valeur = 24;
      input.value = '24';
    }

    timerDebounce = setTimeout(() => {
      modifierChampSimple(champ, valeur, postId, cpt);
    }, 400);
  });


  // 💬 Mise à jour immédiate au chargement
  mettreAJourAideTentatives();

  // 🔁 Lié aux modifs de coût (input + checkbox)
  const coutInput = document.querySelector('[data-champ="enigme_tentative.enigme_tentative_cout_points"] .champ-input');
  const checkbox = document.querySelector('[data-champ="enigme_tentative.enigme_tentative_cout_points"] input[type="checkbox"]');
  if (coutInput) coutInput.addEventListener('input', mettreAJourAideTentatives);
  if (checkbox) checkbox.addEventListener('change', mettreAJourAideTentatives);

  // 🔄 Fonction exportée globalement
  window.mettreAJourMessageTentatives = mettreAJourAideTentatives;
}



// ================================
// 💰 Hook personnalisé – Réaction au champ coût (CPT énigme uniquement)
// ================================
window.onCoutPointsUpdated = function (bloc, champ, valeur, postId, cpt) {
  if (champ === 'enigme_tentative_cout_points') {
    const champMax = document.querySelector('[data-champ="enigme_tentative.enigme_tentative_max"] .champ-input');
    if (champMax) {
      const valeurActuelle = parseInt(champMax.value, 10);

      if (valeur === 0) {
        // Mode gratuit → limite à 24 max
        champMax.max = 24;

        // Si supérieur, on ramène à 24 (ou 5 selon logique métier ? à vérifier)
        if (valeurActuelle > 24) {
          champMax.value = '24';
          modifierChampSimple('enigme_tentative_max', 24, postId, cpt);
        }
      } else {
        // Mode payant → aucune limite
        champMax.removeAttribute('max');
      }
    }
  }
};



// ==============================
// 🔐 Champ bonne réponse – Limite 75 caractères + message d’alerte
// ==============================
function initChampBonneReponse() {
  const bloc = document.querySelector('[data-champ="enigme_reponse_bonne"]');
  if (!bloc) return;

  const input = bloc.querySelector('.champ-input');
  if (!input) return;

  const mettreAJourClasse = () => {
    input.classList.toggle('champ-vide-obligatoire', input.value.trim() === '');
  };

  // Crée ou récupère l’alerte si déjà existante
  let alerte = bloc.querySelector('.message-limite');
  if (!alerte) {
    alerte = document.createElement('p');
    alerte.className = 'message-limite';
    alerte.style.color = 'var(--color-editor-error)';
    alerte.style.fontSize = '0.85em';
    alerte.style.margin = '4px 0 0 5px';
    alerte.style.display = 'none';
    input.insertAdjacentElement('afterend', alerte);
  }

  input.setAttribute('maxlength', '75');
  mettreAJourClasse();

  input.addEventListener('input', () => {
    const longueur = input.value.length;

    if (longueur > 75) {
      input.value = input.value.slice(0, 75);
    }

    if (longueur >= 75) {
      alerte.textContent = '75 caractères maximum atteints.';
      alerte.style.display = '';
    } else {
      alerte.textContent = '';
      alerte.style.display = 'none';
    }

    mettreAJourClasse();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initChampBonneReponse);
} else {
  initChampBonneReponse();
}


// ==============================
// 🖼️ Libellé du bouton galerie ACF
// ==============================
function initLibelleBoutonGalerie() {
  if (!window.acf) return;

  const mettreAJour = (field) => {
    const el = field && field.nodeType ? field : field?.[0];
    if (!el) return;

    const bouton = el.querySelector('.acf-gallery-add');
    if (bouton) {
      const label = window.wp?.i18n?.__('Ajouter une illustration', 'chassesautresor-com') ?? 'Ajouter une illustration';
      bouton.textContent = label;
    }
  };

  window.acf.add_action('ready_field/type=gallery', mettreAJour);
  window.acf.add_action('append_field/type=gallery', mettreAJour);

  document.querySelectorAll('.acf-field[data-type="gallery"]').forEach(mettreAJour);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initLibelleBoutonGalerie);
} else {
  initLibelleBoutonGalerie();
}


// ==============================
// 🧩 Gestion du panneau variantes
// ==============================
function initPanneauVariantes() {
  const panneau = document.getElementById('panneau-variantes-enigme');
  const formulaire = document.getElementById('formulaire-variantes-enigme');
  const postId = formulaire?.dataset.postId;
  const wrapper = formulaire?.querySelector('.liste-variantes-wrapper');
  const boutonAjouter = document.getElementById('bouton-ajouter-variante');
  const messageLimite = document.querySelector('.message-limite-variantes');
  const resumeBloc = document.querySelector('[data-champ="enigme_reponse_variantes"]');
  let listeResume = resumeBloc?.querySelector('.variantes-table');
  let lienAjouterResume = resumeBloc?.querySelector('.champ-ajouter');
  let boutonEditerResume = resumeBloc?.querySelector('.champ-modifier.ouvrir-panneau-variantes');

  if (!panneau || !formulaire || !postId || !wrapper || !boutonAjouter || !messageLimite || !resumeBloc) return;

  function ouvrirPanneau() {
    document.querySelectorAll('.panneau-lateral.ouvert, .panneau-lateral-liens.ouvert').forEach(p => {
      p.classList.remove('ouvert');
      p.setAttribute('aria-hidden', 'true');
    });
    panneau.classList.add('ouvert');
    document.body.classList.add('panneau-ouvert');
    panneau.setAttribute('aria-hidden', 'false');

    const lignes = wrapper.querySelectorAll('.ligne-variante');
    if (lignes.length === 0) {
      ajouterLigneVariante();
    }

    mettreAJourEtatBouton();
  }

  resumeBloc.querySelectorAll('.ouvrir-panneau-variantes').forEach(el => {
    el.addEventListener('click', e => {
      e.preventDefault();
      ouvrirPanneau();
    });
  });

  // Fermer le panneau
  panneau.querySelector('.panneau-fermer')?.addEventListener('click', () => {
    panneau.classList.remove('ouvert');
    document.body.classList.remove('panneau-ouvert');
    panneau.setAttribute('aria-hidden', 'true');
  });

  // Ajouter une ligne
  boutonAjouter.addEventListener('click', () => {
    ajouterLigneVariante();
    mettreAJourEtatBouton();
  });

  // Supprimer une ligne
  formulaire.addEventListener('click', (e) => {
    const btnSupprimer = e.target.closest('.bouton-supprimer-ligne');
    if (!btnSupprimer) return;
    const ligne = btnSupprimer.closest('.ligne-variante');
    const lignes = wrapper.querySelectorAll('.ligne-variante');

    if (!ligne) return;

    if (lignes.length > 1) {
      ligne.remove();
    } else {
      ligne.querySelector('.input-texte').value = '';
      ligne.querySelector('.input-message').value = '';
      ligne.querySelector('input[type="checkbox"]').checked = false;
    }

    mettreAJourEtatBouton();
  });

  // Recalcul du bouton à chaque frappe
  formulaire.addEventListener('input', mettreAJourEtatBouton);

  // Créer une ligne vide
  function ajouterLigneVariante() {
    const lignes = wrapper.querySelectorAll('.ligne-variante');
    const base = lignes[0];
    if (!base) return;

    const nouvelle = base.cloneNode(true);

    nouvelle.querySelector('.input-texte').value = '';
    nouvelle.querySelector('.input-texte').placeholder = wp.i18n.__("réponse déclenchant l'affichage du message", 'chassesautresor-com');

    nouvelle.querySelector('.input-message').value = '';
    nouvelle.querySelector('.input-message').placeholder = wp.i18n.__('Message affiché au joueur', 'chassesautresor-com');
    nouvelle.querySelector('input[type="checkbox"]').checked = false;

    wrapper.appendChild(nouvelle);
  }


  // Gérer affichage bouton et message
  function mettreAJourEtatBouton() {
    const lignes = wrapper.querySelectorAll('.ligne-variante');
    const nb = lignes.length;

    if (nb >= 4) {
      boutonAjouter.style.display = 'none';
      messageLimite.style.display = 'block';
      return;
    }

    const last = lignes[nb - 1];
    const texte = last?.querySelector('.input-texte')?.value.trim();
    const message = last?.querySelector('.input-message')?.value.trim();

    const ligneEstRemplie = texte && message;

    boutonAjouter.style.display = ligneEstRemplie ? 'inline-block' : 'none';
    messageLimite.style.display = 'none';
  }

  // Enregistrement
  formulaire.addEventListener('submit', (e) => {
    e.preventDefault();

    const lignes = wrapper.querySelectorAll('.ligne-variante');
    const updates = [];

    for (let i = 1; i <= 4; i++) {
      const ligne = lignes[i - 1];
      const texte = ligne?.querySelector('.input-texte')?.value.trim() || '';
      const message = ligne?.querySelector('.input-message')?.value.trim() || '';
      const casse = ligne?.querySelector('input[type="checkbox"]')?.checked ? 1 : 0;

      updates.push(['texte_' + i, texte]);
      updates.push(['message_' + i, message]);
      updates.push(['respecter_casse_' + i, casse]);
    }

    const feedback = formulaire.querySelector('.champ-feedback-variantes');
    if (feedback) {
      feedback.style.display = 'block';
      feedback.textContent = wp.i18n.__('Enregistrement...', 'chassesautresor-com');
      feedback.className = 'champ-feedback champ-loading';
    }

    const promises = updates.map(([champ, valeur]) => {
      return fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'modifier_champ_enigme',
          champ,
          valeur,
          post_id: postId
        })
      }).then(r => r.json());
    });

    Promise.all(promises)
      .then(() => {
        if (feedback) {
          feedback.textContent = wp.i18n.__('✔️ Variantes enregistrées', 'chassesautresor-com');
          feedback.className = 'champ-feedback champ-success';
        }

        setTimeout(() => {
          panneau.classList.remove('ouvert');
          document.body.classList.remove('panneau-ouvert');
          panneau.setAttribute('aria-hidden', 'true');

          if (resumeBloc) {
            if (!listeResume) {
              listeResume = document.createElement('table');
              listeResume.className = 'variantes-table';
              const thead = document.createElement('thead');
              const trHead = document.createElement('tr');
              const thTexte = document.createElement('th');
              thTexte.scope = 'col';
              thTexte.textContent = wp.i18n.__('Variante', 'chassesautresor-com');
              const thMessage = document.createElement('th');
              thMessage.scope = 'col';
              thMessage.textContent = wp.i18n.__('Message', 'chassesautresor-com');
              trHead.appendChild(thTexte);
              trHead.appendChild(thMessage);
              thead.appendChild(trHead);
              listeResume.appendChild(thead);
              const tbodyEl = document.createElement('tbody');
              listeResume.appendChild(tbodyEl);
              resumeBloc.insertBefore(listeResume, boutonEditerResume || lienAjouterResume || null);
            }

            const tbody = listeResume.querySelector('tbody');
            tbody.innerHTML = '';
            let nb = 0;
            for (let i = 1; i <= 4; i++) {
              const t = updates.find(u => u[0] === 'texte_' + i)?.[1] || '';
              const m = updates.find(u => u[0] === 'message_' + i)?.[1] || '';
              if (t && m) {
                nb++;
                const tr = document.createElement('tr');
                tr.className = 'variante-resume';
                const tdT = document.createElement('td');
                tdT.className = 'variante-texte';
                tdT.textContent = t;
                const tdM = document.createElement('td');
                tdM.className = 'variante-message';
                tdM.textContent = m;
                tr.appendChild(tdT);
                tr.appendChild(tdM);
                tbody.appendChild(tr);
              }
            }

            if (nb === 0) {
              resumeBloc.classList.add('champ-vide');
              resumeBloc.classList.remove('champ-rempli');
              boutonEditerResume?.style.setProperty('display', 'none');

              if (listeResume) {
                listeResume.remove();
                listeResume = null;
              }

              if (!lienAjouterResume) {
                lienAjouterResume = document.createElement('a');
                lienAjouterResume.href = '#';
                lienAjouterResume.className = 'champ-ajouter ouvrir-panneau-variantes';
                lienAjouterResume.dataset.cpt = 'enigme';
                lienAjouterResume.dataset.postId = postId;
                lienAjouterResume.setAttribute('aria-label', wp.i18n.__('Ajouter des variantes', 'chassesautresor-com'));
                lienAjouterResume.textContent = wp.i18n.__('ajouter des variantes', 'chassesautresor-com');
                resumeBloc.appendChild(lienAjouterResume);
                lienAjouterResume.addEventListener('click', e => {
                  e.preventDefault();
                  ouvrirPanneau();
                });
              }

              lienAjouterResume.style.setProperty('display', 'inline-block');
            } else {
              resumeBloc.classList.add('champ-rempli');
              resumeBloc.classList.remove('champ-vide');
              lienAjouterResume?.style.setProperty('display', 'none');

              if (!boutonEditerResume) {
                boutonEditerResume = document.createElement('button');
                boutonEditerResume.type = 'button';
                boutonEditerResume.className = 'champ-modifier txt-small ouvrir-panneau-variantes';
                boutonEditerResume.dataset.cpt = 'enigme';
                boutonEditerResume.dataset.postId = postId;
                boutonEditerResume.setAttribute('aria-label', wp.i18n.__('Modifier les variantes', 'chassesautresor-com'));
                boutonEditerResume.textContent = wp.i18n.__('modifier', 'chassesautresor-com');
                resumeBloc.appendChild(boutonEditerResume);
                boutonEditerResume.addEventListener('click', e => {
                  e.preventDefault();
                  ouvrirPanneau();
                });
              }

              boutonEditerResume.style.setProperty('display', 'inline-block');
            }
          }

          if (feedback) feedback.textContent = '';
        }, 1000);
      })
      .catch(() => {
        if (feedback) {
          feedback.textContent = wp.i18n.__('❌ Erreur réseau', 'chassesautresor-com');
          feedback.className = 'champ-feedback champ-error';
        }
      });
  });
}

// ==============================
// 📅 Gestion post-update d’un champ de date
// ==============================
window.onDateFieldUpdated = function (input, nouvelleValeur) {
  const bloc = input.closest('[data-champ]');
  const champ = bloc?.dataset.champ;

  if (champ !== 'enigme_acces_date') return;

  const valeur = input.value?.trim() || '';

  // ❌ Champ vide → erreur et affichage
  if (!valeur) {
    afficherErreur(input, "Merci de sélectionner une date.");
    return;
  }

  // ✅ Sinon, on masque toute erreur éventuelle
  masquerErreur(input);
};


function afficherErreur(input, message) {
  const feedback = input.closest('.champ-enigme')?.querySelector('.champ-feedback');
  if (feedback) {
    feedback.textContent = message;
    feedback.style.display = 'block';
    feedback.style.color = 'red';
  }
}

function masquerErreur(input) {
  const feedback = input.closest('.champ-enigme')?.querySelector('.champ-feedback');
  if (feedback) {
    feedback.textContent = '';
    feedback.style.display = 'none';
  }
}

/**
 * 🧩 Initialisation du champ "pré-requis"
 * Corrige P1 + P2 : enregistre la condition pré-requis si nécessaire,
 * et repasse à "immediat" si toutes les cases sont décochées.
 */
function initChampPreRequis() {
  document.querySelectorAll('[data-champ="enigme_acces_pre_requis"]').forEach(bloc => {
    const champ = bloc.dataset.champ;
    const cpt = bloc.dataset.cpt;
    const postId = bloc.dataset.postId;

    const radioPre = document.querySelector('input[name="acf[enigme_acces_condition]"][value="pre_requis"]');
    const radioImmediat = document.querySelector('input[name="acf[enigme_acces_condition]"][value="immediat"]');

    bloc.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      checkbox.addEventListener('change', () => {
        const checkboxes = [...bloc.querySelectorAll('input[type="checkbox"]')];
        const cochés = checkboxes.filter(el => el.checked).map(el => el.value);

        // ✅ 1. Mise à jour des prérequis cochés
        modifierChampSimple(champ, cochés, postId, cpt).then(() => {
          // ✅ 2. Si une ou plusieurs cases sont cochées, enregistrer condition 'pre_requis'
          if (cochés.length > 0) {
            if (radioPre && !radioPre.checked) radioPre.checked = true;

            fetch(ajaxurl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams({
                action: 'verifier_et_enregistrer_condition_pre_requis',
                post_id: postId
              })
            })
              .then(r => r.json())
              .then(res => {
                if (res.success) {
                  DEBUG && console.log('✅ Condition "pré-requis" bien enregistrée après mise à jour des cases');
                } else {
                  console.warn('⚠️ Échec condition pré-requis :', res.data);
                }
              });
          }

          // ❌ 3. Si aucune case cochée → on repasse à immédiat
          if (cochés.length === 0) {
            if (radioImmediat) radioImmediat.checked = true;
            modifierChampSimple('enigme_acces_condition', 'immediat', postId, cpt);
          }
        });
      });
    });
  });
}




// ==============================
// 🧩 Initialisation panneau solution
// ==============================
function initChampSolution() {
  const modeSelecteurs = document.querySelectorAll('input[name="acf[enigme_solution_mode]"]');
  if (!modeSelecteurs.length) return;

  const wrapperDelai = document.querySelector('.acf-field[data-name="enigme_solution_delai"]');
  const wrapperDate = document.querySelector('.acf-field[data-name="enigme_solution_date"]');
  const wrapperExplication = document.querySelector('.acf-field[data-name="enigme_solution_explication"]');
  const wrapperFichier = document.querySelector('.acf-field[data-name="enigme_solution_fichier"]');

  function afficherChamps(valeur) {
    if (wrapperDelai) wrapperDelai.style.display = (valeur === 'delai_fin_chasse') ? '' : 'none';
    if (wrapperDate) wrapperDate.style.display = (valeur === 'date_fin_chasse') ? '' : 'none';

    if (valeur === 'jamais') {
      wrapperExplication?.classList.add('acf-hidden');
      wrapperFichier?.classList.add('acf-hidden');
      return;
    }
  }

  modeSelecteurs.forEach(radio => {
    radio.addEventListener('change', () => afficherChamps(radio.value));
    if (radio.checked) afficherChamps(radio.value);
  });
}


// ==============================
// 🧩 Initialisation inline – solution de l’énigme
// ==============================
function initSolutionInline() {
  const bloc = document.querySelector('.champ-solution-mode');
  if (!bloc) {
    console.warn('initSolutionInline() : .champ-solution-mode introuvable');
    return;
  }

  const postId = bloc.dataset.postId;
  const cpt = bloc.dataset.cpt || 'enigme';

  const cards = bloc.querySelectorAll('.solution-option');
  const cardPdf = bloc.querySelector('.solution-option[data-mode="pdf"]');
  const cardTexte = bloc.querySelector('.solution-option[data-mode="texte"]');
  const btnClearPdf = cardPdf?.querySelector('.solution-reset');
  const btnClearTexte = cardTexte?.querySelector('.solution-reset');

  const inputDelai = bloc.querySelector('#solution-delai');
  const selectHeure = bloc.querySelector('#solution-heure');
  const inputFichier = bloc.querySelector('#solution-pdf-upload');
  const feedbackFichier = bloc.querySelector('.champ-feedback');
  const publicationMessage = bloc.querySelector('.solution-publication-message');
  const textareaExplication = document.querySelector('.acf-field[data-name="enigme_solution_explication"] textarea');

  if (textareaExplication) {
    const valInit = textareaExplication.value.trim();
    if (btnClearTexte) btnClearTexte.style.display = valInit !== '' ? '' : 'none';
  }

  function majMessageSolution() {
    if (!publicationMessage) return;

    const modeActuel = bloc.querySelector('input[name="acf[enigme_solution_mode]"]:checked')?.value;
    const delaiVal = parseInt(inputDelai?.value.trim(), 10) || 0;
    const heureVal = selectHeure?.value || '';

    let label = 'aucune solution ne';
    let note = '';

    if (modeActuel === 'pdf') {
      const titre = cardPdf?.querySelector('h3')?.textContent.trim();
      if (titre && titre !== 'Document PDF') {
        label = `votre fichier ${titre}`;
        note = ` ${delaiVal} jours après la fin de la chasse, à ${heureVal}`;
      } else {
        note = ' (pdf sélectionné mais pas de fichier chargé)';
      }
    } else if (modeActuel === 'texte') {
      const btnTexte = cardTexte?.querySelector('button.stat-value');
      const explicationRemplie = btnTexte && btnTexte.textContent.trim() !== 'Rédiger';
      if (explicationRemplie) {
        label = "votre texte d'explication";
        note = `, ${delaiVal} jours après la fin de la chasse, à ${heureVal}`;
      } else {
        note = ' (rédaction libre sélectionnée mais non remplie)';
      }
    }

    publicationMessage.textContent = `${label} sera affiché(e)${note}`;
  }

  textareaExplication?.addEventListener('input', () => {
    const value = textareaExplication.value.trim();
    const iconTexte = cardTexte?.querySelector('i');
    const boutonTexte = cardTexte?.querySelector('button.stat-value');
    if (value !== '') {
      if (iconTexte) iconTexte.style.color = 'var(--color-editor-success)';
      if (boutonTexte) boutonTexte.textContent = 'éditer';
      if (btnClearTexte) btnClearTexte.style.display = '';
    } else {
      if (iconTexte) iconTexte.style.color = '';
      if (boutonTexte) boutonTexte.textContent = 'Rédiger';
      if (btnClearTexte) btnClearTexte.style.display = 'none';
    }
    majMessageSolution();
  });

  btnClearTexte?.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    textareaExplication.value = '';
    const iconTexte = cardTexte?.querySelector('i');
    const boutonTexte = cardTexte?.querySelector('button.stat-value');
    if (iconTexte) iconTexte.style.color = '';
    if (boutonTexte) boutonTexte.textContent = 'Rédiger';
    if (btnClearTexte) btnClearTexte.style.display = 'none';
    modifierChampSimple('enigme_solution_explication', '', postId, cpt);
    majMessageSolution();
  });

  btnClearPdf?.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const formData = new FormData();
    formData.append('action', 'supprimer_fichier_solution_enigme');
    formData.append('post_id', postId);
    if (feedbackFichier) {
      feedbackFichier.textContent = '⏳ Suppression en cours...';
      feedbackFichier.className = 'champ-feedback champ-loading';
    }
    fetch(ajaxurl, { method: 'POST', body: formData })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          if (cardPdf) {
            const icon = cardPdf.querySelector('i');
            if (icon) icon.style.color = '';
            const titre = cardPdf.querySelector('h3');
            if (titre) titre.textContent = 'Document PDF';
            const lien = cardPdf.querySelector('a.stat-value');
            if (lien) lien.textContent = 'Choisir un fichier';
          }
          if (inputFichier) inputFichier.value = '';
          if (btnClearPdf) btnClearPdf.style.display = 'none';
          if (feedbackFichier) {
            feedbackFichier.textContent = '✅ Fichier supprimé';
            feedbackFichier.className = 'champ-feedback champ-success';
          }
          majMessageSolution();
        } else if (feedbackFichier) {
          feedbackFichier.textContent = '❌ Erreur : ' + (res.data || 'inconnue');
          feedbackFichier.className = 'champ-feedback champ-error';
        }
      })
      .catch(() => {
        if (feedbackFichier) {
          feedbackFichier.textContent = '❌ Erreur réseau';
          feedbackFichier.className = 'champ-feedback champ-error';
        }
      });
  });

  cards.forEach(card => {
    card.addEventListener('click', (e) => {
      e.preventDefault();
      const mode = card.dataset.mode;
      bloc.querySelectorAll('input[name="acf[enigme_solution_mode]"]').forEach(r => {
        r.checked = false;
      });
      card.querySelector('input[name="acf[enigme_solution_mode]"]').checked = true;

      cards.forEach(c => c.classList.remove('active'));
      card.classList.add('active');

      modifierChampSimple('enigme_solution_mode', mode, postId, cpt);
      majMessageSolution();

      if (mode === 'pdf') {
        setTimeout(() => {
          inputFichier?.click();
        }, 100);
      }

      if (mode === 'texte') {
        setTimeout(ouvrirPanneauSolution, 100);
      }
    });
  });

  const checked = bloc.querySelector('input[name="acf[enigme_solution_mode]"]:checked');
  checked?.closest('.solution-option')?.classList.add('active');

  // ⏳ Modification du délai (jours)
  inputDelai?.addEventListener('input', () => {
    const valeur = parseInt(inputDelai.value.trim(), 10);
    if (!isNaN(valeur)) {
      modifierChampSimple('enigme_solution_delai', valeur, postId, cpt);
      majMessageSolution();
    }
  });

  // 🕒 Modification de l'heure
  selectHeure?.addEventListener('change', () => {
    const valeur = selectHeure.value;
    modifierChampSimple('enigme_solution_heure', valeur, postId, cpt);
    majMessageSolution();
  });

  // 📎 Upload fichier PDF
  inputFichier?.addEventListener('change', () => {
    const fichier = inputFichier.files[0];
    if (!fichier || fichier.type !== 'application/pdf') {
      feedbackFichier.textContent = '❌ Fichier invalide. PDF uniquement.';
      feedbackFichier.className = 'champ-feedback champ-error';
      return;
    }

    const formData = new FormData();
    formData.append('action', 'enregistrer_fichier_solution_enigme');
    formData.append('post_id', postId);
    formData.append('fichier_pdf', fichier);

    feedbackFichier.textContent = '⏳ Enregistrement en cours...';
    feedbackFichier.className = 'champ-feedback champ-loading';

    fetch(ajaxurl, {
      method: 'POST',
      body: formData
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          feedbackFichier.textContent = '✅ Fichier enregistré';
          feedbackFichier.className = 'champ-feedback champ-success';

          if (cardPdf) {
            const icon = cardPdf.querySelector('i');
            if (icon) icon.style.color = 'var(--color-editor-success)';
            const titre = cardPdf.querySelector('h3');
            if (titre) titre.textContent = fichier.name;
            const lien = cardPdf.querySelector('a.stat-value');
            if (lien) lien.textContent = 'Modifier';
          }
          if (btnClearPdf) btnClearPdf.style.display = '';
          majMessageSolution();
        } else {
          feedbackFichier.textContent = '❌ Erreur : ' + (res.data || 'inconnue');
          feedbackFichier.className = 'champ-feedback champ-error';
        }
      })
      .catch(() => {
        feedbackFichier.textContent = '❌ Erreur réseau';
        feedbackFichier.className = 'champ-feedback champ-error';
      });
  });

  majMessageSolution();
}


// ==============================
// ✏️ Panneau solution (texte)
// ==============================
function ouvrirPanneauSolution() {
  const panneau = document.getElementById('panneau-solution-enigme');
  if (!panneau) return;

  document.querySelectorAll('.panneau-lateral.ouvert, .panneau-lateral-liens.ouvert').forEach((p) => {
    p.classList.remove('ouvert');
    p.setAttribute('aria-hidden', 'true');
  });

  panneau.classList.add('ouvert');
  document.body.classList.add('panneau-ouvert');
  panneau.setAttribute('aria-hidden', 'false');
}

document.addEventListener('click', (e) => {
  const trigger = e.target.closest('#ouvrir-panneau-solution');
  if (!trigger) return;

  const bloc = document.querySelector('.champ-solution-mode');
  const postId = bloc?.dataset.postId;
  const cpt = bloc?.dataset.cpt || 'enigme';
  const radioTexte = bloc?.querySelector('input[name="acf[enigme_solution_mode]"][value="texte"]');

  if (bloc && radioTexte && !radioTexte.checked) {
    bloc.querySelectorAll('input[name="acf[enigme_solution_mode]"]').forEach(r => { r.checked = false; });
    radioTexte.checked = true;
    bloc.querySelectorAll('.solution-option').forEach(c => c.classList.remove('active'));
    radioTexte.closest('.solution-option')?.classList.add('active');
    if (postId) {
      modifierChampSimple('enigme_solution_mode', 'texte', postId, cpt);
    }
  }

  ouvrirPanneauSolution();
});

// ==============================
// ✖️ Fermeture panneau solution (wysiwyg)
// ==============================
document.addEventListener('click', (e) => {
  if (e.target.closest('#panneau-solution-enigme .panneau-fermer')) {
    const panneau = document.getElementById('panneau-solution-enigme');
    panneau.classList.remove('ouvert');
    document.body.classList.remove('panneau-ouvert');
    panneau.setAttribute('aria-hidden', 'true');
  }
});



// ==============================
// ✅ Enregistrement condition "pré-requis" à la sélection du radio
// ==============================
function initEnregistrementPreRequis() {
  const radioPreRequis = document.querySelector('input[name="acf[enigme_acces_condition]"][value="pre_requis"]');
  const champBloc = document.querySelector('[data-champ="enigme_acces_pre_requis"]');
  const postId = champBloc?.dataset.postId;

  if (!radioPreRequis || !champBloc || !postId) return;

  radioPreRequis.addEventListener('change', () => {
    const cochés = [...champBloc.querySelectorAll('input[type="checkbox"]:checked')].map(cb => cb.value);

    // 🔒 Ne rien faire si aucune case cochée
    if (cochés.length === 0) {
      console.warn('⛔ Pré-requis non enregistré : aucune case cochée.');
      return;
    }

    fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'verifier_et_enregistrer_condition_pre_requis',
        post_id: postId
      })
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          DEBUG && console.log('✅ Condition "pré-requis" enregistrée côté serveur');
        } else {
          console.warn('⚠️ Échec enregistrement condition pré-requis :', res.data);
        }
      })
      .catch(err => {
        console.error('❌ Erreur réseau lors de l’enregistrement de la condition pré-requis', err);
      });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEnregistrementPreRequis);
} else {
  initEnregistrementPreRequis();
}

function mettreAJourCartesStats() {
  const mode = document.querySelector('input[name="acf[enigme_mode_validation]"]:checked')?.value || 'aucune';
  const coutInput = document.querySelector('[data-champ="enigme_tentative.enigme_tentative_cout_points"] .champ-input');
  const cout = coutInput ? parseInt(coutInput.value || '0', 10) : 0;
  const cardTentatives = document.querySelector('#enigme-stats [data-stat="tentatives"]');
  const cardPoints = document.querySelector('#enigme-stats [data-stat="points"]');
  const cardSolutions = document.querySelector('#enigme-stats [data-stat="solutions"]');
  const resolveursSection = document.getElementById('enigme-resolveurs');

  if (cardTentatives) {
    cardTentatives.style.display = mode === 'aucune' ? 'none' : '';
  }
  if (cardPoints) {
    cardPoints.style.display = (mode === 'aucune' || cout <= 0) ? 'none' : '';
  }
  if (cardSolutions) {
    cardSolutions.style.display = mode === 'aucune' ? 'none' : '';
  }
  if (resolveursSection) {
    resolveursSection.style.display = mode === 'aucune' ? 'none' : '';
  }
}

function appliquerEtatGratuitEnLive() {
  DEBUG && console.log('✅ enappliquerEtatGratuit() chargé');
  const $cout = document.querySelector('.champ-cout');
  const $checkbox = document.getElementById('cout-gratuit-enigme');
  if (!$cout || !$checkbox) return;

  function syncGratuit() {
    const raw = $cout.value;
    const trimmed = raw.trim();
    const valeur = trimmed === '' ? 0 : parseInt(trimmed, 10);
    const estGratuit = valeur === 0;

    DEBUG && console.log('[🎯 syncGratuit] coût =', $cout.value, '| gratuit ?', estGratuit);
    $checkbox.checked = estGratuit;
    $cout.disabled = estGratuit;
    if (typeof window.mettreAJourMessageTentatives === 'function') {
      window.mettreAJourMessageTentatives();
    }
  }

  $cout.addEventListener('input', syncGratuit);
  $cout.addEventListener('change', syncGratuit);

  // Appel initial différé de 50ms pour laisser le temps à la valeur d’être injectée
  setTimeout(syncGratuit, 50);
}

function initPagerTentatives() {
  const wrapper = document.querySelector('#enigme-tab-soumission .liste-tentatives');
  const postId = document.querySelector('.edition-panel-enigme')?.dataset.postId;
  const compteur = document.querySelector('#enigme-tab-soumission .total-tentatives');
  if (!wrapper || !postId) return;

  function attachPager() {
    const pager = wrapper.querySelector('.pager');
    if (!pager) return;
    pager.addEventListener('pager:change', (e) => {
      const page = e.detail?.page || 1;
      charger(page);
    });
  }

  attachPager();

  function charger(page) {
    fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'lister_tentatives_enigme',
        enigme_id: postId,
        page
      })
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) return;
        wrapper.innerHTML = res.data.html;
        wrapper.dataset.page = res.data.page;
        wrapper.dataset.pages = res.data.pages;
        wrapper.dataset.total = res.data.total;
        if (compteur) compteur.textContent = '(' + res.data.total + ')';
        attachPager();
      });
  }
}

// ==============================
// ➕ Affichage dynamique du bouton d'ajout d'énigme
// ==============================
window.mettreAJourBoutonAjoutEnigme = function () {
  const nav = document.querySelector('.enigme-navigation');
  if (!nav) return;

  nav.querySelectorAll('#carte-ajout-enigme').forEach((btn) => btn.remove());

  const chasseId = nav.dataset.chasseId;
  if (!chasseId) return;

  const data = new FormData();
  data.append('action', 'verifier_enigmes_completes');
  data.append('chasse_id', chasseId);

  fetch(window.ajaxurl, {
    method: 'POST',
    credentials: 'same-origin',
    body: data
  })
    .then(r => r.json())
    .then(res => {
      if (
        !res.success ||
        res.data.has_incomplete ||
        !res.data.can_add ||
        nav.querySelector('#carte-ajout-enigme')
      ) {
        return;
      }

      const link = document.createElement('a');
      link.id = 'carte-ajout-enigme';
      link.dataset.postId = '0';
      link.href = `${window.location.origin}/creer-enigme/?chasse_id=${chasseId}`;
      link.innerHTML =
        '<i class="fa-solid fa-circle-plus fa-lg" aria-hidden="true"></i>' +
        `<span>${wp.i18n.__('Ajouter une énigme', 'chassesautresor-com')}</span>`;
      const menu = nav.querySelector('.enigme-menu');
      nav.insertBefore(link, menu);
    })
    .catch(() => {});
};

// ==============================
// 🔀 Réordonnancement des énigmes
// ==============================
function initEnigmeReorder() {
  const nav = document.querySelector('.enigme-navigation');
  const menu = nav?.querySelector('.enigme-menu');
  if (!nav || !menu) return;

  menu.querySelectorAll('li').forEach((li) => {
    li.draggable = true;
  });

  let dragged = null;

  menu.addEventListener('dragstart', (e) => {
    dragged = e.target.closest('li');
    if (dragged) {
      e.dataTransfer.effectAllowed = 'move';
      menu.classList.add('dragging');
      dragged.classList.add('dragging');
    }
  });

  menu.addEventListener('dragover', (e) => {
    e.preventDefault();
    const target = e.target.closest('li');
    if (!dragged || !target || dragged === target) return;

    menu.querySelectorAll('.drag-over').forEach((li) => li.classList.remove('drag-over'));
    target.classList.add('drag-over');

    const rect = target.getBoundingClientRect();
    const next = e.clientY > rect.top + rect.height / 2;
    menu.insertBefore(dragged, next ? target.nextSibling : target);
  });

  const saveOrder = () => {
    const order = Array.from(menu.querySelectorAll('li')).map((li) => li.dataset.enigmeId);
    if (!order.length) return;

    const onError = () => {
      alert(wp.i18n.__("Erreur lors de l'enregistrement de l'ordre", 'chassesautresor-com'));
    };

    if (window.wp?.ajax?.post) {
      window.wp.ajax
        .post('reordonner_enigmes', {
          chasse_id: nav.dataset.chasseId,
          ordre: order,
        })
        .catch(onError);
    } else {
      const fd = new FormData();
      fd.append('action', 'reordonner_enigmes');
      fd.append('chasse_id', nav.dataset.chasseId);
      order.forEach((id) => fd.append('ordre[]', id));
      fetch(window.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
      })
        .then((r) => r.json())
        .then((res) => {
          if (!res.success) onError();
        })
        .catch(onError);
    }
  };

  const cleanClasses = () => {
    menu.classList.remove('dragging');
    menu.querySelectorAll('.drag-over').forEach((li) => li.classList.remove('drag-over'));
    dragged?.classList.remove('dragging');
  };

  menu.addEventListener('drop', (e) => {
    e.preventDefault();
    cleanClasses();
    dragged = null;
    saveOrder();
  });

  menu.addEventListener('dragend', () => {
    cleanClasses();
    dragged = null;
    saveOrder();
  });
}
