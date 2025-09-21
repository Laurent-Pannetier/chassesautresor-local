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
    var currentUrl = new URL(window.location.href);
    var url = new URL(currentUrl.toString());
    var param = pager.dataset.param || 'page';
    var section = pager.dataset.section;
    var searchKey = pager.dataset.searchKey;

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

    if (searchKey) {
      var searchParam = 'search[' + searchKey + ']';
      var searchValue = currentUrl.searchParams.get(searchParam);
      if (searchValue !== null) {
        url.searchParams.set(searchParam, searchValue);
      }

      var contextParam = 'search[context]';
      var contextValue = currentUrl.searchParams.get(contextParam);
      if (contextValue !== null) {
        url.searchParams.set(contextParam, contextValue);
      } else if (searchValue !== null) {
        url.searchParams.set(contextParam, searchKey);
      }
    }

    window.location.href = url.toString();
  });
})();
