document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.badge-validation[data-tooltip]').forEach(btn => {
    const msg = btn.dataset.tooltip;
    if (!msg) return;
    const tooltip = document.createElement('div');
    tooltip.className = 'badge-validation__tooltip';
    tooltip.innerHTML = msg;
    btn.appendChild(tooltip);

    const hide = () => btn.classList.remove('show-tooltip');
    const show = () => btn.classList.add('show-tooltip');

    btn.addEventListener('mouseenter', show);
    btn.addEventListener('mouseleave', hide);
    btn.addEventListener('click', e => {
      e.preventDefault();
      btn.classList.toggle('show-tooltip');
    });
    btn.addEventListener('blur', hide);
  });

  document.addEventListener('click', e => {
    document.querySelectorAll('.badge-validation.show-tooltip').forEach(btn => {
      if (!btn.contains(e.target)) {
        btn.classList.remove('show-tooltip');
      }
    });
  });
});
