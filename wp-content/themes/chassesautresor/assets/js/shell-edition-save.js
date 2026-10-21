// File d'attente des champs simples sur la page d'édition du dashboard.
// Les tiroirs, images, suppression et clôture de chasse restent immédiats.

(function () {
  const closureFields = [
    'champs_caches.chasse_cache_gagnants',
    'champs_caches.chasse_cache_date_decouverte',
  ];

  const fields = new Map();
  let dates = null;
  let dateBaseline = null;
  const baselines = new Map();
  let saving = false;
  let allowUnload = false;
  let state = 'idle';
  let errorMessage = '';
  let button = null;
  let statusNode = null;
  let savedTimer = 0;
  let imageAccessOpen = false;
  let imageTogglePending = false;

  function __(text) {
    if (window.wp && window.wp.i18n && typeof window.wp.i18n.__ === 'function') {
      return window.wp.i18n.__(text, 'chassesautresor-com');
    }
    return text;
  }

  function shell() {
    return document.querySelector('.shell-edition');
  }

  function panel() {
    return document.querySelector('.shell-edition .edition-panel');
  }

  function normalize(value) {
    if (Array.isArray(value)) {
      return JSON.stringify(value.map((item) => String(item)));
    }
    return value == null ? '' : String(value);
  }

  function sameDates(left, right) {
    if (!left || !right) return false;
    return left.date_debut === right.date_debut
      && left.date_fin === right.date_fin
      && String(left.illimitee) === String(right.illimitee)
      && String(left.debut_differee) === String(right.debut_differee);
  }

  function readDates() {
    const start = document.getElementById('chasse-date-debut');
    const end = document.getElementById('chasse-date-fin');
    if (!start || !end) return null;
    const toggleEnd = document.getElementById('date-fin-limitee');
    const toggleStart = document.getElementById('date-debut-differee');
    return {
      date_debut: start.value.trim(),
      date_fin: end.value.trim(),
      illimitee: toggleEnd?.checked ? 0 : 1,
      debut_differee: toggleStart?.checked ? 1 : 0,
    };
  }

  function captureBaselines() {
    baselines.clear();
    document.querySelectorAll('.shell-edition [data-champ]').forEach((bloc) => {
      const champ = bloc.dataset.champ;
      if (!champ || baselines.has(champ)) return;

      const rich = bloc.querySelector('.champ-texte-editeur textarea.wp-editor-area');
      if (rich) {
        const editor = window.tinymce?.get(rich.id);
        const live = editor && typeof editor.isHidden === 'function' && !editor.isHidden();
        baselines.set(champ, normalize(live ? editor.getContent() : rich.value));
        return;
      }

      const gallery = bloc.querySelector('.champ-images-editeur');
      if (gallery) {
        baselines.set(champ, normalize(galleryIds(gallery)));
        return;
      }

      const reward = bloc.querySelector('.champ-recompense-editeur');
      if (reward) {
        readReward(reward).forEach((value, name) => baselines.set(name, value));
        return;
      }

      const links = bloc.querySelector('.champ-liens-editeur');
      if (links) {
        baselines.set(champ, linksValue(links));
        return;
      }

      const radios = bloc.querySelectorAll('input[type="radio"]');
      if (radios.length) {
        const checked = bloc.querySelector('input[type="radio"]:checked');
        baselines.set(champ, checked ? checked.value : '');
        return;
      }

      const boxes = [...bloc.querySelectorAll('input[type="checkbox"]')];
      if (boxes.length > 1) {
        baselines.set(champ, normalize(boxes.filter((el) => el.checked).map((el) => el.value)));
        return;
      }

      const input = bloc.querySelector('input, textarea, select');
      if (!input) return;
      baselines.set(
        champ,
        input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value
      );
    });
    dateBaseline = readDates();
    reconcile();
  }

  function reconcile() {
    fields.forEach((value, champ) => {
      if (baselines.has(champ) && baselines.get(champ) === normalize(value)) {
        fields.delete(champ);
      }
    });
    if (dates && sameDates(dates, dateBaseline)) {
      dates = null;
    }
    render();
  }

  function isDirty() {
    return fields.size > 0 || dates !== null;
  }

  function shouldQueue(cpt, champ, valeur) {
    if (!shell()) return false;
    if (cpt !== 'enigme' && cpt !== 'chasse') return false;
    if (closureFields.includes(champ)) return false;
    if (champ === 'champs_caches.chasse_cache_statut' && valeur === 'termine') return false;
    return true;
  }

  function queue(champ, valeur) {
    const name = String(champ || '');
    if (name === '') return;
    if (baselines.has(name) && baselines.get(name) === normalize(valeur)) {
      fields.delete(name);
    } else {
      fields.set(name, valeur);
    }
    if (state !== 'saving') {
      errorMessage = '';
      state = isDirty() ? 'dirty' : 'idle';
    }
    render();
  }

  function queueDates(payload) {
    if (!payload) return;
    dates = sameDates(payload, dateBaseline) ? null : { ...payload };
    if (state !== 'saving') {
      errorMessage = '';
      state = isDirty() ? 'dirty' : 'idle';
    }
    render();
  }

  function hasField(champ) {
    return fields.has(champ);
  }

  function postId() {
    return panel()?.dataset.postId || '';
  }

  function cpt() {
    return panel()?.dataset.cpt || '';
  }

  function nonce() {
    return cpt() === 'chasse'
      ? (window.CHP_CHASSE_DEFAUT?.nonce || '')
      : (window.CHP_ENIGME_DEFAUT?.nonce || '');
  }

  function appendValue(params, name, value) {
    if (Array.isArray(value)) {
      if (value.length === 0) {
        params.append(name, '');
        return;
      }
      value.forEach((item) => {
        params.append(name + '[]', item == null ? '' : String(item));
      });
      return;
    }
    params.append(name, value == null ? '' : String(value));
  }

  function errorText(data) {
    if (data && typeof data === 'object' && data.message) {
      return String(data.message);
    }
    if (typeof data === 'string' && data !== '') {
      return data;
    }
    return __('Erreur lors de l’enregistrement.');
  }

  function rememberSaved(keys) {
    if (!keys.length) return;
    keys.forEach((key) => {
      if (key === 'dates') {
        dates = null;
        dateBaseline = readDates();
        return;
      }
      const value = fields.get(key);
      fields.delete(key);
      if (value === undefined) return;
      baselines.set(key, normalize(value));
      if (key === 'enigme_visuel_texte' || key === 'chasse_principale_description') {
        updateTexteExcerpt(key, value);
      }
      if (key.indexOf('chasse_infos_recompense_') === 0) {
        updateRewardSummary();
      }
      if (key === 'chasse_principale_liens' && typeof updateTargetBlocks === 'function') {
        let parsed = [];
        try {
          parsed = JSON.parse(value);
        } catch (error) {
          parsed = [];
        }
        const card = document.querySelector('.shell-edition [data-champ="chasse_principale_liens"]');
        if (card) updateTargetBlocks(card, key, postId(), parsed);
      }
      if (typeof window.onChampSimpleMisAJour === 'function') {
        window.onChampSimpleMisAJour(key, postId(), value, cpt(), null);
      }
    });
    if (cpt() !== 'chasse') return;
    if (typeof window.rafraichirStatutChasse === 'function') {
      window.rafraichirStatutChasse(postId());
    }
    if (typeof window.mettreAJourAffichageDateFin === 'function') {
      window.mettreAJourAffichageDateFin();
    }
  }

  function richTextField(area) {
    return area.closest('[data-champ]')?.dataset.champ || '';
  }

  function richTextValue(area) {
    const editor = window.tinymce?.get(area.id);
    const live = editor && typeof editor.isHidden === 'function' && !editor.isHidden();
    return live ? editor.getContent() : area.value;
  }

  function syncRichText() {
    document.querySelectorAll('.shell-edition .champ-texte-editeur textarea.wp-editor-area').forEach((area) => {
      const champ = richTextField(area);
      if (champ) queue(champ, richTextValue(area));
    });
  }

  function readReward(box) {
    return new Map([
      ['chasse_infos_recompense_titre', box.querySelector('#champ-recompense-titre')?.value ?? ''],
      ['chasse_infos_recompense_texte', box.querySelector('#champ-recompense-texte')?.value ?? ''],
      ['chasse_infos_recompense_valeur', box.querySelector('#champ-recompense-valeur')?.value ?? ''],
    ]);
  }

  function syncReward() {
    document.querySelectorAll('.shell-edition .champ-recompense-editeur').forEach((box) => {
      readReward(box).forEach((value, champ) => queue(champ, value));
    });
  }

  function linksValue(box) {
    if (typeof serializeLiensForm !== 'function' || typeof normaliserLiens !== 'function') return '[]';
    return JSON.stringify(normaliserLiens(serializeLiensForm(box), { trier: true }));
  }

  function syncLinks() {
    document.querySelectorAll('.shell-edition .champ-liens-editeur').forEach((box) => {
      queue('chasse_principale_liens', linksValue(box));
    });
  }

  function galleryIds(box) {
    return [...box.querySelectorAll('.acf-gallery-attachment input[type="hidden"]')]
      .map((input) => input.value)
      .filter((value) => value !== '');
  }

  function syncGallery() {
    document.querySelectorAll('.shell-edition .champ-images-editeur').forEach((box) => {
      queue('enigme_visuel_image', galleryIds(box));
    });
  }

  function updateImageSummary(urls) {
    const bloc = document.querySelector('.shell-edition .edition-row[data-champ="enigme_visuel_image"]');
    const button = bloc?.querySelector('.ouvrir-panneau-images');
    if (!bloc || !button) return;
    button.replaceChildren();
    if (!urls.length) {
      const span = document.createElement('span');
      span.className = 'champ-ajout-image';
      span.textContent = __('ajouter');
      button.append(span);
      bloc.classList.add('champ-vide');
      bloc.classList.remove('champ-rempli');
      bloc.dataset.rempli = '0';
      return;
    }
    urls.forEach((url) => {
      const img = document.createElement('img');
      img.src = url;
      img.alt = '';
      img.className = 'vignette-enigme';
      button.append(img);
    });
    bloc.classList.add('champ-rempli');
    bloc.classList.remove('champ-vide');
    bloc.dataset.rempli = '1';
  }

  function imageProtection(action) {
    const id = postId();
    if (!id) return Promise.resolve(false);
    const params = new URLSearchParams();
    params.set('action', action);
    params.set('nonce', window.CHP_ENIGME_DEFAUT?.nonce || '');
    params.set('post_id', id);
    return fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params,
      keepalive: true,
    })
      .then((response) => response.json())
      .then((data) => !!(data && data.success))
      .catch(() => false);
  }

  function refreshImageAccess() {
    const box = document.querySelector('.shell-edition .champ-images-editeur');
    if (!box || box.hidden) {
      imageAccessOpen = false;
      return;
    }
    imageProtection('desactiver_htaccess_enigme').then((ok) => {
      const current = document.querySelector('.shell-edition .champ-images-editeur');
      const stillOpen = !!(current && !current.hidden);
      imageAccessOpen = ok && stillOpen;
      if (ok && !stillOpen) {
        imageProtection('reactiver_htaccess_immediat_enigme');
      }
    });
  }

  function applyImageResult(data) {
    if (!Array.isArray(data?.vignettes)) return;
    updateImageSummary(data.vignettes);
    refreshImageAccess();
  }

  function updateTexteExcerpt(champ, value) {
    const bloc = document.querySelector(`.shell-edition [data-champ="${champ}"]`);
    if (!bloc) return;
    const plain = String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    const words = plain.split(' ').filter(Boolean);
    const excerpt = words.slice(0, 25).join(' ');
    let summary = bloc.querySelector('.champ-texte-contenu');
    if (!excerpt) {
      summary?.remove();
      bloc.classList.add('champ-vide');
      bloc.classList.remove('champ-rempli');
      const edit = bloc.querySelector('.champ-modifier, .champ-ajouter');
      if (edit) {
        edit.classList.remove('champ-modifier');
        edit.classList.add('champ-ajouter');
        edit.textContent = __('ajouter');
        edit.removeAttribute('aria-label');
      }
      return;
    }
    if (!summary) {
      summary = document.createElement('span');
      summary.className = 'champ-texte-contenu';
      bloc.querySelector('.champ-texte')?.prepend(summary);
    }
    summary.textContent = words.length > 25 ? excerpt + '…' : excerpt;
    bloc.classList.add('champ-rempli');
    bloc.classList.remove('champ-vide');
    const add = bloc.querySelector('.champ-ajouter');
    if (add) {
      add.classList.remove('champ-ajouter');
      add.classList.add('champ-modifier');
      const verb = bloc.dataset.cpt === 'chasse' ? __('modifier') : __('éditer');
      add.textContent = verb;
      add.setAttribute('aria-label', verb);
    }
  }

  function updateRewardSummary() {
    const bloc = document.querySelector('.shell-edition .champ-recompense-editeur');
    const row = bloc?.closest('.edition-row');
    if (!bloc || !row) return;
    const titre = bloc.querySelector('#champ-recompense-titre')?.value.trim() || '';
    const texte = bloc.querySelector('#champ-recompense-texte')?.value.trim() || '';
    const valeur = Number(bloc.querySelector('#champ-recompense-valeur')?.value || 0);
    const summary = row.querySelector('.champ-texte-contenu');
    const complet = titre !== '' && texte !== '' && valeur > 0;
    row.classList.toggle('champ-rempli', complet);
    row.classList.toggle('champ-vide', !complet);
    if (!summary) return;
    summary.textContent = complet
      ? Math.round(valeur).toLocaleString('fr-FR') + ' € – ' + titre + ' – ' + texte.slice(0, 200)
      : '';
  }

  function save() {
    syncRichText();
    syncGallery();
    syncReward();
    syncLinks();
    if (saving || !isDirty()) return Promise.resolve(false);
    const currentCpt = cpt();
    const currentId = postId();
    if ((currentCpt !== 'enigme' && currentCpt !== 'chasse') || !currentId) {
      return Promise.resolve(false);
    }

    saving = true;
    state = 'saving';
    errorMessage = '';
    render();

    const params = new URLSearchParams();
    params.set(
      'action',
      currentCpt === 'chasse' ? 'enregistrer_champs_chasse' : 'enregistrer_champs_enigme'
    );
    params.set('post_id', currentId);
    params.set('nonce', nonce());
    fields.forEach((value, champ) => {
      appendValue(params, 'champs[' + champ + ']', value);
    });
    if (dates) {
      params.set('dates[date_debut]', dates.date_debut);
      params.set('dates[date_fin]', dates.date_fin);
      params.set('dates[illimitee]', String(dates.illimitee));
      params.set('dates[debut_differee]', String(dates.debut_differee));
    }

    return fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params,
    })
      .then((response) => response.json())
      .then((res) => {
        saving = false;
        if (!res || !res.success) {
          const saved = Array.isArray(res?.data?.enregistres) ? res.data.enregistres : [];
          rememberSaved(saved);
          applyImageResult(res?.data);
          errorMessage = errorText(res?.data);
          state = 'error';
          render();
          return false;
        }

        rememberSaved(Array.isArray(res.data?.enregistres) ? res.data.enregistres : []);
        applyImageResult(res.data);
        fields.clear();
        dates = null;
        captureBaselines();
        state = 'saved';
        errorMessage = '';
        render();
        window.clearTimeout(savedTimer);
        savedTimer = window.setTimeout(() => {
          if (!isDirty() && state === 'saved') {
            state = 'idle';
            render();
          }
        }, 2500);
        return true;
      })
      .catch(() => {
        saving = false;
        errorMessage = __('Erreur lors de l’enregistrement.');
        state = 'error';
        render();
        return false;
      });
  }

  function render() {
    if (!button || !statusNode) return;
    button.disabled = saving || !isDirty();
    button.textContent = saving ? __('Enregistrement…') : __('Enregistrer');
    statusNode.classList.toggle('is-error', state === 'error');
    if (state === 'error') {
      statusNode.textContent = errorMessage;
      return;
    }
    if (state === 'saving') {
      statusNode.textContent = __('Enregistrement…');
      return;
    }
    if (state === 'saved') {
      statusNode.textContent = __('Enregistré');
      return;
    }
    if (isDirty()) {
      statusNode.textContent = __('Modifications non enregistrées');
      return;
    }
    statusNode.textContent = '';
  }

  function leaveMessage() {
    return __('Des modifications ne sont pas enregistrées. Quitter la page ?');
  }

  function confirmLeave(event) {
    if (allowUnload || !isDirty()) return true;
    if (!window.confirm(leaveMessage())) {
      event.preventDefault();
      event.stopPropagation();
      return false;
    }
    allowUnload = true;
    return true;
  }

  function onDocumentClick(event) {
    const link = event.target.closest('a[href]');
    if (link) {
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
        return;
      }
      const href = link.getAttribute('href') || '';
      if (href.startsWith('#') || href.startsWith('javascript:') || link.hasAttribute('download')) {
        return;
      }
      if (link.target === '_blank') return;
      const url = new URL(link.href, window.location.href);
      const sameDocument = url.origin === window.location.origin
        && url.pathname === window.location.pathname
        && url.search === window.location.search;
      if (sameDocument) return;
      confirmLeave(event);
      return;
    }

    const closer = event.target.closest('[data-shell-edition-leave]');
    if (closer) {
      confirmLeave(event);
    }
  }

  function mount() {
    const editionPanel = panel();
    if (!editionPanel || editionPanel.dataset.saveReady === '1') return;
    const kind = editionPanel.dataset.cpt;
    if (kind !== 'enigme' && kind !== 'chasse') return;

    const bar = document.createElement('div');
    bar.className = 'shell-edition-save';
    statusNode = document.createElement('p');
    statusNode.className = 'shell-edition-save__status';
    statusNode.setAttribute('role', 'status');
    statusNode.setAttribute('aria-live', 'polite');
    button = document.createElement('button');
    button.type = 'button';
    button.className = 'shell-edition-save__button';
    button.addEventListener('click', () => {
      save();
    });
    bar.append(statusNode, button);
    document.body.append(bar);

    const closer = editionPanel.querySelector('.edition-panel-header-top .panneau-fermer');
    if (closer) {
      closer.dataset.shellEditionLeave = '1';
    }

    editionPanel.dataset.saveReady = '1';
    captureBaselines();
    render();
  }

  window.addEventListener('beforeunload', (event) => {
    if (allowUnload || !isDirty()) return;
    event.preventDefault();
    event.returnValue = '';
  });
  document.addEventListener('click', onDocumentClick, true);

  function toggleTexteEnigme(trigger) {
    const bloc = trigger.closest('.edition-row');
    const box = bloc?.querySelector('.champ-texte-editeur');
    if (!box) return;
    const willOpen = box.hasAttribute('hidden');
    box.hidden = !willOpen;
    trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    if (!willOpen) return;
    window.requestAnimationFrame(() => {
      const area = box.querySelector('textarea.wp-editor-area');
      if (!area) return;
      const editor = window.tinymce?.get(area.id);
      if (editor) {
        const frame = editor.iframeElement;
        if (frame && frame.offsetHeight < 80) {
          frame.style.height = '220px';
        }
        return;
      }
      const wrap = box.querySelector('.acf-editor-wrap.delay');
      if (!wrap) return;
      wrap.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
    });
  }

  let richTextWatched = false;

  function watchRichText() {
    if (richTextWatched || !window.acf || typeof window.acf.addAction !== 'function') return;
    richTextWatched = true;
    window.acf.addAction('wysiwyg_tinymce_init', (editor) => {
      const area = editor?.id ? document.getElementById(editor.id) : null;
      const champ = area ? richTextField(area) : '';
      if (!area || !champ || !area.closest('.champ-texte-editeur') || area.dataset.watched === '1') return;
      area.dataset.watched = '1';
      baselines.set(champ, normalize(editor.getContent()));
      fields.delete(champ);
      editor.on('change keyup undo redo', () => {
        queue(champ, editor.getContent());
      });
      if (state !== 'saving') {
        state = isDirty() ? 'dirty' : 'idle';
      }
      render();
    });
  }

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('.shell-edition .ouvrir-panneau-description');
    if (!trigger) return;
    const row = trigger.closest('.edition-row');
    if (!row?.querySelector('.champ-texte-editeur')) return;
    event.preventDefault();
    event.stopPropagation();
    toggleTexteEnigme(trigger);
  }, true);

  function toggleBox(trigger, selector) {
    const bloc = trigger.closest('.edition-row, .dashboard-card');
    const box = bloc?.querySelector(selector);
    if (!box) return;
    const willOpen = box.hasAttribute('hidden');
    box.hidden = !willOpen;
    trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  }

  function watchInlineFields() {
    document.querySelectorAll('.shell-edition .champ-recompense-editeur').forEach((box) => {
      if (box.dataset.watched === '1') return;
      box.dataset.watched = '1';
      box.addEventListener('input', () => {
        readReward(box).forEach((value, champ) => queue(champ, value));
      });
    });
    document.querySelectorAll('.shell-edition .champ-liens-editeur').forEach((box) => {
      if (box.dataset.watched === '1') return;
      box.dataset.watched = '1';
      box.addEventListener('input', () => queue('chasse_principale_liens', linksValue(box)));
    });
  }

  document.addEventListener('click', (event) => {
    const reward = event.target.closest('.shell-edition .ouvrir-panneau-recompense');
    if (reward?.closest('.edition-row')?.querySelector('.champ-recompense-editeur')) {
      event.preventDefault();
      event.stopPropagation();
      toggleBox(reward, '.champ-recompense-editeur');
      return;
    }
    const links = event.target.closest('.shell-edition .ouvrir-panneau-liens');
    if (links?.closest('.dashboard-card')?.querySelector('.champ-liens-editeur')) {
      event.preventDefault();
      event.stopPropagation();
      toggleBox(links, '.champ-liens-editeur');
    }
  }, true);

  function toggleImages(trigger) {
    const row = trigger.closest('.edition-row');
    const box = row?.querySelector('.champ-images-editeur');
    if (!box || imageTogglePending) return;
    const feedback = row.querySelector('.champ-feedback');
    const willOpen = box.hasAttribute('hidden');
    if (!willOpen) {
      box.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      if (!imageAccessOpen) return;
      imageAccessOpen = false;
      imageProtection('reactiver_htaccess_immediat_enigme');
      return;
    }

    imageTogglePending = true;
    imageProtection('desactiver_htaccess_enigme').then((ok) => {
      imageTogglePending = false;
      if (!ok) {
        if (feedback) feedback.textContent = __('Impossible d’ouvrir les illustrations.');
        return;
      }
      if (feedback) feedback.textContent = '';
      imageAccessOpen = true;
      box.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      window.requestAnimationFrame(() => {
        window.dispatchEvent(new Event('resize'));
        if (window.acf && typeof window.acf.doAction === 'function') {
          window.acf.doAction('resize');
        }
      });
    });
  }

  function watchGallery() {
    document.querySelectorAll('.shell-edition .champ-images-editeur').forEach((box) => {
      if (box.dataset.watched === '1') return;
      box.dataset.watched = '1';
      const sync = () => queue('enigme_visuel_image', galleryIds(box));
      box.addEventListener('change', sync);
      const observer = new MutationObserver(sync);
      observer.observe(box, { childList: true, subtree: true });
    });
  }

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('.shell-edition .ouvrir-panneau-images');
    if (!trigger) return;
    const row = trigger.closest('.edition-row');
    if (!row?.querySelector('.champ-images-editeur')) return;
    event.preventDefault();
    event.stopPropagation();
    toggleImages(trigger);
  }, true);

  window.addEventListener('pagehide', () => {
    if (!imageAccessOpen) return;
    imageAccessOpen = false;
    imageProtection('reactiver_htaccess_immediat_enigme');
  });

  window.ctaShellEditionSave = {
    shouldQueue,
    queue,
    queueDates,
    hasField,
    isDirty,
    save,
    rebase() {
      fields.clear();
      dates = null;
      captureBaselines();
      if (state !== 'saving') {
        state = isDirty() ? 'dirty' : 'idle';
        errorMessage = '';
      }
      render();
    },
  };

  function start() {
    watchRichText();
    watchGallery();
    watchInlineFields();
    mount();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
