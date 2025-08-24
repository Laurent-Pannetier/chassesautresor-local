(function () {
  function openModal(btn) {
    var overlay = document.createElement('div');
    overlay.className = 'indice-modal-overlay';
    var titre = indicesCreate.texts.indiceTitre.replace('%d', btn.dataset.indiceRang || '');
    overlay.innerHTML = `
      <div class="indice-modal">
        <div class="indice-modal-header">
          <h2>${titre}</h2>
          <p>${indicesCreate.texts.lieA} - ${btn.dataset.objetTitre || ''}</p>
        </div>
        <button type="button" class="indice-modal-close" aria-label="${indicesCreate.texts.close}">×</button>
        <form class="indice-modal-form">
          <input type="hidden" name="action" value="creer_indice_modal" />
          <input type="hidden" name="objet_type" value="${btn.dataset.objetType}" />
          <input type="hidden" name="objet_id" value="${btn.dataset.objetId}" />
          <input type="hidden" name="indice_image" value="" />
          <p class="image-field"><button type="button" class="select-image">${indicesCreate.texts.image}</button><span class="image-preview"></span></p>
          <p><label>${indicesCreate.texts.contenu}<br><textarea name="indice_contenu"></textarea></label></p>
          <p><label><input type="radio" name="indice_disponibilite" value="immediate" checked /> ${indicesCreate.texts.immediate}</label>
             <label><input type="radio" name="indice_disponibilite" value="differe" /> ${indicesCreate.texts.differe}</label></p>
          <p class="date-wrapper" style="display:none;"><input type="datetime-local" name="indice_date_disponibilite" /></p>
          <div class="indice-modal-footer"><span class="indice-state-message"></span><button type="submit" class="indice-modal-validate bouton-cta">${indicesCreate.texts.valider}</button></div>
        </form>
      </div>`;
    document.body.appendChild(overlay);

    var dateInput = overlay.querySelector('input[name="indice_date_disponibilite"]');
    var defaultDate = (function () {
      var d = new Date();
      d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
      return d.toISOString().slice(0, 16);
    })();

    var isEdit = !!btn.dataset.indiceId;
    if (isEdit) {
      overlay.querySelector('input[name="action"]').value = 'modifier_indice_modal';
      var idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'indice_id';
      idInput.value = btn.dataset.indiceId;
      overlay.querySelector('.indice-modal-form').appendChild(idInput);
      if (btn.dataset.indiceImage) {
        overlay.querySelector('input[name="indice_image"]').value = btn.dataset.indiceImage;
        if (btn.dataset.indiceImageUrl) {
          renderPreview(btn.dataset.indiceImageUrl);
        }
      }
      if (btn.dataset.indiceContenu) {
        overlay.querySelector('textarea[name="indice_contenu"]').value = btn.dataset.indiceContenu;
      }
      var dispo = btn.dataset.indiceDisponibilite || 'immediate';
      overlay.querySelectorAll('input[name="indice_disponibilite"]').forEach(function (radio) {
        radio.checked = radio.value === dispo;
      });
      if (dispo === 'differe') {
        dateInput.value = btn.dataset.indiceDate || defaultDate;
        overlay.querySelector('.date-wrapper').style.display = '';
      }
    } else {
      dateInput.value = defaultDate;
    }
    var lastDateValue = dateInput.value;
    var validateBtn = overlay.querySelector('.indice-modal-validate');
    var stateMessage = overlay.querySelector('.indice-state-message');

    function close() {
      overlay.remove();
    }
    overlay.querySelector('.indice-modal-close').addEventListener('click', close);

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) close();
    });

    function renderPreview(url) {
      var preview = overlay.querySelector('.image-preview');
      if (!url) {
        preview.innerHTML = '';
        refreshState();
        return;
      }
      preview.innerHTML = '<img src="' + url + '" alt="" />' +
        '<span class="image-actions">' +
        '<button type="button" class="image-edit" aria-label="' + indicesCreate.texts.edit + '"><span class="dashicons dashicons-edit"></span></button>' +
        '<button type="button" class="image-remove" aria-label="' + indicesCreate.texts.remove + '"><span class="dashicons dashicons-no"></span></button>' +
        '</span>';
      preview.querySelector('.image-edit').addEventListener('click', function (e) {
        e.preventDefault();
        openMedia();
      });
      preview.querySelector('.image-remove').addEventListener('click', function (e) {
        e.preventDefault();
        overlay.querySelector('input[name="indice_image"]').value = '';
        renderPreview('');
      });
      refreshState();
    }

    function openMedia() {
      if (!window.wp || !window.wp.media) return;
      var frame = window.wp.media({ title: indicesCreate.texts.mediaTitle, multiple: false });
      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        overlay.querySelector('input[name="indice_image"]').value = attachment.id;
        var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
        renderPreview(url);
      });
      frame.open();
    }

    overlay.querySelectorAll('input[name="indice_disponibilite"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        var dateWrap = overlay.querySelector('.date-wrapper');
        if (radio.value === 'differe' && radio.checked) {
          dateWrap.style.display = '';
        } else {
          dateWrap.style.display = 'none';
        }
        refreshState();
      });
    });

    overlay.querySelector('.select-image').addEventListener('click', function (e) {
      e.preventDefault();
      openMedia();
    });

    overlay.querySelector('textarea[name="indice_contenu"]').addEventListener('input', refreshState);

    dateInput.addEventListener('input', function (e) {
      if (e.target.value) {
        lastDateValue = e.target.value;
      }
      refreshState();
    });

    dateInput.addEventListener('blur', function (e) {
      if (!e.target.value) {
        e.target.value = lastDateValue || defaultDate;
      }
      refreshState();
    });

    function refreshState() {
      var content = overlay.querySelector('textarea[name="indice_contenu"]').value.trim();
      var image = overlay.querySelector('input[name="indice_image"]').value.trim();
      var dispo = overlay.querySelector('input[name="indice_disponibilite"]:checked').value;
      var state = 'desactive';
      var message = '';
      var complete = content !== '' || image !== '';

      if (!complete) {
        message = indicesCreate.texts.needContent;
      } else {
        state = 'accessible';
        if (dispo === 'differe') {
          var dateVal = dateInput.value;
          if (!dateVal) {
            state = 'desactive';
            message = indicesCreate.texts.needDate;
          } else {
            var ts = Date.parse(dateVal);
            if (isNaN(ts)) {
              state = 'desactive';
              message = indicesCreate.texts.invalidDate;
            } else if (ts > Date.now()) {
              state = 'programme';
            }
          }
        }
      }

      validateBtn.disabled = !(state === 'accessible' || state === 'programme');
      stateMessage.textContent = message;
    }

    overlay.querySelector('.indice-modal-form').addEventListener('submit', function (e) {
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
          if (!btn.dataset.indiceId && btn.dataset.indiceRang) {
            btn.dataset.indiceRang = parseInt(btn.dataset.indiceRang, 10) + 1;
          }
        });
    });

    refreshState();
  }

  function handleClick(e) {
    var target = e.target;
    if (target && target.nodeType !== 1) {
      target = target.parentElement;
    }
    var placeholder = target && target.closest ? target.closest('.cta-indice-enigme') : null;
    if (placeholder) {
      e.preventDefault();
      return;
    }
    var btn = target && target.closest ? target.closest('.cta-creer-indice, .badge-action.edit') : null;
    if (!btn) return;
    e.preventDefault();
    openModal(btn);
  }

  window.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('click', handleClick, true);
  });

  window.openIndiceModal = openModal;
})();
