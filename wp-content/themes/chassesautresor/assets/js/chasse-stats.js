function initChasseStats() {
  const container = document.getElementById('chasse-stats');
  const select = document.getElementById('chasse-periode');
  if (!container || !select) {
    return;
  }

  const cards = {
    participants: container.querySelector('[data-stat="participants"] .stat-value'),
    tentatives: container.querySelector('[data-stat="tentatives"] .stat-value'),
    points: container.querySelector('[data-stat="points"] .stat-value'),
    engagementRate: container.querySelector('[data-stat="engagement-rate"] .stat-value'),
  };

  container.querySelectorAll('.stat-help').forEach((btn) => {
    btn.addEventListener('click', () => {
      const message = btn.dataset.message;
      if (message) {
        alert(message);
      }
    });
  });

  select.addEventListener('change', () => {
    const periode = select.value;
    const data = new FormData();
    data.append('action', 'chasse_recuperer_stats');
    data.append('chasse_id', ChasseStats.chasseId);
    data.append('periode', periode);

    fetch(ChasseStats.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: data,
    })
      .then((response) => response.json())
      .then((res) => {
        if (!res.success) {
          return;
        }
        const stats = res.data;
        if (cards.participants && typeof stats.participants !== 'undefined') {
          cards.participants.textContent = stats.participants;
        }
        if (cards.tentatives && typeof stats.tentatives !== 'undefined') {
          cards.tentatives.textContent = stats.tentatives;
        }
        if (cards.points && typeof stats.points !== 'undefined') {
          cards.points.textContent = stats.points;
        }
        if (cards.engagementRate && typeof stats.engagement_rate !== 'undefined') {
          cards.engagementRate.textContent = `${stats.engagement_rate}%`;
        }
      })
      .catch(() => {});
  });

  const participantsWrapper = document.querySelector('#chasse-tab-stats .liste-participants');
  if (participantsWrapper) {
    function charger(page = 1, orderby = participantsWrapper.dataset.orderby || 'inscription', order = participantsWrapper.dataset.order || 'asc') {
      fetch(ChasseStats.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'chasse_lister_participants',
          chasse_id: ChasseStats.chasseId,
          page,
          orderby,
          order,
        }),
      })
        .then((r) => r.json())
        .then((res) => {
          if (!res.success) return;
          participantsWrapper.innerHTML = res.data.html;
          participantsWrapper.dataset.page = res.data.page;
          participantsWrapper.dataset.pages = res.data.pages;
          participantsWrapper.dataset.order = order;
          participantsWrapper.dataset.orderby = orderby;
        });
    }

    participantsWrapper.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;
      if (btn.classList.contains('pager-first')) {
        e.preventDefault();
        charger(1);
      }
      if (btn.classList.contains('pager-prev')) {
        e.preventDefault();
        const page = parseInt(participantsWrapper.dataset.page || '1', 10);
        if (page > 1) charger(page - 1);
      }
      if (btn.classList.contains('pager-next')) {
        e.preventDefault();
        const page = parseInt(participantsWrapper.dataset.page || '1', 10);
        const pages = parseInt(participantsWrapper.dataset.pages || '1', 10);
        if (page < pages) charger(page + 1);
      }
      if (btn.classList.contains('pager-last')) {
        e.preventDefault();
        const pages = parseInt(participantsWrapper.dataset.pages || '1', 10);
        charger(pages);
      }
      if (btn.classList.contains('sort')) {
        e.preventDefault();
        const orderby = btn.dataset.orderby || 'inscription';
        let order = participantsWrapper.dataset.order || 'asc';
        if (participantsWrapper.dataset.orderby !== orderby) {
          order = 'asc';
        } else {
          order = order === 'asc' ? 'desc' : 'asc';
        }
        charger(1, orderby, order);
      }
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initChasseStats);
} else {
  initChasseStats();
}
