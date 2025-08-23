// ✅ chasse-edit.js
var DEBUG = window.DEBUG || false;
DEBUG && console.log('✅ chasse-edit.js chargé');

let inputDateDebut;
let inputDateFin;
let erreurDebut;
let erreurFin;
let checkboxIllimitee;

function rafraichirCarteIndices() {
  const card = document.querySelector('.dashboard-card.champ-indices');
  if (!card || !window.ChasseIndices) return;

  card.classList.add('loading');
  card.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  const formData = new FormData();
  formData.append('action', 'chasse_lister_indices');
  formData.append('chasse_id', ChasseIndices.chasseId);

  fetch(ChasseIndices.ajaxUrl, {
    method: 'POST',
    credentials: 'same-origin',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (res.success && res.data?.html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = res.data.html;
        const nouvelleCarte = tmp.firstElementChild;
        if (nouvelleCarte) {
          card.replaceWith(nouvelleCarte);
        }
      } else {
        throw new Error('invalid');
      }
    })
    .catch(() => {
      card.classList.remove('loading');
      card.innerHTML = `<p class="error">${ChasseIndices.errorText}</p>`;
    });
}


function initChasseEdit() {
  if (typeof initZonesClicEdition === 'function') initZonesClicEdition();
  inputDateDebut = document.getElementById('chasse-date-debut');
  inputDateFin = document.getElementById('chasse-date-fin');
  erreurDebut = document.getElementById('erreur-date-debut');
  erreurFin = document.getElementById('erreur-date-fin');
  checkboxIllimitee = document.getElementById('duree-illimitee');


  // ==============================
  // 🟢 Initialisation des champs
  // ==============================
  document.querySelectorAll('.champ-chasse[data-champ]').forEach((bloc) => {
    const champ = bloc.dataset.champ;

    if (bloc.classList.contains('champ-img')) {
      if (typeof initChampImage === 'function') initChampImage(bloc);
    } else if (champ === 'chasse_principale_liens') {
      const bouton = bloc.querySelector('.champ-modifier');
      if (bouton && typeof initLiensChasse === 'function') initLiensChasse(bloc);
    } else {
      if (typeof initChampTexte === 'function') initChampTexte(bloc);
    }
  });

  // ==============================
  // 🧰 Déclencheurs de résumé
  // ==============================
  document.querySelectorAll('.edition-panel-chasse .champ-modifier[data-champ]').forEach((btn) => {
    if (typeof initChampDeclencheur === 'function') initChampDeclencheur(btn);
  });

  // ==============================
  // 🛠️ Contrôles panneau principal
  // ==============================
  document.getElementById('toggle-mode-edition-chasse')?.addEventListener('click', () => {
    document.body.classList.toggle('edition-active-chasse');
    document.body.classList.toggle('panneau-ouvert');
    document.body.classList.toggle('mode-edition');
  });
  document.querySelector('.edition-panel-chasse .panneau-fermer')?.addEventListener('click', () => {
    document.body.classList.remove('edition-active-chasse');
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
  const panel = params.get('panel');
  const skipAuto = document.body.classList.contains('scroll-to-enigmes');
  const forceDescription =
    doitOuvrir && tab === 'param' && window.location.hash === '#chasse-description';

  if (doitOuvrir && panel !== 'organisateur' && (!skipAuto || forceDescription)) {
    document.body.classList.add('edition-active-chasse', 'panneau-ouvert', 'mode-edition');
    if (tab) {
      const btn = document.querySelector(`.edition-tab[data-target="chasse-tab-${tab}"]`);
      btn?.click();
    }
    DEBUG && console.log('🔧 Ouverture auto du panneau édition chasse via ?edition=open');
  } else if (skipAuto && !forceDescription && (doitOuvrir || tab)) {
    params.delete('edition');
    params.delete('tab');
    const nouvelleUrl = `${window.location.pathname}${
      params.toString() ? `?${params.toString()}` : ''
    }${window.location.hash}`;
    window.history.replaceState({}, '', nouvelleUrl);
  }

  if (skipAuto && !forceDescription) {
    const cible = document.getElementById('carte-ajout-enigme');
    if (cible) {
      cible.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  // ==============================
  // 📜 Panneau description (wysiwyg)
  // ==============================
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.ouvrir-panneau-description');
    if (!btn || btn.dataset.cpt !== 'chasse') return;
    if (typeof window.openPanel === 'function') {
      window.openPanel('panneau-description-chasse');
    }
  });
  document.querySelector('#panneau-description-chasse .panneau-fermer')?.addEventListener('click', () => {
    if (typeof window.closePanel === 'function') {
      window.closePanel('panneau-description-chasse');
    }
  });

  // ==============================
  // 🏱 Panneau récompense
  // ==============================
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.ouvrir-panneau-recompense');
    if (!btn || btn.dataset.cpt !== 'chasse') return;
    if (typeof window.openPanel === 'function') {
      window.openPanel('panneau-recompense-chasse');
    }

  });
  document.querySelector('#panneau-recompense-chasse .panneau-fermer')?.addEventListener('click', () => {
    if (typeof window.closePanel === 'function') {
      window.closePanel('panneau-recompense-chasse');
    }
  });

  // ==============================
  // 🎯 Badge dynamique récompense
  // ==============================
  if (typeof window.mettreAJourResumeInfos === 'function') {
    window.mettreAJourResumeInfos();
  }
  if (typeof window.mettreAJourCarteAjoutEnigme === 'function') {
    window.mettreAJourCarteAjoutEnigme();
  }
  if (typeof window.mettreAJourEtatIntroChasse === 'function') {
    window.mettreAJourEtatIntroChasse();
  }

  // ==============================
  // 📅 Gestion Date de fin + Durée illimitée
  // ==============================
  if (inputDateFin) {
    if (checkboxIllimitee) {
      const initialDisabled = inputDateFin.disabled;
      inputDateFin.disabled = initialDisabled || checkboxIllimitee.checked;

      checkboxIllimitee.addEventListener('change', function () {
        inputDateFin.disabled = initialDisabled || this.checked;

        // Si la case est décochée et les dates incohérentes, corriger la date de fin
        if (!this.checked) {
          const debut = new Date(inputDateDebut.value);
          const fin = new Date(inputDateFin.value);

          if (!isNaN(debut) && !isNaN(fin) && debut >= fin) {
            const nouvelleDateFin = new Date(debut);
            nouvelleDateFin.setFullYear(nouvelleDateFin.getFullYear() + 2);

            const yyyy = nouvelleDateFin.getFullYear();
            const mm = String(nouvelleDateFin.getMonth() + 1).padStart(2, '0');
            const dd = String(nouvelleDateFin.getDate()).padStart(2, '0');

            const nouvelleValeur = `${yyyy}-${mm}-${dd}`;
            inputDateFin.value = nouvelleValeur;
          }
        }

        const li = inputDateFin.closest('li');
        const status = li?.querySelector('.champ-status');
        if (status) {
          status.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>';
        }

        enregistrerDatesChasse().then((ok) => {
          if (status) {
            status.innerHTML = ok
              ? '<i class="fa-solid fa-check" aria-hidden="true"></i>'
              : '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
            setTimeout(() => { status.innerHTML = ''; }, 1500);
          }
          mettreAJourAffichageDateFin();
        });
      });
    }
      // La logique d'enregistrement de la date de fin est gérée
      // globalement par `date-fields.js` via `initChampDate()`.
      // On se limite ici à mettre à jour l'affichage lorsqu'on
      // modifie la case « illimitée ».
  }
  if (inputDateDebut) {
    // L'enregistrement et la validation sont gérés par `date-fields.js`.
    // Ce fichier ne fait que fournir les messages d'erreur via
    // `validerDatesAvantEnvoi` appelé par `initChampDate()`.
  }



  // ================================
  // 🏆 Gestion de l'enregistrement de la récompense (titre, texte, valeur)
  // ================================
  const boutonRecompense = document.getElementById('bouton-enregistrer-recompense');
  const inputTitreRecompense = document.getElementById('champ-recompense-titre');
  const inputTexteRecompense = document.getElementById('champ-recompense-texte');
  const inputValeurRecompense = document.getElementById('champ-recompense-valeur');
  const panneauRecompense = document.getElementById('panneau-recompense-chasse');
  const boutonSupprimerRecompense = document.getElementById('bouton-supprimer-recompense');

  function formaterValeurRecompense() {
    if (!inputValeurRecompense) return;
    let brut = inputValeurRecompense.value.replace(/\s+/g, '').replace(',', '.');
    if (brut === '') return;
    const nombre = parseFloat(brut);
    if (!isNaN(nombre)) {
      const dec = brut.includes('.') ? Math.min(2, brut.split('.')[1].length) : 0;
      inputValeurRecompense.value = nombre.toLocaleString('fr-FR', {
        minimumFractionDigits: dec,
        maximumFractionDigits: 2
      });
    }
  }

  if (inputValeurRecompense) {
    inputValeurRecompense.addEventListener('input', formaterValeurRecompense);
    formaterValeurRecompense();
  }

  function majAffichageRecompense(titre, texte, valeur) {
    const ligne = document.querySelector('.champ-chasse[data-champ="chasse_infos_recompense_valeur"]');
    if (!ligne) return;
    const champTexte = ligne.querySelector('.champ-texte');
    if (!champTexte) return;
    const peutEditer = !ligne.classList.contains('champ-desactive');
    champTexte.innerHTML = '';

    const complet = titre && texte && valeur && valeur > 0;
    ligne.classList.toggle('champ-rempli', complet);
    ligne.classList.toggle('champ-vide', !complet);

    const span = document.createElement('span');
    span.className = 'champ-texte-contenu';

    if (complet) {
      const valeurSpan = document.createElement('span');
      valeurSpan.className = 'recompense-valeur';
      const arrondi = Math.round(valeur);
      valeurSpan.textContent = arrondi.toLocaleString('fr-FR') + ' €';

      const titreSpan = document.createElement('span');
      titreSpan.className = 'recompense-titre';
      titreSpan.textContent = titre;

      span.appendChild(valeurSpan);
      span.appendChild(document.createTextNode('\u00A0\u2013\u00A0'));
      span.appendChild(titreSpan);
      span.appendChild(document.createTextNode('\u00A0\u2013\u00A0'));
      const descSpan = document.createElement('span');
      descSpan.className = 'recompense-description';
      const texteLimite = texte.length > 200 ? texte.slice(0, 200) + '…' : texte;
      descSpan.textContent = texteLimite;
      span.appendChild(descSpan);
    }

    champTexte.appendChild(span);

    if (peutEditer) {
      const bouton = document.createElement('button');
      bouton.type = 'button';
      bouton.className = 'champ-modifier txt-small ouvrir-panneau-recompense';
      bouton.dataset.champ = 'chasse_infos_recompense_valeur';
      bouton.dataset.cpt = 'chasse';
      bouton.dataset.postId = ligne.dataset.postId || '';
      const action = complet ? 'modifier' : 'ajouter';
      const aria = complet ? 'Modifier la récompense' : 'Ajouter la récompense';
      bouton.setAttribute('aria-label', wp.i18n.__(aria, 'chassesautresor-com'));
      bouton.textContent = wp.i18n.__(action, 'chassesautresor-com');
      champTexte.appendChild(bouton);
      if (typeof initZoneClicEdition === 'function') initZoneClicEdition(bouton);
    }

    if (typeof window.mettreAJourResumeInfos === 'function') {
      window.mettreAJourResumeInfos();
    }
  }

  if (boutonSupprimerRecompense) {
    boutonSupprimerRecompense.addEventListener('click', () => {
      const panneauEdition = document.querySelector('.edition-panel-chasse');
      if (!panneauEdition) return;
      const postId = panneauEdition.dataset.postId;
      if (!postId) return;

      if (!confirm(wp.i18n.__(
        'Voulez-vous vraiment supprimer la récompense ?',
        'chassesautresor-com'
      ))) return;

      const champsASupprimer = [
        'chasse_infos_recompense_titre',
        'chasse_infos_recompense_texte',
        'chasse_infos_recompense_valeur'
      ];

      Promise.all(
        champsASupprimer.map((champ) => {
          return fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              action: 'modifier_champ_chasse',
              champ,
              valeur: '',
              post_id: postId
            })
          });
        })
      ).then(() => {
        majAffichageRecompense('', '', 0);
        inputTitreRecompense.value = '';
        inputTexteRecompense.value = '';
        inputValeurRecompense.value = '';
        if (typeof window.closePanel === 'function') {
          window.closePanel('panneau-recompense-chasse');
        }
      });
    });

  }


  if (boutonRecompense && inputTitreRecompense && inputTexteRecompense && inputValeurRecompense) {
    boutonRecompense.addEventListener('click', () => {
      const titre = inputTitreRecompense.value.trim();
      const texte = inputTexteRecompense.value.trim();
      const valeur = parseFloat(
        inputValeurRecompense.value.replace(/\s+/g, '').replace(',', '.')
      );
      const panneauEdition = document.querySelector('.edition-panel-chasse');
      if (!panneauEdition) return;
      const postId = panneauEdition.dataset.postId;
      if (!postId) return;

      // 🚨 Vérification des 3 champs
      if (!titre.length) {
        alert('Veuillez saisir un titre de récompense.');
        return;
      }

      if (!texte.length) {
        alert('Veuillez saisir une description de récompense.');
        return;
      }

      if (isNaN(valeur) || valeur <= 0 || valeur > 5000000) {
        alert('Veuillez saisir une valeur en euros comprise entre 0 et 5\u00a0000\u00a0000.');
        return;
      }

      // 🔵 Envoi titre de récompense d'abord
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'modifier_champ_chasse',
          champ: 'chasse_infos_recompense_titre',
          valeur: titre,
          post_id: postId
        })
      })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            DEBUG && console.log('✅ Titre récompense enregistré.');

            // 🔵 Ensuite, envoi texte récompense
            return fetch('/wp-admin/admin-ajax.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams({
                action: 'modifier_champ_chasse',
                champ: 'chasse_infos_recompense_texte',
                valeur: texte,
                post_id: postId
              })
            });
          } else {
            throw new Error('Erreur enregistrement titre récompense');
          }
        })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            DEBUG && console.log('✅ Texte récompense enregistré.');

            // 🔵 Ensuite, envoi valeur récompense
            return fetch('/wp-admin/admin-ajax.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams({
                action: 'modifier_champ_chasse',
                champ: 'chasse_infos_recompense_valeur',
                valeur: valeur,
                post_id: postId
              })
            });
          } else {
            throw new Error('Erreur enregistrement texte récompense');
          }
        })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            majAffichageRecompense(titre, texte, valeur);
            if (document.activeElement && panneauRecompense.contains(document.activeElement)) {
              document.activeElement.blur();
              document.body.focus();
            }
            if (typeof window.closePanel === 'function') {
              window.closePanel('panneau-recompense-chasse');
            }
          } else {
            console.error('❌ Erreur valeur récompense', res.data);
          }
        })
        .catch(err => {
          console.error('❌ Erreur sur sauvegarde récompense', err);
        });
    });
  }

  // ==============================
  // ==============================
  // 🏁 Terminaison manuelle
  // ==============================
  let zoneFinChasse;
  let btnFinChasse;

  function resetFinChasse() {
    if (zoneFinChasse) {
      const textarea = zoneFinChasse.querySelector('#chasse-gagnants');
      const valider = zoneFinChasse.querySelector('.valider-fin-chasse-btn');
      zoneFinChasse.style.display = 'none';
      if (textarea) textarea.value = '';
      if (valider) valider.disabled = true;
    }
    if (btnFinChasse) btnFinChasse.style.display = 'inline-block';
    document.removeEventListener('keydown', onEscapeFinChasse);
    zoneFinChasse = null;
    btnFinChasse = null;
  }

  function onEscapeFinChasse(e) {
    if (e.key === 'Escape') {
      resetFinChasse();
    }
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.terminer-chasse-btn');
    if (btn) {
      const zone = btn.nextElementSibling;
      if (zone) {
        zone.style.display = 'block';
        const textarea = zone.querySelector('#chasse-gagnants');
        const valider = zone.querySelector('.valider-fin-chasse-btn');
        textarea.addEventListener('input', () => {
          valider.disabled = textarea.value.trim() === '';
        });
        zoneFinChasse = zone;
        btnFinChasse = btn;
        document.addEventListener('keydown', onEscapeFinChasse);
      }
      btn.style.display = 'none';
      return;
    }

    const annuler = e.target.closest('.annuler-fin-chasse-btn');
    if (annuler) {
      resetFinChasse();
      return;
    }

    const valider = e.target.closest('.valider-fin-chasse-btn');
    if (valider) {
      document.removeEventListener('keydown', onEscapeFinChasse);
      const postId = valider.dataset.postId;
      const zone = valider.closest('.zone-validation-fin');
      const textarea = zone.querySelector('#chasse-gagnants');
      const gagnants = textarea.value.trim();
      if (!gagnants) return;
      valider.disabled = true;
      const now = new Date();
      const dateValue = now.toISOString().split('T')[0];
      const dateDisplay = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()}`;
      const gagnantsEsc = gagnants.replace(/[&<>\"']/g, (c) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
      })[c]);

      modifierChampSimple('champs_caches.chasse_cache_gagnants', gagnants, postId, 'chasse')
        .then((ok) => ok && modifierChampSimple('champs_caches.chasse_cache_date_decouverte', dateValue, postId, 'chasse'))
        .then((ok) => ok && modifierChampSimple('champs_caches.chasse_cache_statut', 'termine', postId, 'chasse'))
        .then((ok) => {
          if (ok) {
            const container = document.querySelector('.champ-mode-fin .fin-chasse-actions');
            if (container) {
              container.innerHTML = `<p class="message-chasse-terminee">Chasse gagnée le ${dateDisplay} par ${gagnantsEsc}</p>`;
            }
          } else {
            valider.disabled = false;
          }
        });
    }
    });

    window.addEventListener('message', (e) => {
      if (e.data && (e.data.type === 'indice-created' || e.data === 'indice-created')) {
        rafraichirCarteIndices();
      }
    });
    window.addEventListener('indice-created', rafraichirCarteIndices);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChasseEdit);
  } else {
    initChasseEdit();
  }


// ==============================
// 🔗 Initialisation des liens chasse
// ==============================
function initLiensChasse(bloc) {
  if (typeof window.initLiensPublics === 'function') {
    initLiensPublics(bloc, {
      panneauId: 'panneau-liens-chasse',
      formId: 'formulaire-liens-chasse',
      action: 'modifier_champ_chasse'
    });
  }
}


// ==============================
// 🔎 Validation logique entre date de début et date de fin
// ==============================
function validerDatesAvantEnvoi(champModifie) {
  console.log('[validerDatesAvantEnvoi] champModifie=', champModifie);
  // ✅ Si illimité, on n'applique aucun contrôle
  if (checkboxIllimitee?.checked) return true;

  if (erreurDebut) erreurDebut.style.display = 'none';
  if (erreurFin) erreurFin.style.display = 'none';

  if (!inputDateDebut || !inputDateFin) return true;

  const maintenant = new Date();
  const dateMinimum = new Date();
  dateMinimum.setFullYear(maintenant.getFullYear() - 10);
  const dateMaximum = new Date();
  dateMaximum.setFullYear(dateMaximum.getFullYear() + 5);

  console.log('[validerDatesAvantEnvoi] bornes=', dateMinimum.toISOString(), dateMaximum.toISOString());

  const debut = new Date(inputDateDebut.value);
  const fin = new Date(inputDateFin.value);

  console.log('[validerDatesAvantEnvoi] debut=', debut.toISOString(), 'fin=', fin.toISOString());

  if (debut < dateMinimum || debut > dateMaximum) {
    if (champModifie === 'debut' && erreurDebut) {
      erreurDebut.textContent = '❌ La date de début est trop ancienne (10 ans maximum d\'ancienneté).';
      erreurDebut.style.display = 'block';
      afficherErreurGlobale('❌ La date de début est trop ancienne (10 ans maximum d\'ancienneté).');
    }
    return false;
  }

  if (isNaN(debut.getTime()) && champModifie === 'debut') {
    if (erreurDebut) {
      erreurDebut.textContent = '❌ Date de début invalide.';
      erreurDebut.style.display = 'block';
      afficherErreurGlobale('❌ Date de début invalide.');
    }
    return false;
  }

  if (isNaN(fin.getTime()) && champModifie === 'fin') {
    if (erreurFin) {
      erreurFin.textContent = '❌ Date de fin invalide.';
      erreurFin.style.display = 'block';
      afficherErreurGlobale('❌ Date de fin invalide.');
    }
    return false;
  }

  if (debut.getTime() >= fin.getTime()) {
    const msg = '❌ La date de début doit être antérieure à la date de fin.';
    if (champModifie === 'debut' && erreurDebut) {
      erreurDebut.textContent = msg;
      erreurDebut.style.display = 'block';
      afficherErreurGlobale(msg);
    }
    if (champModifie === 'fin' && erreurFin) {
      erreurFin.textContent = msg;
      erreurFin.style.display = 'block';
      afficherErreurGlobale(msg);
    }
    return false;
  }

  return true;
}


// ==============================
// 🔥 Affichage d'un message global temporaire
// ==============================
function afficherErreurGlobale(message) {
  console.log('[afficherErreurGlobale]', message);
  const erreurGlobal = document.getElementById('erreur-global');
  if (!erreurGlobal) return;

  erreurGlobal.textContent = message;
  erreurGlobal.style.display = 'block';
  erreurGlobal.style.position = 'fixed';
  erreurGlobal.style.top = '0';
  erreurGlobal.style.left = '0';
  erreurGlobal.style.width = '100%';
  erreurGlobal.style.zIndex = '9999';

  setTimeout(() => {
    erreurGlobal.style.display = 'none';
  }, 4000); // Disparition après 4 secondes
}


// ================================
// 💰 Mise à jour dynamique de l'affichage du coût (Gratuit / Payant)
// ================================
function mettreAJourAffichageCout(postId, cout) {
  const coutAffichage = document.querySelector(`.chasse-prix[data-post-id="${postId}"] .cout-affichage`);
  if (!coutAffichage) return;

  coutAffichage.dataset.cout = cout; // Met à jour data-cout
  coutAffichage.innerHTML = ''; // Vide l'affichage

  const templateId = parseInt(cout, 10) === 0 ? 'icon-free' : 'icon-unlock';
  const template = document.getElementById(templateId);

  if (template) {
    coutAffichage.appendChild(template.content.cloneNode(true));
  }

  if (parseInt(cout, 10) === 0) {
    coutAffichage.insertAdjacentText('beforeend', ' Gratuit');
  } else {
    coutAffichage.insertAdjacentText('beforeend', ` ${cout}`);
    const devise = document.createElement('span');
    devise.className = 'prix-devise';
    devise.textContent = 'pts';
    coutAffichage.appendChild(devise);
  }
}


// ================================
// 💾 Enregistrement du coût en points après clic bouton "✓"
// ================================
document.querySelectorAll('.champ-cout-points .champ-enregistrer').forEach(bouton => {
  bouton.addEventListener('click', (e) => {
    e.preventDefault();
    const li = bouton.closest('li');
    const input = li.querySelector('.champ-input');
    if (!li || !input) return;

    const champ = li.dataset.champ;
    const postId = li.dataset.postId;
    const valeur = input.value.trim() === '' ? '0' : input.value.trim();

    if (!champ || !postId) return;

    modifierChampSimple(champ, valeur, postId, 'chasse');

    if (champ === 'chasse_infos_cout_points') {
      mettreAJourAffichageCout(postId, valeur);
      rafraichirStatutChasse(postId);
    }

    // Cache les boutons après envoi
    const boutons = li.querySelector('.champ-inline-actions');
    if (boutons) {
      boutons.style.opacity = '0';
      boutons.style.visibility = 'hidden';
      input.dataset.valeurInitiale = valeur;
    }
  });
});



// ================================
// 💰 Gestion de l'enregistrement du coût en points
// ================================
document.querySelectorAll('.champ-cout-points .champ-annuler').forEach(bouton => {
  bouton.addEventListener('click', (e) => {
    e.preventDefault();
    const li = bouton.closest('li');
    const input = li.querySelector('.champ-input');
    if (!li || !input) return;

    // Restaure l'ancienne valeur
    input.value = input.dataset.valeurInitiale || '0';

    // Cache les boutons
    const boutons = li.querySelector('.champ-inline-actions');
    if (boutons) {
      boutons.style.opacity = '0';
      boutons.style.visibility = 'hidden';
    }
  });
});



// ================================
// 🎯 Gestion du champ Nombre de gagnants + Illimité (avec debounce)
// ================================
function initChampNbGagnants() {
  const inputNb = document.getElementById('chasse-nb-gagnants');
  const checkboxIllimite = document.getElementById('nb-gagnants-illimite');

  if (!inputNb || !checkboxIllimite) return;

  let timerDebounce;

  checkboxIllimite.addEventListener('change', function () {
    const postId = inputNb.closest('li').dataset.postId;
    if (!postId) return;

    if (checkboxIllimite.checked) {
      inputNb.disabled = true;
      inputNb.value = '0';
    } else {
      inputNb.disabled = false;
      if (parseInt(inputNb.value.trim(), 10) === 0 || inputNb.value.trim() === '') {
        inputNb.value = '1';
      }
    }

    inputNb.dispatchEvent(new Event('input', { bubbles: true }));
    mettreAJourAffichageNbGagnants(postId, inputNb.value.trim());
  });

  inputNb.addEventListener('input', function () {
    const postId = inputNb.closest('li').dataset.postId;
    if (!postId) return;

    clearTimeout(timerDebounce);
    timerDebounce = setTimeout(() => {
      let valeur = parseInt(inputNb.value.trim(), 10);
      if (isNaN(valeur) || valeur < 1) {
        valeur = 1;
        inputNb.value = '1';
      }
      mettreAJourAffichageNbGagnants(postId, valeur);
    }, 500);
  });
}

// ================================
// 🔚 Gestion dynamique du mode de fin
// ================================
function initModeFinChasse() {
  const radios = document.querySelectorAll('input[name="acf[chasse_mode_fin]"]');
  const templateNb = document.getElementById('template-nb-gagnants');
  const templateFin = document.getElementById('template-fin-chasse-actions');
  const modeFinLi = document.querySelector('.champ-mode-fin');
  const finActions = modeFinLi?.querySelector('.fin-chasse-actions');

  if (!radios.length || !templateNb || !modeFinLi || !finActions) return;

  const postId = modeFinLi.dataset.postId;

  function update(save = false) {
    const selected = document.querySelector('input[name="acf[chasse_mode_fin]"]:checked')?.value;
    const existingNb = document.querySelector('.champ-nb-gagnants');

    if (save && selected) {
      modifierChampSimple('chasse_mode_fin', selected, postId, 'chasse');
    }

    if (selected === 'automatique') {
      if (!existingNb) {
        const clone = templateNb.content.firstElementChild.cloneNode(true);
        modeFinLi.insertAdjacentElement('afterend', clone);
        initChampNbGagnants();
        if (typeof initChampTexte === 'function') initChampTexte(clone);
      }

      document.querySelector('.annuler-fin-chasse-btn')?.dispatchEvent(new Event('click', { bubbles: true }));
      const message = finActions.querySelector('.message-chasse-terminee');
      finActions.innerHTML = '';
      if (message) finActions.appendChild(message);

      const inputNb = document.getElementById('chasse-nb-gagnants');
      if (inputNb) {
        mettreAJourAffichageNbGagnants(postId, inputNb.value.trim());
      }
    } else if (selected === 'manuelle') {
      if (existingNb) existingNb.remove();

      if (!finActions.querySelector('.terminer-chasse-btn') && templateFin) {
        const message = finActions.querySelector('.message-chasse-terminee');
        finActions.innerHTML = '';
        if (message) finActions.appendChild(message);
        finActions.appendChild(templateFin.content.cloneNode(true));
      }

      mettreAJourAffichageNbGagnants(postId, 0);
    }
  }

  radios.forEach(radio => {
    radio.addEventListener('change', () => update(true));
  });

  update();
}

// À appeler :
initChampNbGagnants();
initModeFinChasse();

// ==============================
// ➕ Mise à jour de la carte d'ajout d'énigme
// ==============================
window.mettreAJourCarteAjoutEnigme = function () {
  const carte = document.getElementById('carte-ajout-enigme');
  if (!carte) return;

  const panel = document.querySelector('.edition-panel-chasse');
  if (!panel) return;

  const selectors = [
    '[data-champ="post_title"]',
    '[data-champ="chasse_principale_image"]',
    '[data-champ="chasse_principale_description"]'
  ];

  const incomplets = selectors.filter(sel => {
    const li = panel.querySelector('.resume-infos ' + sel);
    return li && li.classList.contains('champ-vide');
  });

  let overlay = carte.querySelector('.overlay-message');

  if (incomplets.length === 0) {
    carte.classList.remove('disabled');
    overlay?.remove();
  } else {
    carte.classList.add('disabled');
    const texte = incomplets.map(sel => {
      if (sel.includes('post_title')) return 'titre';
      if (sel.includes('image')) return 'image';
      if (sel.includes('description')) return 'description';
      return 'champ requis';
    }).join(', ');

    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'overlay-message';
      carte.appendChild(overlay);
    }

    overlay.innerHTML = `
      <i class="fa-solid fa-circle-info"></i>
      <p>Complétez d’abord : ${texte}</p>
    `;
  }
};

// ==============================
// 🎨 Mise à jour visuelle de l'intro de la chasse
// ==============================
window.mettreAJourEtatIntroChasse = function () {
  const section = document.querySelector('.chasse-section-intro');
  const panel = document.querySelector('.edition-panel-chasse');
  if (!section || !panel) return;

  const selectors = [
    '[data-champ="post_title"]',
    '[data-champ="chasse_principale_image"]',
    '[data-champ="chasse_principale_description"]'
  ];

  const incomplets = selectors.filter(sel => {
    const li = panel.querySelector('.resume-infos ' + sel);
    return li && li.classList.contains('champ-vide');
  });

  section.classList.toggle('champ-vide-obligatoire', incomplets.length > 0);
};


// ================================
// 👥 Mise à jour dynamique de l'affichage du nombre de gagnants
// ================================
function mettreAJourAffichageNbGagnants(postId, nb) {
  const nbGagnantsAffichage = document.querySelector(`.nb-gagnants-affichage[data-post-id="${postId}"]`);
  if (!nbGagnantsAffichage) return;

  if (parseInt(nb, 10) === 0) {
    nbGagnantsAffichage.textContent = 'Nombre illimité de gagnants';
  } else {
    nbGagnantsAffichage.textContent = `${nb} gagnant${nb > 1 ? 's' : ''}`;
  }
}



document.addEventListener('acf/submit_success', function (e) {
  DEBUG && console.log('✅ Formulaire ACF soumis avec succès', e);
  if (typeof window.mettreAJourResumeInfos === 'function') {
    window.mettreAJourResumeInfos();
  }
});


// ================================
// 🔁 Rafraîchissement dynamique du statut de la chasse
// ================================
function rafraichirStatutChasse(postId) {
  console.log('[rafraichirStatutChasse] postId=', postId);
  if (!postId) return;

  fetch(ajaxurl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      action: 'forcer_recalcul_statut_chasse',
      post_id: postId
    })
  })
    .then(res => res.json())
    .then(stat => {
      if (!stat.success) {
        console.warn('⚠️ Échec recalcul statut chasse', stat);
        return;
      }

      fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'recuperer_statut_chasse',
          post_id: postId
        })
      })
        .then(r => r.json())
        .then(data => {
          if (data.success && data.data?.statut) {
            const statut = data.data.statut;
            const label = data.data.statut_label;
            const badge = document.querySelector(`.badge-statut[data-post-id="${postId}"]`);
            DEBUG && console.log('🔎 Badge trouvé :', badge);

            if (badge) {
              badge.textContent = label;
              badge.className = `badge-statut statut-${statut}`;
            } else {
              console.warn('❓ Aucun badge-statut trouvé pour postId', postId);
            }
          } else {
            console.warn('⚠️ Données statut invalides', data);
          }
        })
        .catch(err => {
          console.error('❌ Erreur réseau récupération statut chasse', err);
        });
    })
    .catch(err => {
      console.error('❌ Erreur réseau recalcul statut chasse', err);
    });
}

// ================================
// 💾 Enregistrement groupé des dates de chasse
// ================================
function enregistrerDatesChasse() {
  console.log('[enregistrerDatesChasse]');
  if (!inputDateDebut || !inputDateFin) return Promise.resolve(false);

  const postId = inputDateDebut.closest('.champ-chasse')?.dataset.postId;
  if (!postId) return Promise.resolve(false);

  const params = new URLSearchParams({
    action: 'modifier_dates_chasse',
    post_id: postId,
    date_debut: inputDateDebut.value.trim(),
    // On conserve toujours la date en base, même si l'affichage est "Illimitée"
    date_fin: inputDateFin.value.trim(),
    illimitee: checkboxIllimitee?.checked ? 1 : 0
  });
  console.log('[enregistrerDatesChasse] params=', params.toString());

  return fetch(ajaxurl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params
  })
    .then(r => r.json())
    .then(res => {
      console.log('[enregistrerDatesChasse] reponse=', res);
      if (res.success) {
        rafraichirStatutChasse(postId);
        mettreAJourAffichageDateFin();
        return true;
      }
      console.error('❌ Erreur sauvegarde dates:', res.data);
      return false;
    })
    .catch(err => {
      console.error('❌ Erreur réseau sauvegarde dates:', err);
      return false;
    });
}
window.enregistrerDatesChasse = enregistrerDatesChasse;
