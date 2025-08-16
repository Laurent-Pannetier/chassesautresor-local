/*
 * Apply label styling to table columns marked with data-format="etiquette".
 */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.stats-table, .table-tentatives').forEach((table) => {
    table.querySelectorAll('th[data-format]').forEach((th) => {
      const formats = th.dataset.format.split(' ');
      if (!formats.includes('etiquette')) {
        return;
      }

      const col = th.dataset.col ? parseInt(th.dataset.col, 10) : th.cellIndex + 1;
      table.querySelectorAll(`tbody td:nth-child(${col})`).forEach((td) => {
        if (td.querySelector('.etiquette')) {
          return;
        }
        const text = td.textContent.trim();
        if (text !== '') {
          const extra = formats.includes('grande') ? ' grande' : '';
          td.innerHTML = `<span class="etiquette${extra}">${text}</span>`;
        }
      });
    });
  });
});
