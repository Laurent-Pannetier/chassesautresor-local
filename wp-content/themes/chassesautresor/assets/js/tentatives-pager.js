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
    url.searchParams.set('section', 'chasses');
    if (page > 1) {
      url.searchParams.set('page', String(page));
    } else {
      url.searchParams.delete('page');
    }
    window.location.href = url.toString();
  });
})();
