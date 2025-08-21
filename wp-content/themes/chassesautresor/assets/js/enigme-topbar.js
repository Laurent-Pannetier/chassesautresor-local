(function () {
  function initTopbar() {
    if (!document.body.classList.contains('single-enigme')) {
      return;
    }

    const body = document.body;
    const desktopHeader = document.querySelector('header.site-header');
    let hideTimer;

    function showTopbar() {
      body.classList.add('topbar-visible');
    }

    function hideTopbar() {
      body.classList.remove('topbar-visible');
    }

    // Desktop behaviour: show on hover near the top
    window.addEventListener('mousemove', (e) => {
      if (window.matchMedia('(min-width: 1024px)').matches) {
        if (e.clientY <= 50) {
          showTopbar();
        } else if (!desktopHeader || !desktopHeader.matches(':hover')) {
          hideTopbar();
        }
      }
    });

    if (desktopHeader) {
      desktopHeader.addEventListener('mouseleave', hideTopbar);
    }

    // Mobile behaviour: show after interaction then hide after 3.5s
    function triggerMobile() {
      if (window.matchMedia('(max-width: 1023px)').matches) {
        showTopbar();
        clearTimeout(hideTimer);
        hideTimer = setTimeout(hideTopbar, 3500);
      }
    }

    ['scroll', 'touchstart', 'click'].forEach((evt) => {
      window.addEventListener(evt, triggerMobile, { passive: true });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTopbar);
  } else {
    initTopbar();
  }
})();
