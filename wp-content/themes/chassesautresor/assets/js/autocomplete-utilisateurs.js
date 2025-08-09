
(function () {
  const DEBUG = window.DEBUG || false;
  const AJAX_URL =
    window.ajaxurl || (window.ajax_object ? window.ajax_object.ajax_url : null);

  function initAutocompleteUtilisateurs(input) {
    if (!input) {
      DEBUG && console.log('❌ Élément introuvable');
      return;
    }
    if (!AJAX_URL) {
      DEBUG && console.warn('❌ URL AJAX non définie');
      return;
    }
    DEBUG && console.log('✅ initAutocompleteUtilisateurs', input);

    const list = document.createElement('ul');
    list.className = 'suggestions-list';
    list.style.position = 'absolute';
    list.style.background = 'white';
    list.style.border = '1px solid #ccc';
    list.style.width = input.offsetWidth + 'px';
    list.style.maxHeight = '200px';
    list.style.overflowY = 'auto';
    list.style.display = 'none';
    list.style.zIndex = '1000';
    input.parentNode.insertBefore(list, input.nextSibling);

    input.addEventListener('input', () => {
      input.dataset.userId = '';
      const term = input.value.trim();
      if (term.length < 2) {
        list.innerHTML = '';
        list.style.display = 'none';
        return;
      }

      fetch(
        `${AJAX_URL}?action=rechercher_utilisateur&term=${encodeURIComponent(
          term
        )}`
      )
        .then((res) => res.json())
        .then((data) => {
          list.innerHTML = '';
          if (data.success && data.data.length > 0) {
            list.style.display = 'block';
            data.data.forEach((user) => {
              const item = document.createElement('li');
              item.textContent = user.text;
              item.dataset.userId = user.id;
              item.style.padding = '8px';
              item.style.cursor = 'pointer';
              item.style.listStyle = 'none';
              item.addEventListener('click', () => {
                input.value = user.text;
                input.dataset.userId = user.id;
                list.innerHTML = '';
                list.style.display = 'none';
              });
              list.appendChild(item);
            });
          } else {
            list.style.display = 'none';
          }
        })
        .catch(() => {
          list.style.display = 'none';
        });
    });

    document.addEventListener('click', (e) => {
      if (e.target !== input && e.target.parentNode !== list) {
        list.style.display = 'none';
      }
    });
  }

  window.initAutocompleteUtilisateurs = initAutocompleteUtilisateurs;

  document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('utilisateur-points');
    if (input) {
      initAutocompleteUtilisateurs(input);
    }
  });
})();
