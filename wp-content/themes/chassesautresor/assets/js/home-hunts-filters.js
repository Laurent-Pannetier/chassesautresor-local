(function () {
  const root = document.querySelector('[data-home-hunts]');

  if (!root || typeof window.fetch !== 'function' || typeof window.FormData !== 'function') {
    return;
  }

  const grid = root.querySelector('.organisateur-chasses-grid');

  if (!grid) {
    return;
  }

  const form = grid.querySelector('[data-home-hunts-filters]');

  if (!form) {
    return;
  }

  const localized = window.homeHuntsFilters || {};
  const endpoint = typeof localized.ajaxUrl === 'string' ? localized.ajaxUrl : '';
  const labels = localized.labels && typeof localized.labels === 'object' ? localized.labels : {};
  const errorLabel = typeof labels.error === 'string' ? labels.error : '';
  const resetLabel = typeof labels.reset === 'string' ? labels.reset : '';

  if (!endpoint) {
    return;
  }

  let nonce = typeof localized.nonce === 'string' ? localized.nonce : (root.dataset ? root.dataset.nonce : '');

  const countElement = form.querySelector('[data-home-hunts-count]') || root.querySelector('[data-home-hunts-count]');
  const feedbackElement = root.querySelector('[data-home-hunts-feedback]');
  const resetButton = form.querySelector('[data-home-hunts-reset]');

  if (countElement && !countElement.dataset.template) {
    countElement.dataset.template = countElement.textContent || '';
  }

  const defaultCountValue = countElement
    ? parseInt(countElement.dataset.defaultCount || '', 10)
    : null;
  const defaultTemplate = countElement ? countElement.dataset.template || '' : '';

  if (resetButton && resetLabel) {
    resetButton.setAttribute('aria-label', resetLabel);
  }

  const locale = typeof document.documentElement.lang === 'string'
    ? document.documentElement.lang.toLowerCase()
    : '';
  const numberFormatter = typeof window.Intl === 'object' && typeof window.Intl.NumberFormat === 'function'
    ? new Intl.NumberFormat(locale || undefined)
    : null;
  const isEnglishLocale = locale.startsWith('en');

  let currentController = null;

  function getLatestNonce() {
    if (root.dataset && typeof root.dataset.nonce === 'string' && root.dataset.nonce.length > 0) {
      nonce = root.dataset.nonce;
    }

    return nonce;
  }

  function formatNumber(value) {
    if (!numberFormatter) {
      return String(value);
    }

    try {
      return numberFormatter.format(value);
    } catch (error) {
      return String(value);
    }
  }

  function setLoading(isLoading) {
    if (root.classList) {
      root.classList.toggle('home-hunts--loading', Boolean(isLoading));
    }

    if (isLoading) {
      grid.setAttribute('aria-busy', 'true');
    } else {
      grid.removeAttribute('aria-busy');
    }

    if (resetButton) {
      resetButton.disabled = Boolean(isLoading);
      if (isLoading) {
        resetButton.setAttribute('aria-disabled', 'true');
      } else {
        resetButton.removeAttribute('aria-disabled');
      }
    }
  }

  function clearFeedback() {
    if (!feedbackElement) {
      return;
    }

    feedbackElement.textContent = '';
    feedbackElement.classList.remove('home-hunts__feedback--error');
  }

  function showError(message) {
    if (!feedbackElement) {
      return;
    }

    const finalMessage = message && message.length > 0 ? message : errorLabel;

    if (!finalMessage) {
      return;
    }

    feedbackElement.textContent = finalMessage;
    feedbackElement.classList.add('home-hunts__feedback--error');
  }

  function formatCountLabel(countValue) {
    const template = defaultTemplate;

    if (template && /\d/u.test(template)) {
      return template.replace(/[\d\s\u00A0,.]+/u, formatNumber(countValue));
    }

    const pluralWord = isEnglishLocale ? 'results' : 'résultats';
    const singularWord = isEnglishLocale ? 'result' : 'résultat';

    return `${formatNumber(countValue)} ${countValue > 1 ? pluralWord : singularWord}`;
  }

  function updateCount(total) {
    if (!countElement) {
      return;
    }

    let value = typeof total === 'number' && Number.isFinite(total) ? Math.max(0, Math.trunc(total)) : null;

    if (null === value) {
      value = Number.isFinite(defaultCountValue) ? Math.max(0, Math.trunc(defaultCountValue)) : null;
    }

    if (null === value) {
      return;
    }

    countElement.textContent = formatCountLabel(value);
    countElement.dataset.count = String(value);
  }

  function clearHunts() {
    let node = form.nextSibling;

    while (node && node !== feedbackElement) {
      const next = node.nextSibling;
      node.parentNode.removeChild(node);
      node = next;
    }
  }

  function insertHunts(html) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;

    const newGrid = wrapper.querySelector('.organisateur-chasses-grid');
    const source = newGrid || wrapper;
    const fragment = document.createDocumentFragment();

    source.childNodes.forEach((child) => {
      if (child.nodeType === Node.ELEMENT_NODE) {
        fragment.appendChild(child);
        return;
      }

      if (child.nodeType === Node.TEXT_NODE && child.textContent && child.textContent.trim().length > 0) {
        fragment.appendChild(child);
      }
    });

    const referenceNode = feedbackElement && grid.contains(feedbackElement) ? feedbackElement : null;

    if (referenceNode) {
      grid.insertBefore(fragment, referenceNode);
    } else {
      grid.appendChild(fragment);
    }
  }

  function replaceHunts(html) {
    clearHunts();
    insertHunts(html);
  }

  function getStatusControl() {
    return form.querySelector('[data-home-hunts-select]');
  }

  function getCostControls() {
    return Array.from(form.querySelectorAll('[data-home-hunts-checkbox]'));
  }

  function buildFormData() {
    const data = new FormData();

    data.append('action', 'ca_filter_chasses');

    const currentNonce = getLatestNonce();

    if (currentNonce) {
      data.append('nonce', currentNonce);
    }

    const statusControl = getStatusControl();

    if (statusControl && typeof statusControl.value === 'string' && statusControl.value.length > 0) {
      data.append('status', statusControl.value);
    }

    const costControls = getCostControls();
    const selectedCosts = costControls.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);

    if (selectedCosts.length > 0) {
      selectedCosts.forEach((value) => {
        data.append('cost[]', value);
      });
    } else {
      data.append('cost[]', '');
    }

    return data;
  }

  function handleSuccess(payload) {
    if (!payload || typeof payload !== 'object') {
      throw new Error('Invalid response');
    }

    if (typeof payload.html === 'string') {
      replaceHunts(payload.html);
    } else {
      replaceHunts('');
    }

    if (typeof payload.total === 'number') {
      updateCount(payload.total);
    } else {
      updateCount(null);
    }

    if (typeof payload.nonce === 'string' && payload.nonce.length > 0) {
      nonce = payload.nonce;
      root.dataset.nonce = payload.nonce;
    }
  }

  function submitFilters() {
    if (currentController && typeof currentController.abort === 'function') {
      currentController.abort();
    }

    const controller = typeof window.AbortController === 'function' ? new AbortController() : null;
    currentController = controller;

    const options = {
      method: 'POST',
      credentials: 'same-origin',
      body: buildFormData(),
    };

    if (controller) {
      options.signal = controller.signal;
    }

    setLoading(true);
    clearFeedback();

    fetch(endpoint, options)
      .then((response) => {
        if (!response || !response.ok) {
          throw new Error('Network error');
        }

        return response.json();
      })
      .then((json) => {
        if (!json || typeof json !== 'object') {
          throw new Error('Invalid response');
        }

        if (json.success !== true) {
          const message = json.data && typeof json.data === 'object' && typeof json.data.message === 'string'
            ? json.data.message
            : errorLabel;

          throw new Error(message || 'Request failed');
        }

        handleSuccess(json.data);
      })
      .catch((error) => {
        if (error && error.name === 'AbortError') {
          return;
        }

        showError(error && error.message ? error.message : errorLabel);
        if (typeof console !== 'undefined' && console.error) {
          console.error('home-hunts-filters: unable to update hunts', error);
        }
      })
      .finally(() => {
        if (currentController === controller) {
          currentController = null;
          setLoading(false);
        }
      });
  }

  function shouldHandleInput(control, eventType) {
    if (eventType === 'change') {
      return true;
    }

    if (eventType === 'input' && control.tagName === 'INPUT') {
      return ['text', 'search', 'number', 'range', 'email', 'url', 'tel'].includes(control.type);
    }

    if (eventType === 'input' && control.tagName === 'TEXTAREA') {
      return true;
    }

    return false;
  }

  function onFiltersChange(event) {
    const target = event.target;

    if (!target || typeof target.closest !== 'function') {
      return;
    }

    const control = target.closest('[data-home-hunts-select], [data-home-hunts-checkbox]');

    if (!control || !form.contains(control)) {
      return;
    }

    if (!shouldHandleInput(control, event.type)) {
      return;
    }

    submitFilters();
  }

  function restoreDefaults() {
    if (typeof form.reset === 'function') {
      form.reset();
    }

    const statusControl = getStatusControl();

    if (statusControl && typeof statusControl.dataset.defaultValue === 'string') {
      statusControl.value = statusControl.dataset.defaultValue;
    }

    getCostControls().forEach((checkbox) => {
      if (typeof checkbox.dataset.defaultChecked === 'string') {
        checkbox.checked = checkbox.dataset.defaultChecked === 'true';
      }
    });
  }

  function onResetClick(event) {
    event.preventDefault();
    restoreDefaults();
    updateCount(defaultCountValue);
    clearFeedback();
    submitFilters();
  }

  form.addEventListener('change', onFiltersChange);
  form.addEventListener('input', onFiltersChange);

  if (resetButton) {
    resetButton.addEventListener('click', onResetClick);
  }
})();
