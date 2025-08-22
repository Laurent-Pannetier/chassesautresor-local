(function() {
  document.addEventListener('DOMContentLoaded', () => {
    const aside = document.querySelector('.menu-lateral');
    if (!aside) return;
    const bp = getComputedStyle(document.documentElement)
      .getPropertyValue('--breakpoint-desktop')
      .trim() || '1280px';
    if (!window.matchMedia(`(min-width: ${bp})`).matches) return;
    const __ = window.wp?.i18n?.__ || (s => s);
    const opener = document.createElement('button');
    opener.className = 'menu-lateral__reveal';
    opener.type = 'button';
    opener.innerHTML = '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>' +
      '<span class="screen-reader-text">' + __('Afficher le panneau', 'chassesautresor-com') + '</span>';
    document.body.appendChild(opener);
    let timer = null;
    function hideAside() {
      aside.classList.add('is-hidden');
      opener.style.display = 'block';
    }
    function showAside() {
      aside.classList.remove('is-hidden');
      opener.style.display = 'none';
      if (timer) clearTimeout(timer);
      timer = setTimeout(hideAside, 5000);
    }
    opener.addEventListener('click', showAside);
    aside.addEventListener('mouseenter', () => {
      if (timer) clearTimeout(timer);
    });
    aside.addEventListener('mouseleave', () => {
      if (timer) clearTimeout(timer);
      timer = setTimeout(hideAside, 5000);
    });
    timer = setTimeout(hideAside, 5000);
    window.enigmeAside = { show: showAside };
  });
})();
