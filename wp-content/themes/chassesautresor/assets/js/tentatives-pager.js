/**
 * Handle navigation for the Tentatives table using the default pager.
 */
(function () {
  document.addEventListener('pager:change', function (e) {
    var pager = e.target;
    if (!pager.classList.contains('tentatives-pager')) {
      return;
    }
    var page = e.detail.page || 1;
    var url = new URL(window.location.href);
    var param = pager.dataset.param || 'page';
    var section = pager.dataset.section;

    if (typeof section === 'string') {
      if (section.length) {
        url.searchParams.set('section', section);
      } else {
        url.searchParams.delete('section');
      }
    }

    if (page > 1) {
      url.searchParams.set(param, String(page));
    } else {
      url.searchParams.delete(param);
    }

    if (param !== 'page') {
      url.searchParams.delete('page');
    }

    window.location.href = url.toString();
  });
})();
