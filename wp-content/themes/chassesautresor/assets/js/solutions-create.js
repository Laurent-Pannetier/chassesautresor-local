(function () {
  function openModal(btn) {
    var overlay = document.createElement('div');
    overlay.className = 'solution-modal-overlay';
    var isEdit = !!btn.dataset.solutionId;
    var needEnigme = btn.dataset.chasseId && !btn.dataset.objetId;
    var enigmeField = needEnigme
      ? '<p><label>' +
        solutionsCreate.texts.enigmeLabel +
        '<br><select name="solution_enigme_linked"><option value="">' +
        solutionsCreate.texts.loading +
        '</option></select></label></p>'
      : '';
    overlay.innerHTML = `
      <div class="solution-modal">
        <button type="button" class="solution-modal-close" aria-label="${solutionsCreate.texts.close}">×</button>
        <form class="solution-modal-form">
          <input type="hidden" name="action" value="${isEdit ? 'modifier_solution_modal' : 'creer_solution_modal'}" />
          <input type="hidden" name="objet_type" value="${btn.dataset.objetType || ''}" />
          ${enigmeField}
          <input type="hidden" name="objet_id" value="${btn.dataset.objetId || ''}" />
          ${isEdit ? '<input type="hidden" name="solution_id" value="' + btn.dataset.solutionId + '" />' : ''}
          <p><label>${solutionsCreate.texts.contenu}<br><textarea name="solution_explication">${btn.dataset.solutionExplication || ''}</textarea></label></p>
          <p><label>${solutionsCreate.texts.fichier}<br><input type="file" name="solution_fichier" accept="application/pdf" /></label></p>
          <p><label>${solutionsCreate.texts.disponibilite}<br><select name="solution_disponibilite">
            <option value="fin_chasse">${solutionsCreate.texts.finChasse}</option>
            <option value="differee">${solutionsCreate.texts.differee}</option>
          </select></label></p>
          <p class="delai-wrapper" style="display:none;">
            <input type="number" name="solution_delai" min="0" value="${btn.dataset.solutionDelai || 0}" /> ${solutionsCreate.texts.days}
            <input type="time" name="solution_heure" value="${btn.dataset.solutionHeure || '18:00'}" />
          </p>
          <div class="solution-modal-footer"><span class="solution-state-message"></span><button type="submit" class="solution-modal-validate bouton-cta">${solutionsCreate.texts.valider}</button></div>
        </form>
      </div>`;
    document.body.appendChild(overlay);

    function close() { overlay.remove(); }
    overlay.querySelector('.solution-modal-close').addEventListener('click', close);
    overlay.addEventListener('click', function(e){ if(e.target === overlay) close(); });

    var form = overlay.querySelector('.solution-modal-form');
    var validateBtn = overlay.querySelector('.solution-modal-validate');
    var stateMessage = overlay.querySelector('.solution-state-message');
    var selectDispo = overlay.querySelector('select[name="solution_disponibilite"]');
    var delaiWrapper = overlay.querySelector('.delai-wrapper');
    if (btn.dataset.solutionDisponibilite === 'differee') {
      selectDispo.value = 'differee';
      delaiWrapper.style.display = '';
    }
    selectDispo.addEventListener('change', function () {
      delaiWrapper.style.display = this.value === 'differee' ? '' : 'none';
    });

    if (needEnigme) {
      var selectEnigme = overlay.querySelector('select[name="solution_enigme_linked"]');
      var hiddenObjet = overlay.querySelector('input[name="objet_id"]');
      var fd = new FormData();
      fd.append('action', 'chasse_lister_enigmes');
      fd.append('chasse_id', btn.dataset.chasseId || '');
      fetch(solutionsCreate.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          selectEnigme.innerHTML = '';
          if (!res.success || !res.data.enigmes.length) {
            var opt = document.createElement('option');
            opt.value = '';
            opt.textContent = solutionsCreate.texts.enigmePlaceholder;
            selectEnigme.appendChild(opt);
            btn.dataset.objetId = '';
            btn.dataset.objetTitre = '';
            hiddenObjet.value = '';
            return;
          }
          res.data.enigmes.forEach(function (enigme) {
            var opt = document.createElement('option');
            opt.value = enigme.id;
            opt.textContent = enigme.title;
            selectEnigme.appendChild(opt);
          });
          var def = btn.dataset.defaultEnigme;
          if (def) selectEnigme.value = def;
          if (!selectEnigme.value) selectEnigme.value = selectEnigme.options[0].value;
          var sel = selectEnigme.options[selectEnigme.selectedIndex];
          btn.dataset.objetId = selectEnigme.value;
          btn.dataset.objetTitre = sel ? sel.text : '';
          hiddenObjet.value = selectEnigme.value;
        });
      selectEnigme.addEventListener('change', function () {
        var opt = selectEnigme.options[selectEnigme.selectedIndex];
        btn.dataset.objetId = selectEnigme.value;
        btn.dataset.objetTitre = opt ? opt.text : '';
        hiddenObjet.value = selectEnigme.value;
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (needEnigme && (!btn.dataset.objetId || !overlay.querySelector('input[name="objet_id"]').value)) {
        stateMessage.textContent = solutionsCreate.texts.needEnigme;
        return;
      }
      var data = new FormData(form);
      validateBtn.disabled = true;
      stateMessage.textContent = solutionsCreate.texts.loading;
      fetch(solutionsCreate.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          validateBtn.disabled = false;
          if (res.success) {
            stateMessage.textContent = solutionsCreate.texts.success;
            window.dispatchEvent(new Event('solution-created'));
            setTimeout(close, 500);
          } else {
            stateMessage.textContent = res.data || solutionsCreate.texts.ajaxError;
          }
        })
        .catch(function () {
          validateBtn.disabled = false;
          stateMessage.textContent = solutionsCreate.texts.ajaxError;
        });
    });
  }

  document.addEventListener('click', function(e){
    var btn = e.target.closest('.ajouter-solution, .badge-action.edit');
    if(!btn) return;
    e.preventDefault();
    openModal(btn);
  });
})();
