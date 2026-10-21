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
      if (key === 'enigme_visuel_texte') {
        updateTexteExcerpt(value);
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

  function richTextValue(area) {
    const editor = window.tinymce?.get(area.id);
    const live = editor && typeof editor.isHidden === 'function' && !editor.isHidden();
    return live ? editor.getContent() : area.value;
  }

  function syncRichText() {
    document.querySelectorAll('.shell-edition .champ-texte-editeur textarea.wp-editor-area').forEach((area) => {
      queue('enigme_visuel_texte', richTextValue(area));
    });
  }

  function updateTexteExcerpt(value) {
    const bloc = document.querySelector('.shell-edition [data-champ="enigme_visuel_texte"]');
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
      add.textContent = __('éditer');
      add.setAttribute('aria-label', __('Éditer le texte'));
    }
  }

  function save() {
    syncRichText();
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
          errorMessage = errorText(res?.data);
          state = 'error';
          render();
          return false;
        }

        rememberSaved(Array.isArray(res.data?.enregistres) ? res.data.enregistres : []);
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
      if (!area || !area.closest('.champ-texte-editeur') || area.dataset.watched === '1') return;
      area.dataset.watched = '1';
      baselines.set('enigme_visuel_texte', normalize(editor.getContent()));
      fields.delete('enigme_visuel_texte');
      editor.on('change keyup undo redo', () => {
        queue('enigme_visuel_texte', editor.getContent());
      });
      if (state !== 'saving') {
        state = isDirty() ? 'dirty' : 'idle';
      }
      render();
    });
  }

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest(
      '.shell-edition .ouvrir-panneau-description[data-champ="enigme_visuel_texte"]'
    );
    if (!trigger) return;
    const row = trigger.closest('.edition-row');
    if (!row?.querySelector('.champ-texte-editeur')) return;
    event.preventDefault();
    event.stopPropagation();
    toggleTexteEnigme(trigger);
  }, true);

  window.ctaShellEditionSave = {
    shouldQueue,
    queue,
    queueDates,
    hasField,
    isDirty,
    save,
  };

  function start() {
    watchRichText();
    mount();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
