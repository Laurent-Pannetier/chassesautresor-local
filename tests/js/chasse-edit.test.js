const flush = () => new Promise(resolve =>  setTimeout(resolve, 0));
const html = `
  <div id="chasse-tab-param">
    <ul>
      <li class="edition-row champ-chasse champ-mode-fin" data-post-id="1">
        <div class="edition-row-label"><label for="chasse_mode_fin">Mode</label></div>
        <div class="edition-row-content">
          <div class="champ-mode-options">
            <label class="switch-control">
              <input type="checkbox" id="chasse_mode_fin" name="acf[chasse_mode_fin]" value="manuelle">
              <span class="switch-slider"></span>
            </label>
            <div class="fin-chasse-actions"></div>
          </div>
        </div>
      </li>
      <li class="edition-row champ-chasse champ-date-debut" data-post-id="1">
        <div class="edition-row-label"><label for="chasse-date-debut">Début</label></div>
        <div class="edition-row-content">
          <div class="champ-mode-options">
            <span class="toggle-option">Now</span>
            <label class="switch-control">
              <input type="checkbox" id="date-debut-differee">
              <span class="switch-slider"></span>
            </label>
            <span class="toggle-option">Later</span>
            <div class="date-debut-actions" style="display:none;">
              <input type="datetime-local" id="chasse-date-debut" value="" class="champ-inline-date champ-date-edit">
              <div id="erreur-date-debut" class="message-erreur"></div>
            </div>
          </div>
        </div>
      </li>
      <li class="edition-row champ-chasse champ-date-fin" data-post-id="1">
        <div class="edition-row-label"><label for="chasse-date-fin">Fin</label></div>
        <div class="edition-row-content">
          <div class="champ-mode-options">
            <span class="toggle-option">Illimitée</span>
            <label class="switch-control">
              <input type="checkbox" id="date-fin-limitee">
              <span class="switch-slider"></span>
            </label>
            <span class="toggle-option">Limitée</span>
            <div class="date-fin-actions" style="display:none;">
              <input type="date" id="chasse-date-fin" value="" class="champ-inline-date champ-date-edit">
              <div id="erreur-date-fin" class="message-erreur"></div>
            </div>
          </div>
        </div>
      </li>
      <li class="edition-row champ-chasse champ-cout-points" data-post-id="1">
        <div class="edition-row-label"><label>Accès</label></div>
        <div class="edition-row-content">
          <div class="champ-mode-options">
            <span class="toggle-option">Gratuit</span>
            <label class="switch-control">
              <input type="checkbox" id="cout-payant">
              <span class="switch-slider"></span>
            </label>
            <span class="toggle-option">Points</span>
            <div class="cout-points-actions" style="display:none;">
              <input type="number" value="0" class="champ-input champ-cout champ-number">
            </div>
          </div>
        </div>
      </li>
    </ul>
    <template id="template-nb-gagnants">
      <li class="edition-row champ-nb-gagnants" data-post-id="1">
        <div class="edition-row-label"><label for="chasse-nb-gagnants">Nb gagnants</label></div>
        <div class="edition-row-content">
          <div class="champ-mode-options">
            <span class="toggle-option">Illimité</span>
            <label class="switch-control">
              <input type="checkbox" id="nb-gagnants-limite">
              <span class="switch-slider"></span>
            </label>
            <span class="toggle-option">Limité</span>
            <div class="nb-gagnants-actions" style="display:none;">
              <input type="number" id="chasse-nb-gagnants" value="0" class="champ-input champ-number">
            </div>
          </div>
        </div>
      </li>
    </template>
    <template id="template-fin-chasse-actions">
      <button type="button" class="terminer-chasse-btn bouton-cta" data-post-id="1" data-cpt="chasse">Terminer la chasse</button>
      <div class="zone-validation-fin" style="display:none;">
        <label for="chasse-gagnants">Gagnants</label>
        <textarea id="chasse-gagnants"></textarea>
        <button type="button" class="valider-fin-chasse-btn bouton-cta" data-post-id="1" data-cpt="chasse" disabled>Valider la fin de chasse</button>
        <button type="button" class="annuler-fin-chasse-btn bouton-secondaire">Annuler</button>
      </div>
    </template>
  </div>
  <div id="chasse-tab-animation"></div>
`;

describe('chasse-edit UI', () => {
  beforeEach(() => {
    document.body.innerHTML = html;
    global.ajaxurl = '/ajax';
    global.initZonesClicEdition = jest.fn();
    global.initChampImage = jest.fn();
    global.initLiensChasse = jest.fn();
    global.initChampTexte = jest.fn();
    global.initChampDeclencheur = jest.fn();
    global.mettreAJourResumeInfos = jest.fn();
    global.mettreAJourCarteAjoutEnigme = jest.fn();
    global.mettreAJourEtatIntroChasse = jest.fn();
    global.initChampNbGagnants = jest.fn();
    global.initChampDate = jest.fn();
    global.mettreAJourAffichageDateFin = jest.fn();
    global.fetch = jest.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve({ success: true }) }));
    global.modifierChampSimple = jest.fn(() => Promise.resolve(true));
    global.wp = { i18n: { __: (s) => s } };
    jest.resetModules();
    require('../../wp-content/themes/chassesautresor/assets/js/chasse-edit.js');
  });

  test('manual termination block is in Paramètres tab', () => {
    const toggle = document.getElementById('chasse_mode_fin');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(document.querySelector('#chasse-tab-param .fin-chasse-actions .terminer-chasse-btn')).not.toBeNull();
    expect(document.querySelector('#chasse-tab-animation .fin-chasse-actions')).toBeNull();
  });

  test('changing termination mode saves field', async () => {
    const toggle = document.getElementById('chasse_mode_fin');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    await Promise.resolve();
    expect(global.modifierChampSimple).toHaveBeenCalledWith('chasse_mode_fin', 'manuelle', '1', 'chasse');
  });

  test('terminate button toggles with mode', () => {
    const actions = document.querySelector('.fin-chasse-actions');
    expect(actions.querySelector('.terminer-chasse-btn')).toBeNull();
    const toggle = document.getElementById('chasse_mode_fin');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.querySelector('.terminer-chasse-btn')).not.toBeNull();
    toggle.checked = false;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.querySelector('.terminer-chasse-btn')).toBeNull();
  });

  test('validating manual termination updates message', async () => {
    const toggle = document.getElementById('chasse_mode_fin');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    const fakeNow = new Date('2024-01-02');
    jest.spyOn(global, 'Date').mockImplementation(() => fakeNow);
    global.Date.now = () => fakeNow.getTime();
    const btn = document.querySelector('.terminer-chasse-btn');
    btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    const textarea = document.querySelector('#chasse-gagnants');
    textarea.value = 'Alice';
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    const valider = document.querySelector('.valider-fin-chasse-btn');
    valider.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    await flush();
    await flush();
    await flush();
    await flush();
    const message = document.querySelector('.message-chasse-terminee');
    expect(message).not.toBeNull();
    expect(message.textContent).toContain('Alice');
    expect(message.textContent).toContain('02/01/2024');
  });

  test('start date toggle reveals datepicker when checked', () => {
    const toggle = document.getElementById('date-debut-differee');
    const actions = document.querySelector('.date-debut-actions');
    const input = document.getElementById('chasse-date-debut');
    expect(actions.style.display).toBe('none');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.style.display).toBe('');
    expect(input.disabled).toBe(false);
    expect(global.initChampDate).toHaveBeenCalledWith(input);
  });

  test('start date toggle hides datepicker when unchecked', () => {
    const toggle = document.getElementById('date-debut-differee');
    const actions = document.querySelector('.date-debut-actions');
    const input = document.getElementById('chasse-date-debut');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    toggle.checked = false;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.style.display).toBe('none');
    expect(input.disabled).toBe(true);
  });

  test('end date toggle reveals datepicker when checked', () => {
    const toggle = document.getElementById('date-fin-limitee');
    const actions = document.querySelector('.date-fin-actions');
    const input = document.getElementById('chasse-date-fin');
    expect(actions.style.display).toBe('none');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.style.display).toBe('');
    expect(input.disabled).toBe(false);
    expect(global.initChampDate).toHaveBeenCalledWith(input);
  });

  test('end date toggle hides datepicker when unchecked', () => {
    const toggle = document.getElementById('date-fin-limitee');
    const actions = document.querySelector('.date-fin-actions');
    const input = document.getElementById('chasse-date-fin');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    toggle.checked = false;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.style.display).toBe('none');
    expect(input.disabled).toBe(true);
  });

  test('access toggle reveals points input when checked', () => {
    const toggle = document.getElementById('cout-payant');
    const actions = document.querySelector('.cout-points-actions');
    const input = document.querySelector('.champ-cout');
    expect(actions.style.display).toBe('none');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.style.display).toBe('');
    expect(input.disabled).toBe(false);
  });

  test('access toggle hides points input when unchecked', () => {
    const toggle = document.getElementById('cout-payant');
    const actions = document.querySelector('.cout-points-actions');
    const input = document.querySelector('.champ-cout');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    toggle.checked = false;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.style.display).toBe('none');
    expect(input.disabled).toBe(true);
  });
});
