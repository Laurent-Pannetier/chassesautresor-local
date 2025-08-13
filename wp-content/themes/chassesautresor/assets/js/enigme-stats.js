document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('enigme-stats');
  const select = document.getElementById('enigme-periode');
  if (!container || !select) {
    return;
  }

  const cards = {
    joueurs: container.querySelector('[data-stat="joueurs"] .stat-value'),
    tentatives: container.querySelector('[data-stat="tentatives"] .stat-value'),
    points: container.querySelector('[data-stat="points"] .stat-value'),
    solutions: container.querySelector('[data-stat="solutions"] .stat-value'),
  };

  select.addEventListener('change', () => {
    const periode = select.value;

    const data = new FormData();
    data.append('action', 'enigme_recuperer_stats');
    data.append('enigme_id', EnigmeStats.enigmeId);
    data.append('periode', periode);

    fetch(EnigmeStats.ajaxUrl, {
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
        if (cards.joueurs && typeof stats.joueurs !== 'undefined') {
          cards.joueurs.textContent = stats.joueurs;
        }
        if (cards.tentatives && typeof stats.tentatives !== 'undefined') {
          cards.tentatives.textContent = stats.tentatives;
        }
        if (cards.points && typeof stats.points !== 'undefined') {
          cards.points.textContent = stats.points;
        }
        if (cards.solutions && typeof stats.solutions !== 'undefined') {
          cards.solutions.textContent = stats.solutions;
        }
      })
      .catch(() => {});
  });
});

