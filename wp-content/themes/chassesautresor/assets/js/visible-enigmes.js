(function () {
  const cache = { data: null, expires: 0 };

  function fetchVisible(chasseId) {
    const now = Date.now();
    if (cache.data && cache.expires > now) {
      return Promise.resolve(cache.data);
    }
    const params = new URLSearchParams();
    params.append('action', 'chasse_recuperer_enigmes_visibles');
    params.append('chasse_id', chasseId);
    return fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params
    })
      .then(r => r.json())
      .then(res => {
        if (!res.success) return [];
        cache.data = res.data.enigmes;
        cache.expires = Date.now() + 30000; // 30s cache
        return cache.data;
      });
  }

  function renderList(enigmes) {
    const grid = document.querySelector('.bloc-enigmes-chasse .cards-grid');
    if (!grid) return;
    grid.innerHTML = '';
    enigmes.forEach(e => {
      const article = document.createElement('article');
      article.className = 'carte-enigme';
      article.dataset.enigmeId = e.id;
      const link = document.createElement('a');
      link.href = e.permalink;
      link.textContent = e.title;
      article.appendChild(link);
      grid.appendChild(article);
    });
  }

  function refreshList() {
    const container = document.querySelector('.bloc-enigmes-chasse');
    if (!container) return;
    const chasseId = container.dataset.chasseId;
    if (!chasseId) return;
    fetchVisible(chasseId).then(renderList);
  }

  document.addEventListener('enigmeDebloquee', () => {
    cache.data = null;
    cache.expires = 0;
    refreshList();
  });

  window.fetchVisibleEnigmes = fetchVisible;
})();
