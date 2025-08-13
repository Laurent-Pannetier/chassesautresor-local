document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('enigme-stats');
  const select = document.getElementById('enigme-periode');
  if (!container || !select) {
    return;
  }

  const cards = {
    joueurs: container.querySelector('[data-stat="joueurs"]'),
    tentatives: container.querySelector('[data-stat="tentatives"]'),
    points: container.querySelector('[data-stat="points"]'),
    solutions: container.querySelector('[data-stat="solutions"]'),
  };

  function updateValues(stats) {
    if (cards.joueurs) {
      cards.joueurs.querySelector('.stat-value').textContent = stats.joueurs ?? 0;
    }
    if (cards.tentatives) {
      cards.tentatives.querySelector('.stat-value').textContent = stats.tentatives ?? 0;
    }
    if (cards.points) {
      cards.points.querySelector('.stat-value').textContent = stats.points ?? 0;
    }
    if (cards.solutions) {
      cards.solutions.querySelector('.stat-value').textContent = stats.solutions ?? 0;
    }
  }

  function fetchStats() {
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
        if (!res.success) return;
        updateValues(res.data);
      })
      .catch(() => {});
  }

  function updateVisibility() {
    const mode = document.querySelector('input[name="acf[enigme_mode_validation]"]:checked')?.value || 'aucune';
    const coutInput = document.getElementById('enigme-tentative-cout');
    let cout = parseInt(coutInput?.value || '0', 10);
    if (isNaN(cout)) cout = 0;
    if (document.getElementById('cout-gratuit-enigme')?.checked) {
      cout = 0;
    }
    if (cards.tentatives) {
      cards.tentatives.classList.toggle('cache', mode === 'aucune');
    }
    if (cards.solutions) {
      cards.solutions.classList.toggle('cache', mode === 'aucune');
    }
    if (cards.points) {
      cards.points.classList.toggle('cache', mode === 'aucune' || cout <= 0);
    }
  }

  updateVisibility();

  const originalHook = window.onChampSimpleMisAJour;
  window.onChampSimpleMisAJour = function (champ, postId, valeur, cpt) {
    if (typeof originalHook === 'function') {
      originalHook(champ, postId, valeur, cpt);
    }
    if (
      cpt === 'enigme' &&
      (champ === 'enigme_mode_validation' || champ.includes('enigme_tentative_cout_points'))
    ) {
      updateVisibility();
      fetchStats();
    }
  };

  select.addEventListener('change', fetchStats);
});

