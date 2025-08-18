/* global ConversionHistoryAjax */
(function () {
  function toggleTable(button) {
    var container = button.closest('.conversion-history');
    var tableWrapper = container.querySelector('.conversion-history-table');
    var expanded = button.getAttribute('aria-expanded') === 'true';

    button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    button.setAttribute(
      'aria-label',
      expanded ? button.getAttribute('data-label-open') : button.getAttribute('data-label-close')
    );

    var textSpan = button.querySelector('.conversion-history-toggle-text');
    if (textSpan) {
      textSpan.textContent = expanded ? button.getAttribute('data-label-open') : button.getAttribute('data-label-close');
    }

    tableWrapper.style.display = expanded ? 'none' : '';
  }

  function loadPage(container, page) {
    var loading = container.querySelector('.conversion-history-loading');
    var tableWrapper = container.querySelector('.conversion-history-table');
    if (loading) {
      loading.style.display = 'inline-block';
    }

    var formData = new FormData();
    formData.append('action', 'load_conversion_history');
    formData.append('nonce', ConversionHistoryAjax.nonce);
    formData.append('page', page);

    fetch(ConversionHistoryAjax.ajax_url, {
      method: 'POST',
      body: formData,
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        if (response.success) {
          tableWrapper.querySelector('tbody').innerHTML = response.data.rows;
        }
      })
      .finally(function () {
        if (loading) {
          loading.style.display = 'none';
        }
      });
  }

  document.addEventListener('click', function (e) {
    var button = e.target.closest('.conversion-history-toggle');
    if (!button) {
      return;
    }
    e.preventDefault();
    toggleTable(button);
  });

  document.addEventListener('pager:change', function (e) {
    var pager = e.target.closest('.conversion-history .points-history-pager');
    if (!pager) {
      return;
    }
    var container = pager.closest('.conversion-history');
    var page = e.detail.page;
    loadPage(container, page);
  });
})();
