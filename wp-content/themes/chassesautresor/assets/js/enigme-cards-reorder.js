(function () {
  document.querySelectorAll('.cards-grid').forEach((grid) => {
    grid.querySelectorAll('.carte').forEach((card) => {
      card.draggable = true;
    });

    let dragged = null;

    grid.addEventListener('dragstart', (e) => {
      dragged = e.target.closest('.carte');
      if (dragged) {
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'move';
        }
        dragged.classList.add('dragging');
      }
    });

    grid.addEventListener('dragend', () => {
      if (dragged) {
        dragged.classList.remove('dragging');
      }
      dragged = null;
      grid.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
    });

    grid.addEventListener('dragover', (e) => {
      e.preventDefault();
      const target = e.target.closest('.carte');
      if (!dragged || !target || dragged === target) return;

      grid.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
      target.classList.add('drag-over');

      const rect = target.getBoundingClientRect();
      const shouldInsertAfter =
        e.clientY > rect.top + rect.height / 2 || e.clientX > rect.left + rect.width / 2;

      grid.insertBefore(dragged, shouldInsertAfter ? target.nextSibling : target);
    });

    grid.addEventListener('drop', (e) => {
      e.preventDefault();
      if (dragged) {
        dragged.classList.remove('dragging');
        dragged = null;
      }
      grid
        .querySelectorAll('.drag-over')
        .forEach((el) => el.classList.remove('drag-over'));
    });
  });
})();
