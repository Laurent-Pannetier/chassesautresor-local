const flush = () => new Promise(resolve =>  setTimeout(resolve, 0));
const html = `
  <div id="chasse-tab-param">
    <ul>
      <li class="edition-row champ-chasse champ-mode-fin" data-post-id="1">
        <div class="edition-row-label"><label>Mode</label></div>
        <div class="edition-row-content">
          <div class="champ-mode-options">
            <input type="radio" name="acf[chasse_mode_fin]" value="automatique" checked>
            <input type="radio" name="acf[chasse_mode_fin]" value="manuelle">
            <div class="fin-chasse-actions"></div>
          </div>
        </div>
      </li>
    </ul>
    <template id="template-nb-gagnants">
      <li class="edition-row champ-nb-gagnants" data-post-id="1">
        <div class="edition-row-label"><label for="chasse-nb-gagnants">Nb gagnants</label></div>
        <div class="edition-row-content">
          <input type="number" id="chasse-nb-gagnants" value="0" class="champ-input champ-number">
          <input type="checkbox" id="nb-gagnants-illimite">
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
  <div id="chasse-tab-animation">
    <div class="dashboard-card carte-orgy champ-visuels">
      <p id="visuels-texte">Texte à copier</p>
      <button type="button" class="copy-message" data-target="#visuels-texte">Copier</button>
      <p id="visuels-complet">Message complet</p>
      <button type="button" class="copy-message" data-target="#visuels-complet">Copier</button>
    </div>
  </div>
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
    global.modifierChampSimple = jest.fn(() => Promise.resolve(true));
    global.wp = { i18n: { __: (s) => s } };
    global.navigator = global.navigator || {};
    global.navigator.clipboard = { writeText: jest.fn().mockResolvedValue(undefined) };
    jest.resetModules();
    require('../../wp-content/themes/chassesautresor/assets/js/chasse-edit.js');
  });

  test('manual termination block is in Paramètres tab', () => {
    const radio = document.querySelector('input[value="manuelle"]');
    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));
    expect(document.querySelector('#chasse-tab-param .fin-chasse-actions .terminer-chasse-btn')).not.toBeNull();
    expect(document.querySelector('#chasse-tab-animation .fin-chasse-actions')).toBeNull();
  });

  test('changing termination mode saves field', async () => {
    const radio = document.querySelector('input[value="manuelle"]');
    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));
    await Promise.resolve();
    expect(global.modifierChampSimple).toHaveBeenCalledWith('chasse_mode_fin', 'manuelle', '1', 'chasse');
  });

  test('terminate button toggles with mode', () => {
    const actions = document.querySelector('.fin-chasse-actions');
    expect(actions.querySelector('.terminer-chasse-btn')).toBeNull();
    const manual = document.querySelector('input[value="manuelle"]');
    manual.checked = true;
    manual.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.querySelector('.terminer-chasse-btn')).not.toBeNull();
    const auto = document.querySelector('input[value="automatique"]');
    auto.checked = true;
    auto.dispatchEvent(new Event('change', { bubbles: true }));
    expect(actions.querySelector('.terminer-chasse-btn')).toBeNull();
  });

  test('validating manual termination updates message', async () => {
    const radio = document.querySelector('input[value="manuelle"]');
    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));
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

  test('visuels block present and copy works', () => {
    const bloc = document.querySelector('#chasse-tab-animation .champ-visuels');
    expect(bloc).not.toBeNull();
    const btn = bloc.querySelector('button[data-target="#visuels-texte"]');
    btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    expect(global.navigator.clipboard.writeText).toHaveBeenCalledWith('Texte à copier');
  });

  test('copy works for dynamically added button', () => {
    const newBtn = document.createElement('button');
    newBtn.type = 'button';
    newBtn.className = 'copy-message';
    newBtn.dataset.target = '#visuels-texte';
    document.querySelector('#chasse-tab-animation .champ-visuels').appendChild(newBtn);
    newBtn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    expect(global.navigator.clipboard.writeText).toHaveBeenCalledWith('Texte à copier');
  });
});
