/*
 * Apply label styling to table columns marked with data-format="etiquette".
 */
document.addEventListener('DOMContentLoaded', () => {
  const i18n = window.TableEtiquetteI18N || {};
  const locale = document.documentElement.lang || 'en';

  document.querySelectorAll('.stats-table, .table-tentatives').forEach((table) => {
    table.querySelectorAll('th[data-format="etiquette"]').forEach((th) => {
      const col = th.dataset.col ? parseInt(th.dataset.col, 10) : th.cellIndex + 1;
      table.querySelectorAll(`tbody td:nth-child(${col})`).forEach((td) => {
        if (td.querySelector('.etiquette')) {
          return;
        }
        const text = td.textContent.trim();
        if (text === '') {
          return;
        }

        let labelText = text;
        if (text === 'programme' || text === 'programmé') {
          const dateAttr = td.dataset.date;
          const formattedDate = dateAttr ? new Date(dateAttr).toLocaleString(locale) : '';
          const base = i18n.programmedOn || 'Programmé le';
          labelText = `${base}${formattedDate ? ` ${formattedDate}` : ''}`;
        } else if (text === 'accessible') {
          labelText = i18n.accessible || 'Accessible';
        }

        td.innerHTML = `<span class="etiquette">${labelText}</span>`;
      });
    });
  });
});
