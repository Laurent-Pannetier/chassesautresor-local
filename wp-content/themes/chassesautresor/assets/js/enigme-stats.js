document.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('enigme-stats-table');
  const select = document.getElementById('enigme-periode');
  if (!table || !select) {
    return;
  }

  const headerLabel = table.querySelector('.periode-label');
  const rows = {
    joueurs: table.querySelector('tr[data-stat="joueurs"] td:last-child'),
    tentatives: table.querySelector('tr[data-stat="tentatives"] td:last-child'),
    points: table.querySelector('tr[data-stat="points"] td:last-child'),
    solutions: table.querySelector('tr[data-stat="solutions"] td:last-child'),
  };

  const labels = {
    total: 'Total',
    jour: "Aujourd\u2019hui",
    semaine: 'Semaine',
    mois: 'Mois',
  };

  select.addEventListener('change', () => {
    const periode = select.value;
    headerLabel.textContent = labels[periode] || 'Total';

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
        if (rows.joueurs && typeof stats.joueurs !== 'undefined') {
          rows.joueurs.textContent = stats.joueurs;
        }
        if (rows.tentatives && typeof stats.tentatives !== 'undefined') {
          rows.tentatives.textContent = stats.tentatives;
        }
        if (rows.points && typeof stats.points !== 'undefined') {
          rows.points.textContent = stats.points;
        }
        if (rows.solutions && typeof stats.solutions !== 'undefined') {
          rows.solutions.textContent = stats.solutions;
        }
      })
      .catch(() => {});
  });
});

