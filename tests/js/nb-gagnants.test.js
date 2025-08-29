describe('initChampNbGagnants', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <li class="edition-row champ-nb-gagnants" data-post-id="1" data-champ="chasse_infos_nb_max_gagants" data-cpt="chasse">
        <div class="champ-mode-options">
          <span class="toggle-option">Illimité</span>
          <label class="switch-control">
            <input type="checkbox" id="nb-gagnants-limite" checked>
            <span class="switch-slider"></span>
          </label>
          <span class="toggle-option">Limité</span>
          <div class="nb-gagnants-actions">
            <input type="number" id="chasse-nb-gagnants" value="2" class="champ-input champ-number">
          </div>
        </div>
      </li>
      <span class="nb-gagnants-affichage" data-post-id="1"></span>
    `;

    global.modifierChampSimple = jest.fn(() => Promise.resolve(true));
    global.wp = { i18n: { __: s => s, _n: (s, p, n) => (n > 1 ? p : s), sprintf: (str, ...args) => str.replace('%d', args[0]) } };
    jest.resetModules();
    jest.useFakeTimers();
    require('../../wp-content/themes/chassesautresor/assets/js/chasse-edit.js');
    global.modifierChampSimple.mockClear();
  });

  afterEach(() => {
    jest.useRealTimers();
    jest.resetModules();
  });

  test('enregistre le nombre puis l\'option illimité', async () => {
    const input = document.getElementById('chasse-nb-gagnants');
    const toggle = document.getElementById('nb-gagnants-limite');

    input.value = '5';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    jest.runAllTimers();
    await Promise.resolve();

    expect(global.modifierChampSimple).toHaveBeenCalledWith(
      'caracteristiques.chasse_infos_nb_max_gagants',
      5,
      '1',
      'chasse'
    );

    toggle.checked = false;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    await Promise.resolve();
  
    expect(global.modifierChampSimple).toHaveBeenCalledWith(
      'caracteristiques.chasse_infos_nb_max_gagants',
      0,
      '1',
      'chasse'
    );
    expect(document.querySelector('.nb-gagnants-affichage').textContent).toMatch(/illimité/i);
  });
});
