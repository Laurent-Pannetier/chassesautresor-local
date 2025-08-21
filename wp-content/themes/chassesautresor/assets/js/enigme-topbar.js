(function () {
  function initTopbar() {
    if (!document.body.classList.contains('single-enigme')) {
      return;
    }

    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      return;
    }

    const body = document.body;
    const desktopHeader = document.querySelector('header.site-header');
    let hideTimer;

    body.classList.remove('topbar-visible');

    function showTopbar() {
      clearTimeout(hideTimer);
      body.classList.add('topbar-visible');
    }

    function scheduleHide() {
      clearTimeout(hideTimer);
      hideTimer = setTimeout(() => {
        body.classList.remove('topbar-visible');
      }, 1000);
    }

    // Desktop behaviour: show on hover near the top
    window.addEventListener('mousemove', (e) => {
      if (e.clientY <= 50) {
        showTopbar();
      } else if (!desktopHeader || !desktopHeader.matches(':hover')) {
        scheduleHide();
      }
    });

    if (desktopHeader) {
      desktopHeader.addEventListener('mouseenter', showTopbar);
      desktopHeader.addEventListener('mouseleave', scheduleHide);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTopbar);
  } else {
    initTopbar();
  }
})();
