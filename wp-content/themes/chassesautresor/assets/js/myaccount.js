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

  adminNav.addEventListener('click', async (e) => {
    const link = e.target.closest('.dashboard-nav-link');
    if (!link) {
      return;
    }

    e.preventDefault();
    const section = link.dataset.section;
    if (!section) {
      window.location.href = link.href;
      return;
    }

    const url = `${ctaMyAccount.ajaxUrl}?action=cta_load_admin_section&section=${section}`;

    try {
      const response = await fetch(url, { credentials: 'same-origin' });
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      const data = await response.json();
      if (!data.success) {
        throw new Error('Request failed');
      }
      content.innerHTML = '<section class="msg-important"></section>' + data.data.html;
      adminNav.querySelectorAll('.dashboard-nav-link').forEach((a) => a.classList.remove('active'));
      link.classList.add('active');
      window.history.pushState(null, '', link.href);
    } catch (err) {
      window.location.href = link.href;
    }
  });
});
