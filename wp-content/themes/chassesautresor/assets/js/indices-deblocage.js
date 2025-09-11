document.addEventListener('DOMContentLoaded', function () {
  var container = document.querySelector('.zone-indices .indice-display');
  if (!container) return;

  function displayContent(html) {
    container.innerHTML = html;
  }

  function fetchIndice(id, link) {
    var fd = new FormData();
    fd.append('action', 'debloquer_indice');
    fd.append('indice_id', id);
    fetch(indicesUnlock.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          displayContent(res.data.html);
          if (link) {
            link.dataset.unlocked = '1';
            link.classList.remove('indice-link--locked');
            link.classList.add('indice-link--unlocked');
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
      if (link.dataset.unlocked === '1') {
        fetchIndice(link.dataset.indiceId, link);
      } else {
        var cout = link.dataset.cout || '0';
        container.innerHTML = '<p>' + indicesUnlock.texts.unlock + ' - ' + cout + ' ' + indicesUnlock.texts.pts + '</p>'
          + '<button type="button" class="btn-debloquer-indice" data-indice-id="' + link.dataset.indiceId + '">'
          + indicesUnlock.texts.unlock + '</button>';
      }
      return;
    }

    var btn = e.target.closest('.btn-debloquer-indice');
    if (btn) {
      e.preventDefault();
      btn.disabled = true;
      var id = btn.dataset.indiceId;
      var linkSel = document.querySelector('.indice-link[data-indice-id="' + id + '"]');
      fetchIndice(id, linkSel);
    }
  });
});
