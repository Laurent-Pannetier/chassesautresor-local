// ========================================
// 📁 myaccount.js
// Charge les sections admin via AJAX dans l'espace "Mon Compte".
// ========================================

document.addEventListener('DOMContentLoaded', () => {
  const navs = document.querySelectorAll('.dashboard-nav');
  const content = document.querySelector('.myaccount-content');
  const header = document.querySelector('.myaccount-title');
  const siteMessages = document.querySelector('.msg-important');

  if (!navs.length || !content || !siteMessages || typeof ctaMyAccount === 'undefined') {
    return;
  }

  const fadeFlash = () => {
    const flash = siteMessages ? siteMessages.querySelector('.flash') : null;
    if (flash) {
      setTimeout(() => {
        flash.remove();
      }, 3000);
    }
  };

  const decorateMessages = () => {
    if (!siteMessages) {
      return;
    }
    siteMessages.querySelectorAll('p').forEach((p) => {
      if (p.classList.contains('message-erreur')) {
        p.setAttribute('role', 'alert');
        p.setAttribute('aria-live', 'assertive');
      } else {
        if (!p.className.match(/message-(info|succes)/) && !p.classList.contains('flash')) {
          p.classList.add('message-info');
        }
        p.setAttribute('role', 'status');
        p.setAttribute('aria-live', 'polite');
      }
    });

    siteMessages.querySelectorAll('.message-close').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const key = btn.dataset.key;
        if (!key) {
          return;
        }
        const params = new URLSearchParams();
        params.set('action', 'cta_dismiss_message');
        params.set('key', key);
        try {
          await fetch(ctaMyAccount.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString(),
          });
        } catch (e) {
          // ignore network errors
        }
        const parent = btn.closest('p');
        if (parent) {
          parent.remove();
        }
      });
    });
  };

  const loadSection = async (link, push = true) => {
    const section = link.dataset.section;
    if (!section) {
      window.location.href = link.href;
      return;
    }

    const params = new URLSearchParams(window.location.search);
    params.set('action', 'cta_load_admin_section');
    params.set('section', section);
    const url = `${ctaMyAccount.ajaxUrl}?${params.toString()}`;

    try {
      const response = await fetch(url, { credentials: 'same-origin' });
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      const data = await response.json();
      if (!data.success) {
        throw new Error('Request failed');
      }
      const messages = data.data.messages || '';
      if (siteMessages) {
        siteMessages.innerHTML = messages;
      }
      content.innerHTML = data.data.html;
      decorateMessages();
      fadeFlash();
      document
        .querySelectorAll('.dashboard-nav-link[data-section]')
        .forEach((a) => a.classList.remove('active'));
      link.classList.add('active');
      if (header) {
        const newTitle = link.dataset.title || link.textContent.trim();
        if (newTitle) {
          header.textContent = newTitle;
        }
      }
      document.dispatchEvent(
        new CustomEvent('myaccountSectionLoaded', { detail: { section } })
      );
      if (push) {
        window.history.pushState(null, '', link.href);
      } else {
        window.history.replaceState(null, '', '/mon-compte/');
      }
    } catch (err) {
      if (siteMessages) {
        siteMessages.innerHTML = `
          <p class="message-erreur" role="alert" aria-live="assertive">Impossible de charger la section.</p>
          <p class="message-info" role="status" aria-live="polite"><a href="#" class="reload-section">Recharger</a> ou <a href="${link.href}">ouvrir la page complète</a>.</p>
        `;
      }
      content.innerHTML = '';
      const reload = siteMessages ? siteMessages.querySelector('.reload-section') : null;
      if (reload) {
        reload.addEventListener('click', (e) => {
          e.preventDefault();
          loadSection(link);
        });
      }
    }
  };

  navs.forEach((nav) => {
    nav.addEventListener('click', (e) => {
      const link = e.target.closest('.dashboard-nav-link');
      if (!link || !link.dataset.section) {
        return;
      }

      e.preventDefault();
      loadSection(link);
    });
  });

  const params = new URLSearchParams(window.location.search);
  const initialSection = params.get('section');
  if (initialSection) {
    const initialLink = document.querySelector(`.dashboard-nav-link[data-section="${initialSection}"]`);
    if (initialLink) {
      loadSection(initialLink, false);
    }
  }

  decorateMessages();
  fadeFlash();
});
