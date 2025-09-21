/**
 * Handle AJAX interactions for the Tentatives table search and pager.
 */
(function () {
  var config = window.caTentativesPager || {};
  var SEARCH_ACTION =
    (config && typeof config.action === 'string' && config.action) ||
    'ca_fetch_tentatives';
  var PAGE_PARAM = 'tentatives-page';

  function getAjaxUrl() {
    if (config && typeof config.ajaxUrl === 'string' && config.ajaxUrl) {
      return config.ajaxUrl;
    }

    if (typeof window.ajaxurl === 'string' && window.ajaxurl) {
      return window.ajaxurl;
    }

    return '';
  }

  function getSearchForm() {
    return document.querySelector('form.table-search[data-ajax-action="' + SEARCH_ACTION + '"]');
  }

  function getWrapper(form) {
    if (!form) {
      return null;
    }

    var target = form.dataset.ajaxTarget;
    if (target) {
      try {
        var node = document.querySelector(target);
        if (node) {
          return node;
        }
      } catch (error) {
        // Ignore invalid selector errors.
      }
    }

    var scope = form.closest('.myaccount-tentatives');
    if (scope) {
      var wrapper = scope.querySelector('.stats-table-wrapper');
      if (wrapper) {
        return wrapper;
      }
    }

    return null;
  }

  function getSearchField(form) {
    if (!form) {
      return null;
    }

    return form.querySelector('input[type="search"]');
  }

  function getSearchValue(form) {
    var field = getSearchField(form);
    if (!field) {
      return '';
    }

    return field.value.trim();
  }

  function toggleReset(form, visible) {
    if (!form) {
      return;
    }

    var button = form.querySelector('[data-table-search-reset]');
    if (!button) {
      return;
    }

    if (visible) {
      button.hidden = false;
      button.disabled = false;
    } else {
      button.hidden = true;
      button.disabled = true;
    }
  }

  function setLoading(wrapper, loading) {
    if (!wrapper) {
      return;
    }

    if (loading) {
      wrapper.classList.add('is-loading');
      wrapper.setAttribute('aria-busy', 'true');
    } else {
      wrapper.classList.remove('is-loading');
      wrapper.removeAttribute('aria-busy');
    }
  }

  function getHiddenFields(form) {
    if (!form) {
      return [];
    }

    return form.querySelectorAll('input[type="hidden"][name]');
  }

  function buildStateUrl(form, term, page) {
    var url = new URL(window.location.href);
    var searchParam = form ? form.dataset.searchParameter : '';
    var contextParam = 'search[context]';
    var hiddenFields = getHiddenFields(form);
    var contextValue = '';

    Array.prototype.forEach.call(hiddenFields, function (field) {
      if (field.name === contextParam) {
        contextValue = field.value || '';
      }
    });

    if (searchParam && term) {
      url.searchParams.set(searchParam, term);
      if (contextValue) {
        url.searchParams.set(contextParam, contextValue);
      } else if (form && form.dataset.searchKey) {
        url.searchParams.set(contextParam, form.dataset.searchKey);
      }
    } else {
      if (searchParam) {
        url.searchParams.delete(searchParam);
      }
      url.searchParams.delete(contextParam);
    }

    if (page > 1) {
      url.searchParams.set(PAGE_PARAM, String(page));
    } else {
      url.searchParams.delete(PAGE_PARAM);
    }

    Array.prototype.forEach.call(hiddenFields, function (field) {
      var name = field.name;
      if (!name || name === searchParam || name === contextParam) {
        return;
      }

      if (field.value === '') {
        url.searchParams.delete(name);
      } else {
        url.searchParams.set(name, field.value);
      }
    });

    return url;
  }

  function buildRequestBody(form, wrapper, page, term) {
    var data = new URLSearchParams();
    var action = form ? form.dataset.ajaxAction || SEARCH_ACTION : SEARCH_ACTION;
    data.set('action', action);
    data.set('page', String(page));

    var perPageAttr = wrapper ? wrapper.getAttribute('data-per-page') : null;
    var perPage = parseInt(perPageAttr || '10', 10);
    if (!perPage || perPage < 1) {
      perPage = 10;
    }
    data.set('per_page', String(perPage));

    var hiddenFields = getHiddenFields(form);
    Array.prototype.forEach.call(hiddenFields, function (field) {
      var name = field.name;
      if (!name) {
        return;
      }

      if (name === 'search[context]' || (form && name === form.dataset.searchParameter)) {
        return;
      }

      data.append(name, field.value);
    });

    var searchParam = form ? form.dataset.searchParameter : '';
    if (searchParam) {
      data.set(searchParam, term);
    }

    var contextParam = 'search[context]';
    if (form) {
      var contextInput = form.querySelector('input[name="search[context]"]');
      if (contextInput && contextInput.value) {
        data.set(contextParam, contextInput.value);
      } else if (form.dataset.searchKey) {
        data.set(contextParam, form.dataset.searchKey);
      }
    }

    return data;
  }

  function updatePager(wrapper, html) {
    if (!wrapper) {
      return;
    }

    var current = wrapper.querySelector('.tentatives-pager');
    if (typeof html === 'string' && html.trim() !== '') {
      var template = document.createElement('template');
      template.innerHTML = html.trim();
      var next = template.content.firstElementChild;

      if (next) {
        if (current && current.parentNode) {
          current.parentNode.replaceChild(next, current);
        } else {
          var table = wrapper.querySelector('table');
          if (table) {
            table.insertAdjacentElement('afterend', next);
          } else {
            wrapper.appendChild(next);
          }
        }
      }

      return;
    }

    if (current && current.parentNode) {
      current.parentNode.removeChild(current);
    }
  }

  function updateTable(wrapper, rowsHtml) {
    if (!wrapper) {
      return;
    }

    var tbody = wrapper.querySelector('tbody');
    if (tbody) {
      tbody.innerHTML = typeof rowsHtml === 'string' ? rowsHtml : '';
    }
  }

  function updateHistory(form, wrapper, page, term) {
    if (!window.history || typeof window.history.replaceState !== 'function') {
      return;
    }

    var url = buildStateUrl(form, term, page);
    window.history.replaceState({}, '', url.toString());
  }

  function loadTentatives(form, options) {
    var wrapper = getWrapper(form);
    var ajaxUrl = getAjaxUrl();

    if (!form || !wrapper || !ajaxUrl) {
      return Promise.resolve();
    }

    var page = options && typeof options.page === 'number' ? options.page : 1;
    if (page < 1) {
      page = 1;
    }

    var term = '';
    if (options && Object.prototype.hasOwnProperty.call(options, 'term')) {
      term = String(options.term || '').trim();
    } else {
      term = getSearchValue(form);
    }

    var stateUrl = buildStateUrl(form, term, page);
    var requestBody = buildRequestBody(form, wrapper, page, term);

    setLoading(wrapper, true);

    return fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: requestBody
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        return response.json();
      })
      .then(function (payload) {
        if (!payload || !payload.success) {
          var message = payload && payload.data && payload.data.message ? payload.data.message : 'Unknown error';
          throw new Error(message);
        }

        return payload.data || {};
      })
      .then(function (data) {
        updateTable(wrapper, data.rows || '');
        updatePager(wrapper, data.pager || '');
        wrapper.setAttribute('data-page', String(data.page || 1));
        toggleReset(form, term !== '');

        var field = getSearchField(form);
        if (field) {
          field.value = term;
        }

        updateHistory(form, wrapper, data.page || 1, term);
      })
      .catch(function (error) {
        console.error('Tentatives table update failed', error);
        window.location.href = stateUrl.toString();
      })
      .finally(function () {
        setLoading(wrapper, false);
      });
  }

  function isTentativesForm(form) {
    return (
      form &&
      typeof form === 'object' &&
      typeof form.classList !== 'undefined' &&
      form.classList.contains('table-search') &&
      form.dataset &&
      form.dataset.ajaxAction === SEARCH_ACTION
    );
  }

  function handleSearchSubmit(event) {
    var detail = event.detail;
    if (!detail || !detail.form) {
      return;
    }

    var form = detail.form;
    if (!isTentativesForm(form)) {
      return;
    }

    event.preventDefault();
    loadTentatives(form, { page: 1, term: detail.term || '' });
  }

  function handleSearchReset(event) {
    var detail = event.detail;
    if (!detail || !detail.form) {
      return;
    }

    var form = detail.form;
    if (!isTentativesForm(form)) {
      return;
    }

    event.preventDefault();

    var field = getSearchField(form);
    if (field) {
      field.value = '';
    }

    toggleReset(form, false);
    loadTentatives(form, { page: 1, term: '' });
  }

  function handlePagerChange(event) {
    var pager = event.target;
    if (!pager || !pager.classList || !pager.classList.contains('tentatives-pager')) {
      return;
    }

    var form = getSearchForm();
    if (!form) {
      return;
    }

    var page = event.detail && typeof event.detail.page === 'number' ? event.detail.page : 1;
    loadTentatives(form, { page: page });
  }

  function handleNativeSubmit(event) {
    var form = event.target;

    if (!isTentativesForm(form)) {
      return;
    }

    if (event.defaultPrevented) {
      return;
    }

    event.preventDefault();
    loadTentatives(form, { page: 1 });
  }

  function handleNativeReset(event) {
    var trigger =
      event.target && typeof event.target.closest === 'function'
        ? event.target.closest('[data-table-search-reset]')
        : null;

    if (!trigger) {
      return;
    }

    var form = trigger.closest('form.table-search');
    if (!isTentativesForm(form)) {
      return;
    }

    if (event.defaultPrevented) {
      return;
    }

    event.preventDefault();

    var field = getSearchField(form);
    if (field) {
      field.value = '';
    }

    toggleReset(form, false);
    loadTentatives(form, { page: 1, term: '' });
  }

  function initialise() {
    var form = getSearchForm();
    if (form) {
      toggleReset(form, getSearchValue(form) !== '');
    }
  }

  document.addEventListener('tablesearch:submit', handleSearchSubmit);
  document.addEventListener('tablesearch:reset', handleSearchReset);
  document.addEventListener('pager:change', handlePagerChange);
  document.addEventListener('submit', handleNativeSubmit);
  document.addEventListener('click', handleNativeReset);
  document.addEventListener('DOMContentLoaded', initialise);
})();
