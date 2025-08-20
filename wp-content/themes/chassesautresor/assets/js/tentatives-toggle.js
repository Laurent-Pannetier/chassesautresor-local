(function () {
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.toggle-proposition');
    if (!btn) {
      return;
    }
    e.preventDefault();
    var cell = btn.closest('.proposition-cell');
    if (!cell) {
      return;
    }
    var excerpt = cell.querySelector('.proposition-excerpt');
    var full = cell.querySelector('.proposition-full');
    var expanded = btn.getAttribute('aria-expanded') === 'true';
    if (expanded) {
      if (full) full.hidden = true;
      if (excerpt) excerpt.hidden = false;
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', btn.dataset.more || 'Voir plus');
      btn.innerHTML = '<i class="fa-solid fa-ellipsis" aria-hidden="true"></i>';
    } else {
      if (full) full.hidden = false;
      if (excerpt) excerpt.hidden = true;
      btn.setAttribute('aria-expanded', 'true');
      btn.setAttribute('aria-label', btn.dataset.less || 'Voir moins');
      btn.innerHTML = '<i class="fa-solid fa-minus" aria-hidden="true"></i>';
    }
  });
})();
