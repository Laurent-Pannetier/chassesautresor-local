// Tri dynamique du tableau des organisateurs par colonne "\xC3\x89tat"
document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('.table-organisateurs');
  if (!table) return;
  const header = table.querySelector('th[data-col="etat"]');
  if (!header) return;
  header.style.cursor = 'pointer';
  let asc = true;
  header.addEventListener('click', () => {
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
      const va = a.querySelector('td[data-col="etat"]').textContent.trim();
      const vb = b.querySelector('td[data-col="etat"]').textContent.trim();
      return asc ? va.localeCompare(vb) : vb.localeCompare(va);
    });
    rows.forEach(r => tbody.appendChild(r));
    asc = !asc;
  });
});
