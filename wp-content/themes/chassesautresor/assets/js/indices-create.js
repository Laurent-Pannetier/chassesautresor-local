(function () {
  function openModal(btn) {
    var overlay = document.createElement('div');
    overlay.className = 'indice-create-overlay';
    overlay.innerHTML = `
      <div class="indice-create-modal">
        <button type="button" class="indice-create-close" aria-label="${indicesCreate.texts.close}">×</button>
        <form class="indice-create-form">
          <input type="hidden" name="action" value="creer_indice_modal" />
          <input type="hidden" name="objet_type" value="${btn.dataset.objetType}" />
          <input type="hidden" name="objet_id" value="${btn.dataset.objetId}" />
          <input type="hidden" name="indice_image" value="" />
          <p><button type="button" class="select-image">${indicesCreate.texts.image}</button></p>
          <p><label>${indicesCreate.texts.contenu}<br><textarea name="indice_contenu"></textarea></label></p>
          <p><label><input type="radio" name="indice_disponibilite" value="immediate" checked /> ${indicesCreate.texts.immediate}</label>
             <label><input type="radio" name="indice_disponibilite" value="differe" /> ${indicesCreate.texts.differe}</label></p>
          <p class="date-wrapper" style="display:none;"><input type="datetime-local" name="indice_date_disponibilite" /></p>
          <p><button type="submit" class="indice-create-validate">${indicesCreate.texts.valider}</button></p>
        </form>
      </div>`;
    document.body.appendChild(overlay);

    function close() {
      overlay.remove();
    }
    overlay.querySelector('.indice-create-close').addEventListener('click', close);

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) close();
    });

    overlay.querySelectorAll('input[name="indice_disponibilite"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        var dateWrap = overlay.querySelector('.date-wrapper');
        if (radio.value === 'differe' && radio.checked) {
          dateWrap.style.display = '';
        } else {
          dateWrap.style.display = 'none';
        }
      });
    });

    overlay.querySelector('.select-image').addEventListener('click', function (e) {
      e.preventDefault();
      if (!window.wp || !window.wp.media) return;
      var frame = window.wp.media({ title: indicesCreate.texts.mediaTitle, multiple: false });
      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        overlay.querySelector('input[name="indice_image"]').value = attachment.id;
      });
      frame.open();
    });

    overlay.querySelector('.indice-create-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var form = e.target;
      var data = new FormData(form);
      fetch(indicesCreate.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res.success) return;
          close();
          var selector = '.liste-indices[data-objet-type="' + btn.dataset.objetType + '"][data-objet-id="' + btn.dataset.objetId + '"]';
          var wrapper = document.querySelector(selector);
          if (wrapper && window.reloadIndicesTable) {
            wrapper.dataset.page = '1';
            window.reloadIndicesTable(wrapper);
          }
        });
    });
  }

  function handleClick(e) {
    var target = e.target;
    if (target && target.nodeType !== 1) {
      target = target.parentElement;
    }
    var btn = target && target.closest ? target.closest('.cta-creer-indice') : null;
    if (!btn) return;
    e.preventDefault();
    openModal(btn);
  }

  window.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('click', handleClick, true);
  });

  window.openIndiceCreateModal = openModal;
})();
