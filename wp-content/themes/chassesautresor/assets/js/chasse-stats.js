document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('#chasse-stats-table');
  if (table) {
    table.querySelectorAll('th.sortable').forEach((th) => {
      th.addEventListener('click', () => {
        const index = th.cellIndex;
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const asc = th.classList.toggle('asc');
        rows.sort((a, b) => {
          const aText = a.children[index].textContent.trim();
          const bText = b.children[index].textContent.trim();
          const aNum = parseInt(aText, 10);
          const bNum = parseInt(bText, 10);
          let comp;
          if (!isNaN(aNum) && !isNaN(bNum)) {
            comp = aNum - bNum;
          } else {
            comp = aText.localeCompare(bText);
          }
          return asc ? comp : -comp;
        });
        rows.forEach((row) => tbody.appendChild(row));
      });
    });
  }

  const periodeSelect = document.querySelector('#chasse-periode');
  if (periodeSelect) {
    periodeSelect.addEventListener('change', () => {
      const data = new FormData();
      data.append('action', 'chasse_recuperer_stats');
      data.append('chasse_id', ChasseStats.chasseId);
      data.append('periode', periodeSelect.value);

      fetch(ChasseStats.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: data,
      })
        .then((response) => response.json())
        .then((res) => {
          if (!res.success) return;
          const { kpis, detail } = res.data;
          const kpiEls = document.querySelectorAll('.kpi-card .kpi-value');
          kpiEls[0].textContent = kpis.joueurs_engages;
          kpiEls[1].textContent = kpis.points_depenses;
          kpiEls[2].textContent = kpis.indices_debloques;

          const tbody = table.querySelector('tbody');
          tbody.innerHTML = '';
          detail.forEach((row) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td><a href="${row.edit_url}">${row.titre}</a></td>` +
              `<td>${row.joueurs}</td>` +
              `<td>${row.tentatives}</td>` +
              `<td>${row.points}</td>` +
              `<td>${row.resolus}</td>`;
            tbody.appendChild(tr);
          });
        });
    });
  }
});
