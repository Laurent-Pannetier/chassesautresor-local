/**
 * Génère les éléments DOM représentant les liens publics (ul ou placeholder).
 * Utilisable pour organisateur, chasse, etc.
 * @param {Array} liens - Tableau d’objets : [{ type_de_lien: 'facebook', url_lien: 'https://...' }]
 * @returns {HTMLElement} Elément racine contenant les liens
 */
function renderLiensPublics(liens = []) {
  const icones = {
    site_web: 'fa-solid fa-globe',
    discord: 'fa-brands fa-discord',
    facebook: 'fa-brands fa-facebook-f',
    twitter: 'fa-brands fa-x-twitter',
    instagram: 'fa-brands fa-instagram'
  };

  const labels = {
    site_web: 'Site Web',
    discord: 'Discord',
    facebook: 'Facebook',
    twitter: 'Twitter/X',
    instagram: 'Instagram'
  };

  if (!Array.isArray(liens) || liens.length === 0) {
    const placeholder = document.createElement('div');
    placeholder.className = 'liens-placeholder';

    const message = document.createElement('p');
    message.className = 'liens-placeholder-message';
    message.textContent = 'Aucun lien ajouté pour le moment.';
    placeholder.appendChild(message);

    Object.entries(icones).forEach(([type, icone]) => {
      const i = document.createElement('i');
      i.className = `fa ${icone} icone-grisee`;
      i.title = labels[type];
      placeholder.appendChild(i);
    });

    return placeholder;
  }

  const liste = document.createElement('ul');
  liste.className = 'liste-liens-publics';

  liens.forEach(({ type_de_lien, url_lien }) => {
    const type = Array.isArray(type_de_lien) ? type_de_lien[0] : type_de_lien;
    const icone = icones[type] || 'fa-link';
    const label = labels[type] || type;
    const url = url_lien || '#';

    const li = document.createElement('li');
    li.className = 'item-lien-public';

    const a = document.createElement('a');
    a.className = `lien-public lien-${type}`;
    a.href = url;
    a.target = '_blank';
    a.rel = 'noopener';

    const icon = document.createElement('i');
    icon.className = `fa ${icone}`;
    a.appendChild(icon);

    const span = document.createElement('span');
    span.className = 'texte-lien';
    span.textContent = label;
    a.appendChild(span);

    li.appendChild(a);
    liste.appendChild(li);
  });

  return liste;
}
window.renderLiensPublicsJS = renderLiensPublics;


/**
 * 🔁 met à jour dynamiquement le titre dans le header pour un CPT donné
 * @param {string} cpt - Le type de post (ex: 'organisateur', 'chasse', 'enigme')
 * @param {string} valeur - Le nouveau titre à afficher
 */
window.mettreAJourTitreHeader = function (cpt, valeur) {
  const selecteurs = {
    organisateur: '.header-organisateur__nom',
    chasse: '.titre-objet[data-cpt="chasse"]',
    enigme: '.titre-objet[data-cpt="enigme"]'
  };

  const cible = document.querySelector(selecteurs[cpt]);
  if (cible) {
    cible.textContent = valeur;
  } else {
    console.warn('❌ Impossible de trouver le header pour le CPT :', cpt);
  }
};

/**
 * 🔁 Met à jour dynamiquement la légende (sous-titre) d’une énigme dans le header.
 * @param {string} valeur - La nouvelle légende à afficher
 */
window.mettreAJourLegendeEnigme = function (valeur) {
  const legende = document.querySelector('.enigme-soustitre');
  if (legende) {
    legende.textContent = valeur;
  }
};



/**
 * 🖼️ Met à jour dynamiquement l’image visible pour un CPT donné
 * après modification via un panneau d’édition.
 *
 * @param {string} cpt - Le nom du CPT (ex. "organisateur", "chasse", "enigme")
 * @param {number|string} postId - L’ID du post
 * @param {string} nouvelleUrl - L’URL de l’image mise à jour
 */
function mettreAJourVisuelCPT(cpt, postId, nouvelleUrl) {
  document.querySelectorAll(`img.visuel-cpt[data-cpt="${cpt}"][data-post-id="${postId}"]`)
    .forEach(img => {
      img.src = nouvelleUrl;
      img.srcset = nouvelleUrl;
    });
}

/**
 * Initialise la logique d'édition des liens publics pour un bloc donné.
 * Regroupe l'ouverture/fermeture du panneau, la collecte des données et
 * l'envoi AJAX.
 *
 * @param {HTMLElement} bloc - Le bloc contenant les métadonnées (data-champ, data-post-id)
 * @param {Object} params - Identifiants et action AJAX
 * @param {string} params.panneauId - ID du panneau latéral contenant le formulaire
 * @param {string} params.formId - ID du formulaire de liens
 * @param {string} params.action - Action AJAX à appeler
 */
function openLocalPanel(panneau, panneauId) {
  if (typeof window.openPanel === 'function') {
    window.openPanel(panneauId);
  } else {
    document
      .querySelectorAll('.panneau-lateral.ouvert, .panneau-lateral-liens.ouvert')
      .forEach((p) => {
        p.classList.remove('ouvert');
        p.setAttribute('aria-hidden', 'true');
      });
    panneau.classList.add('ouvert');
    document.body.classList.add('panneau-ouvert');
    panneau.setAttribute('aria-hidden', 'false');
  }
}

function closeLocalPanel(panneau, panneauId) {
  if (typeof window.closePanel === 'function') {
    window.closePanel(panneauId);
  } else {
    panneau.classList.remove('ouvert');
    document.body.classList.remove('panneau-ouvert');
    panneau.setAttribute('aria-hidden', 'true');
  }
}

function setupPanelHandlers(bouton, panneau, panneauId) {
  bouton.addEventListener('click', (e) => {
    e.preventDefault();
    openLocalPanel(panneau, panneauId);
  });

  panneau.querySelector('.panneau-fermer')?.addEventListener('click', () => {
    closeLocalPanel(panneau, panneauId);
  });
}

function serializeLiensForm(formulaire) {
  const donnees = [];
  formulaire.querySelectorAll('.champ-url-lien').forEach((input) => {
    const ligne = input.closest('[data-type]');
    const type = ligne?.dataset.type;
    const url = input.value.trim();

    if (type && url !== '') {
      try {
        new URL(url);
        donnees.push({ type_de_lien: type, url_lien: url });
      } catch (_) {
        input.classList.add('champ-erreur');
      }
    }
  });

  return donnees;
}

function updateTargetBlocks(bloc, champ, postId, donnees) {
  const champDonnees = bloc.querySelector('.champ-donnees');
  if (champDonnees) {
    champDonnees.dataset.valeurs = JSON.stringify(donnees);
  }

  let zoneAffichage = bloc.querySelector('.champ-affichage');
  if (!zoneAffichage) {
    const fiche = document.querySelector(
      `.champ-chasse.champ-fiche-publication[data-champ="${champ}"][data-post-id="${postId}"]`
    );
    zoneAffichage = fiche?.querySelector('.champ-affichage');
  }

  if (zoneAffichage && typeof renderLiensPublicsJS === 'function') {
    zoneAffichage.replaceChildren(renderLiensPublicsJS(donnees));

    if (!zoneAffichage.dataset.noEdit && !bloc.querySelector('.champ-modifier')) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'champ-modifier txt-small ouvrir-panneau-liens';
      btn.setAttribute('aria-label', 'Configurer vos liens');
      btn.textContent = wp.i18n.__('modifier', 'chassesautresor-com');
      zoneAffichage.appendChild(btn);
    }
  }

  bloc.classList.toggle('champ-vide', donnees.length === 0);
  bloc.classList.toggle('champ-rempli', donnees.length > 0);

  document
    .querySelectorAll(`.champ-chasse[data-champ="${champ}"][data-post-id="${postId}"]`)
    .forEach((blocCible) => {
      if (blocCible === bloc) return;

      const zone = blocCible.querySelector('.champ-affichage');
      if (zone && typeof renderLiensPublicsJS === 'function') {
        zone.replaceChildren(renderLiensPublicsJS(donnees));

        if (!zone.dataset.noEdit && !blocCible.querySelector('.champ-modifier')) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'champ-modifier txt-small ouvrir-panneau-liens';
          btn.setAttribute('aria-label', 'Configurer vos liens');
          btn.textContent = wp.i18n.__('modifier', 'chassesautresor-com');
          zone.appendChild(btn);
        }
      }

      blocCible.classList.toggle('champ-vide', donnees.length === 0);
      blocCible.classList.toggle('champ-rempli', donnees.length > 0);
    });

  document
    .querySelectorAll(`.champ-organisateur[data-champ="${champ}"][data-post-id="${postId}"]`)
    .forEach((blocCible) => {
      const zone = blocCible.querySelector('.champ-affichage');

      if (zone && typeof renderLiensPublicsJS === 'function') {
        zone.replaceChildren(renderLiensPublicsJS(donnees));

        if (!zone.dataset.noEdit && !blocCible.querySelector('.champ-modifier')) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'champ-modifier txt-small ouvrir-panneau-liens';
          btn.setAttribute('aria-label', 'Configurer vos liens');
          btn.textContent = wp.i18n.__('modifier', 'chassesautresor-com');
          zone.appendChild(btn);
        }
      }

      blocCible.classList.toggle('champ-vide', donnees.length === 0);
      blocCible.classList.toggle('champ-rempli', donnees.length > 0);
    });
}

function initLiensPublics(bloc, { panneauId, formId, action, reload = false }) {
  const champ = bloc.dataset.champ;
  const postId = bloc.dataset.postId;
  const bouton = bloc.querySelector('.champ-modifier');
  const panneau = document.getElementById(panneauId);
  let formulaire = document.getElementById(formId);
  const feedback = bloc.querySelector('.champ-feedback');

  if (!champ || !postId || !bouton || !panneau || !formulaire) return;

  setupPanelHandlers(bouton, panneau, panneauId);

  // ❌ Supprime les éventuels anciens écouteurs
  const clone = formulaire.cloneNode(true);
  formulaire.replaceWith(clone);
  formulaire = clone;

  formulaire.addEventListener('submit', async (e) => {
    e.preventDefault();
    e.stopPropagation();

    const donnees = serializeLiensForm(formulaire);

    try {
      const response = await fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action,
          champ,
          post_id: postId,
          valeur: JSON.stringify(donnees)
        })
      });

      const res = await response.json();
      if (!res.success) throw new Error(res.data || 'Erreur AJAX');

      updateTargetBlocks(bloc, champ, postId, donnees);
      closeLocalPanel(panneau, panneauId);

      if (typeof window.mettreAJourResumeInfos === 'function') {
        window.mettreAJourResumeInfos();
      }

      if (reload) {
        location.reload();
      }
    } catch (err) {
      console.error('❌ AJAX fail', err.message || err);
      if (feedback) {
        feedback.textContent = 'Erreur : ' + (err.message || 'Serveur ou réseau.');
        feedback.className = 'champ-feedback champ-error';
      }
    }
  });
}

window.initLiensPublics = initLiensPublics;
