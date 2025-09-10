document.addEventListener('DOMContentLoaded', function () {
  document.body.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-debloquer-indice');
    if (!btn) return;
    e.preventDefault();
    var id = btn.dataset.indiceId;
    if (!id) return;
    btn.disabled = true;
    var fd = new FormData();
    fd.append('action', 'debloquer_indice');
    fd.append('indice_id', id);
    fetch(indicesUnlock.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          var li = btn.closest('li');
          if (li) {
            li.innerHTML = res.data.html;
          }
          if (res.data.points !== undefined) {
            var solde = document.querySelector('.participation-infos .solde');
            if (solde) {
              solde.textContent = indicesUnlock.texts.solde + ' : ' + res.data.points + ' ' + indicesUnlock.texts.pts;
            }
          }
        } else {
          btn.disabled = false;
        }
      })
      .catch(function () {
        btn.disabled = false;
      });
  });
});
