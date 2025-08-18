/**
 * Handle AJAX pagination for the points history table.
 */
(function () {
  function loadPage(wrapper, page) {
    var params = new URLSearchParams({
      action: 'load_points_history',
      nonce: PointsHistoryAjax.nonce,
      page: String(page),
    });
    fetch(PointsHistoryAjax.ajax_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString(),
    })
      .then(function (res) { return res.json(); })
      .then(function (response) {
        if (response.success) {
          var tbody = wrapper.querySelector('tbody');
          if (tbody) {
            tbody.innerHTML = response.data.rows;
          }
        }
      });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest(
      '.points-history-pager .pager-first, .points-history-pager .pager-prev, .points-history-pager .pager-next, .points-history-pager .pager-last'
    );
    if (!btn) {
      return;
    }
    e.preventDefault();
    var pager = btn.closest('.points-history-pager');
    if (!pager) {
      return;
    }
    var total = parseInt(pager.getAttribute('data-total') || '1', 10);
    var current = parseInt(pager.getAttribute('data-current') || '1', 10);
    if (btn.classList.contains('pager-first')) {
      current = 1;
    } else if (btn.classList.contains('pager-prev')) {
      if (current > 1) current -= 1;
    } else if (btn.classList.contains('pager-next')) {
      if (current < total) current += 1;
    } else if (btn.classList.contains('pager-last')) {
      current = total;
    }
    pager.setAttribute('data-current', String(current));
    var select = pager.querySelector('.pager-select');
    if (select) {
      select.value = String(current);
    }
    var wrapper = pager.closest('.stats-table-wrapper');
    if (wrapper) {
      loadPage(wrapper, current);
    }
  });

  document.addEventListener('change', function (e) {
    var select = e.target.closest('.points-history-pager .pager-select');
    if (!select) {
      return;
    }
    var pager = select.closest('.points-history-pager');
    var page = parseInt(select.value, 10);
    pager.setAttribute('data-current', String(page));
    var wrapper = pager.closest('.stats-table-wrapper');
    if (wrapper) {
      loadPage(wrapper, page);
    }
  });
})();

