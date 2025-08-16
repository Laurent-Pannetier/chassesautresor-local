// ========================================
// 📁 myaccount.js
// Charge les sections admin via AJAX dans l'espace "Mon Compte".
// ========================================

document.addEventListener('DOMContentLoaded', () => {
  const adminNav = document.querySelector('.admin-nav');
  const content = document.querySelector('.myaccount-content');

  if (!adminNav || !content || typeof ctaMyAccount === 'undefined') {
    return;
  }

  const fadeFlash = () => {
    const flash = content.querySelector('.msg-important .flash');
    if (flash) {
      setTimeout(() => {
        flash.remove();
      }, 3000);
    }
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
      content.innerHTML = `<section class="msg-important">${messages}</section>` + data.data.html;
      fadeFlash();
      adminNav.querySelectorAll('.dashboard-nav-link').forEach((a) => a.classList.remove('active'));
      link.classList.add('active');
      document.dispatchEvent(
        new CustomEvent('myaccountSectionLoaded', { detail: { section } })
      );
      if (push) {
        window.history.pushState(null, '', link.href);
      } else {
        window.history.replaceState(null, '', '/mon-compte/');
      }
    } catch (err) {
      window.location.assign(link.href);
    }
  };

  adminNav.addEventListener('click', (e) => {
    const link = e.target.closest('.dashboard-nav-link');
    if (!link) {
      return;
    }

    e.preventDefault();
    loadSection(link);
  });

  const params = new URLSearchParams(window.location.search);
  const initialSection = params.get('section');
  if (initialSection) {
    const initialLink = adminNav.querySelector(`.dashboard-nav-link[data-section="${initialSection}"]`);
    if (initialLink) {
      loadSection(initialLink, false);
    }
  }

  fadeFlash();
});
