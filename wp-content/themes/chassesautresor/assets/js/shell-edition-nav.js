// Le survol d'un point d'exclamation ouvre, à côté du marqueur,
// la liste des éléments qui bloquent l'activation.
// Le clic reste géré par l'attribut popover, y compris sur écran tactile.

(function () {
  function isOpen(popover) {
    try {
      return popover.matches(':popover-open');
    } catch (error) {
      return popover.dataset.open === '1';
    }
  }

  function place(button, popover) {
    const margin = 8;
    const rect = button.getBoundingClientRect();
    popover.style.position = 'fixed';
    popover.style.margin = '0';
    popover.style.inset = 'auto';
    const width = popover.offsetWidth;
    const height = popover.offsetHeight;
    let left = rect.right + margin;
    if (left + width > window.innerWidth - margin) {
      left = Math.max(margin, rect.left - width - margin);
    }
    let top = rect.top;
    if (top + height > window.innerHeight - margin) {
      top = Math.max(margin, window.innerHeight - height - margin);
    }
    popover.style.left = left + 'px';
    popover.style.top = top + 'px';
  }

  function bind(button) {
    const id = button.getAttribute('popovertarget');
    const popover = id ? document.getElementById(id) : null;
    if (!popover || typeof popover.showPopover !== 'function') return;

    // Le titre natif doublerait l'explication déjà affichée au survol.
    button.removeAttribute('title');

    let hideTimer = 0;
    const finePointer = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const open = () => {
      window.clearTimeout(hideTimer);
      if (!isOpen(popover)) {
        popover.showPopover();
      }
      place(button, popover);
    };
    const scheduleHide = () => {
      if (!finePointer()) return;
      window.clearTimeout(hideTimer);
      hideTimer = window.setTimeout(() => {
        if (isOpen(popover)) {
          popover.hidePopover();
        }
      }, 180);
    };

    button.addEventListener('mouseenter', () => {
      if (!finePointer()) return;
      open();
    });
    button.addEventListener('mouseleave', scheduleHide);
    button.addEventListener('focus', () => {
      if (finePointer() && button.matches(':focus-visible')) {
        open();
      }
    });
    button.addEventListener('blur', (event) => {
      if (popover.contains(event.relatedTarget)) return;
      scheduleHide();
    });
    popover.addEventListener('mouseenter', () => {
      window.clearTimeout(hideTimer);
    });
    popover.addEventListener('mouseleave', scheduleHide);
    popover.addEventListener('toggle', (event) => {
      if (event.newState === 'open') {
        place(button, popover);
      }
    });
  }

  function start() {
    document
      .querySelectorAll('.app-nav__marker--incomplete, .hunt-lifecycle-card__gaps')
      .forEach(bind);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
