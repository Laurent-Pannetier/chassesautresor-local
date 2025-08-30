/**
 * Reorder enigme cards within the cards grid.
 */
function initEnigmeCardsReorder() {
  const grid = document.querySelector('.cards-grid');
  if (!grid) return;

  const addCard = grid.querySelector('#carte-ajout-enigme');
  const addWrapper = addCard?.closest('.carte-ajout-wrapper');
  let dragged = null;

  grid.addEventListener('dragstart', (e) => {
    const card = e.target.closest('.carte-enigme');
    if (!card || card.id === 'carte-ajout-enigme') return;
    dragged = card;
    e.dataTransfer.effectAllowed = 'move';
    grid.classList.add('dragging');
    dragged.classList.add('dragging');
  });

  grid.addEventListener('dragover', (e) => {
    e.preventDefault();
    if (!dragged) return;
    e.dataTransfer.dropEffect = 'move';
    const target = e.target.closest('.carte-enigme');
    if (!target || target === dragged || target.id === 'carte-ajout-enigme') return;
    grid.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
    target.classList.add('drag-over');
    const rect = target.getBoundingClientRect();
    const next = e.clientY > rect.top + rect.height / 2;
    grid.insertBefore(dragged, next ? target.nextSibling : target);
  });

  const ensureAddLast = () => {
    if (addWrapper) {
      grid.appendChild(addWrapper);
    } else if (addCard) {
      grid.appendChild(addCard);
    }
  };

  const saveOrder = () => {
    const order = Array.from(
      grid.querySelectorAll('.carte-enigme[data-enigme-id]')
    ).map((el) => el.dataset.enigmeId);
    if (!order.length) return;
    const fd = new FormData();
    fd.append('action', 'reordonner_enigmes');
    fd.append('chasse_id', grid.dataset.chasseId);
    order.forEach((id) => fd.append('ordre[]', id));
    fetch(window.ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      body: fd,
    })
      .then((r) => r.json())
      .then((res) => {
        if (!res.success) {
          alert(wp.i18n.__("Erreur lors de l'enregistrement de l'ordre", 'chassesautresor-com'));
        }
      })
      .catch(() => {
        alert(wp.i18n.__("Erreur lors de l'enregistrement de l'ordre", 'chassesautresor-com'));
      });
  };

  const cleanClasses = () => {
    grid.classList.remove('dragging');
    grid.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
    dragged?.classList.remove('dragging');
  };

  grid.addEventListener('drop', (e) => {
    e.preventDefault();
    cleanClasses();
    ensureAddLast();
    saveOrder();
    dragged = null;
  });

  grid.addEventListener('dragend', () => {
    cleanClasses();
    ensureAddLast();
    saveOrder();
    dragged = null;
  });
}

document.addEventListener('DOMContentLoaded', initEnigmeCardsReorder);
