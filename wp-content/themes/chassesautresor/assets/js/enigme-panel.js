(function() {
  document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.enigme-mobile-panel-toggle');
    const panel = document.getElementById('enigme-mobile-panel');
    if (!toggle || !panel) return;
    const sheet = panel.querySelector('.enigme-mobile-panel__sheet');
    const overlay = panel.querySelector('.enigme-mobile-panel__overlay');
    const tabs = panel.querySelectorAll('.panel-tab');
    const tabContents = panel.querySelectorAll('.panel-tab-content');
    let lastFocused = null;

    function openPanel() {
      lastFocused = document.activeElement;
      panel.hidden = false;
      panel.classList.add('open');
      toggle.setAttribute('aria-expanded', 'true');
      document.body.classList.add('no-scroll');
      const focusable = sheet.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
      if (focusable.length) {
        focusable[0].focus();
      }
    }

    function closePanel() {
      panel.classList.remove('open');
      panel.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('no-scroll');
      if (lastFocused) {
        lastFocused.focus();
      }
    }

    function trapFocus(e) {
      const focusable = sheet.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }

    toggle.addEventListener('click', () => {
      if (panel.hidden) {
        openPanel();
      } else {
        closePanel();
      }
    });

    overlay.addEventListener('click', closePanel);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !panel.hidden) {
        closePanel();
      } else if (e.key === 'Tab' && !panel.hidden) {
        trapFocus(e);
      }
    });

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => {
          t.classList.remove('panel-tab--active');
          t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('panel-tab--active');
        tab.setAttribute('aria-selected', 'true');
        const target = tab.dataset.target;
        tabContents.forEach(c => {
          c.hidden = c.id !== target;
        });
      });
    });

    panel.querySelectorAll('.enigme-navigation a').forEach(link => {
      link.addEventListener('click', () => {
        closePanel();
      });
    });
  });
})();
