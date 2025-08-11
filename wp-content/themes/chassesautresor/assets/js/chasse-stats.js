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
      const url = new URL(window.location.href);
      url.searchParams.set('periode', periodeSelect.value);
      window.location.href = url.toString();
    });
  }
});
