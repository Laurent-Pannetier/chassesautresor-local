document.addEventListener('DOMContentLoaded', function () {
  function displayContent(container, html) {
    container.innerHTML = '<button type="button" class="indice-close" aria-label="' + indicesUnlock.texts.close + '">&times;</button>' + html;
  }

  function fetchIndice(id, link, container) {
    var fd = new FormData();
    fd.append('action', 'debloquer_indice');
    fd.append('indice_id', id);
    fetch(indicesUnlock.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          displayContent(container, res.data.html);
          if (window.jQuery && typeof jQuery.fn.fancybox === 'function') {
            jQuery(container).find('a.fancybox').fancybox();
          }
          if (link) {
            link.dataset.unlocked = '1';
            link.classList.remove('indice-link--locked');
            link.classList.add('indice-link--unlocked');
            var icon = link.querySelector('i');
            if (icon) {
              icon.classList.remove('fa-lightbulb', 'fa-hourglass');
              icon.classList.add('fa-eye');
            }
          }
          if (res.data.points !== undefined) {
            var solde = document.querySelector('.participation-infos .solde');
            if (solde) {
              solde.textContent = indicesUnlock.texts.solde + ' : ' + res.data.points + ' ' + indicesUnlock.texts.pts;
            }
          }
        }
      });
  }

  document.body.addEventListener('click', function (e) {
    var link = e.target.closest('.indice-link');
    if (link) {
      e.preventDefault();
      var zone = link.closest('.zone-indices');
      var container = zone ? zone.querySelector('.indice-display') : null;
      if (!container) return;
      var cout = link.dataset.cout || '0';
      if (link.dataset.unlocked === '1' || cout === '0') {
        fetchIndice(link.dataset.indiceId, link, container);
      } else {
        var html = '<p>' + indicesUnlock.texts.unlock + ' - ' + cout + ' ' + indicesUnlock.texts.pts + '</p>'
          + '<button type="button" class="btn-debloquer-indice" data-indice-id="' + link.dataset.indiceId + '">'
          + indicesUnlock.texts.unlock + '</button>';
        displayContent(container, html);
      }
      return;
    }

    var closeBtn = e.target.closest('.indice-close');
    if (closeBtn) {
      e.preventDefault();
      var closeContainer = closeBtn.closest('.indice-display');
      if (closeContainer) {
        closeContainer.innerHTML = '';
      }
      return;
    }

    var btn = e.target.closest('.btn-debloquer-indice');
    if (btn) {
      e.preventDefault();
      btn.disabled = true;
      var zoneBtn = btn.closest('.zone-indices');
      var containerBtn = zoneBtn ? zoneBtn.querySelector('.indice-display') : null;
      var id = btn.dataset.indiceId;
      var linkSel = zoneBtn ? zoneBtn.querySelector('.indice-link[data-indice-id="' + id + '"]') : null;
      if (containerBtn) {
        fetchIndice(id, linkSel, containerBtn);
      }
    }
  });
});
