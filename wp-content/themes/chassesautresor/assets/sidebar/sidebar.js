(function() {
  function init() {
    const aside = document.querySelector('.menu-lateral');
    if (!aside) return;
    const bp = getComputedStyle(document.documentElement)
      .getPropertyValue('--breakpoint-desktop')
      .trim() || '1280px';
    const isDesktop = window.matchMedia(`(min-width: ${bp})`).matches;
    const __ = window.wp?.i18n?.__ || (s => s);
    let opener = null;
    let timer = null;
    function hideAside() {
      if (!isDesktop) return;
      aside.classList.add('is-hidden');
      if (opener) opener.style.display = 'flex';
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
    }
    function showAside() {
      if (!isDesktop) return;
      aside.classList.remove('is-hidden');
      if (opener) opener.style.display = 'none';
      if (timer) clearTimeout(timer);
      timer = setTimeout(hideAside, 5000);
    }
    if (isDesktop) {
      opener = document.createElement('button');
      opener.className = 'menu-lateral__reveal';
      opener.type = 'button';
      opener.innerHTML = '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>' +
        '<span class="screen-reader-text">' + __('Afficher le panneau', 'chassesautresor-com') + '</span>';
      document.body.appendChild(opener);
      opener.addEventListener('click', showAside);
      opener.addEventListener('mouseenter', showAside);
      aside.addEventListener('mouseenter', () => {
        if (timer) clearTimeout(timer);
      });
      aside.addEventListener('mouseleave', () => {
        if (timer) clearTimeout(timer);
        timer = setTimeout(hideAside, 5000);
      });
    }
    const menu = aside.querySelector('.enigme-menu');
    const search = aside.querySelector('.enigme-menu__search');
    if (menu && search) {
      let searchTimer = null;
      search.addEventListener('input', () => {
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
          const q = search.value.toLowerCase();
          const items = menu.querySelectorAll('li[data-enigme-id]');
          items.forEach(li => {
            li.hidden = !li.textContent.toLowerCase().includes(q);
          });
          const groups = menu.querySelectorAll('.enigme-menu__group');
          groups.forEach(group => {
            const visible = group.querySelector('li[data-enigme-id]:not([hidden])');
            const toggle = group.querySelector('.enigme-menu__group-toggle');
            const list = group.querySelector('.enigme-menu__group-list');
            if (q && visible) {
              group.hidden = false;
              if (toggle && list) {
                toggle.setAttribute('aria-expanded', 'true');
                list.hidden = false;
              }
            } else {
              group.hidden = !visible;
            }
          });
        }, 300);
      });
      aside.addEventListener('click', e => {
        const btn = e.target.closest('.enigme-menu__group-toggle');
        if (!btn) return;
        const list = btn.nextElementSibling;
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        if (list) list.hidden = expanded;
      });
    }
    function reloadNav(chasseId) {
      if (!chasseId) return;
      const data = new URLSearchParams();
      data.append('action', 'chasse_recuperer_navigation');
      data.append('chasse_id', chasseId);
      fetch(sidebarData.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data
      })
        .then(r => r.json())
        .then(res => {
          if (!res.success) return;
          const nav = document.querySelector('.enigme-navigation');
          const menu = nav ? nav.querySelector('.enigme-menu') : null;
          if (menu) {
            menu.innerHTML = res.data.html;
          }
          if (nav && Array.isArray(res.data.ids)) {
            nav.dataset.visibleIds = res.data.ids.join(',');
          }
        });
    }
    document.addEventListener('enigmeDebloquee', () => {
      const nav = document.querySelector('.enigme-navigation');
      const chasseId = nav ? nav.dataset.chasseId : null;
      reloadNav(chasseId);
    });
    showAside();
    window.sidebarAside = { show: showAside, reload: reloadNav };
    window.enigmeAside = window.sidebarAside;
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
