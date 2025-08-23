(function () {
  document.addEventListener('pager:change', function (e) {
    var pager = e.target;
    if (!pager.classList.contains('indices-pager')) {
      return;
    }
    var wrapper = pager.closest('.liste-indices');
    if (!wrapper) {
      return;
    }
    var page = e.detail.page || 1;
    var ajaxUrl = wrapper.dataset.ajaxUrl;
    var objetId = wrapper.dataset.objetId;
    var objetType = wrapper.dataset.objetType;

    var formData = new FormData();
    formData.append('action', 'indices_lister_table');
    formData.append('objet_id', objetId);
    formData.append('objet_type', objetType);
    formData.append('page', String(page));

    fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.success) return;
        wrapper.innerHTML = res.data.html;
        wrapper.dataset.page = String(res.data.page);
        wrapper.dataset.pages = String(res.data.pages);
      });
  });
})();

