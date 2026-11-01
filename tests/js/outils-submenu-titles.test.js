const fs = require('fs');
const path = require('path');

const theme = path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js');

function loadScripts() {
  const helpers = fs.readFileSync(path.join(theme, 'core/helpers.js'), 'utf8');
  const resume = fs.readFileSync(path.join(theme, 'core/resume.js'), 'utf8');
  const fields = fs.readFileSync(path.join(theme, 'core/champ-init.js'), 'utf8');
  global.initChampTexte = eval(helpers + '\n' + resume + '\n' + fields + '\ninitChampTexte;');
}

function sidebar(huntTitle, riddleTitle) {
  document.body.innerHTML = ''
    + '<span class="titre-objet" data-cpt="enigme">Ancien</span>'
    + '<span class="titre-objet" data-cpt="chasse">Ancienne chasse</span>'
    + '<ul id="app-nav-outils-enigmes">'
    + '<li class="app-nav__subitem--hunt" data-nav-cpt="chasse" data-nav-id="7">'
    + '<span class="app-nav__sublabel">' + huntTitle + '</span>'
    + '</li>'
    + '<li class="app-nav__subitem--riddle" data-nav-cpt="enigme" data-nav-id="11">'
    + '<a><span class="app-nav__sublabel">' + riddleTitle + '</span></a>'
    + '<button type="button" class="app-nav__marker app-nav__marker--incomplete"'
    + ' popovertarget="enigme-gaps-11"></button>'
    + '<div id="enigme-gaps-11" popover><ul><li>Titre</li><li>Image</li></ul></div>'
    + '</li>'
    + '<li class="app-nav__subitem--riddle" data-nav-cpt="enigme" data-nav-id="12">'
    + '<span class="app-nav__sublabel">Seconde énigme</span>'
    + '</li>'
    + '</ul>';
}

function label(cpt, id) {
  return document.querySelector(
    '[data-nav-cpt="' + cpt + '"][data-nav-id="' + id + '"] .app-nav__sublabel'
  ).textContent;
}

describe('outils submenu titles', () => {
  beforeEach(() => {
    jest.resetModules();
    jest.useFakeTimers();
    global.wp = { i18n: { __: (text) => text } };
    global.ajaxurl = '/ajax';
    global.fetch = jest.fn(() => Promise.resolve({
      json: () => Promise.resolve({ success: true, data: {} }),
    }));
    window.CHP_ENIGME_DEFAUT = { titre: 'en création' };
    window.CHP_CHASSE_DEFAUT = { titre: 'nouvelle chasse' };
    sidebar('Nouvelle chasse', 'en création');
    loadScripts();
  });

  afterEach(() => {
    jest.clearAllTimers();
    jest.useRealTimers();
  });

  test('follows the riddle title while it is typed', () => {
    document.body.insertAdjacentHTML(
      'beforeend',
      '<div data-champ="post_title" data-cpt="enigme" data-post-id="11">'
      + '<input class="champ-input" value="en création" />'
      + '</div>'
    );
    const bloc = document.querySelector('[data-champ="post_title"]');
    const input = bloc.querySelector('.champ-input');
    initChampTexte(bloc);

    input.value = 'Le phare rouge';
    input.dispatchEvent(new Event('input'));

    expect(label('enigme', '11')).toBe('Le phare rouge');
    expect(document.getElementById('enigme-gaps-11').textContent).toContain('Titre');
  });

  test('follows the hunt title the same way', () => {
    document.body.insertAdjacentHTML(
      'beforeend',
      '<div data-champ="post_title" data-cpt="chasse" data-post-id="7">'
      + '<input class="champ-input" value="Nouvelle chasse" />'
      + '</div>'
    );
    const bloc = document.querySelector('[data-champ="post_title"][data-cpt="chasse"]');
    const input = bloc.querySelector('.champ-input');
    initChampTexte(bloc);

    input.value = 'La chasse du phare';
    input.dispatchEvent(new Event('input'));

    expect(label('chasse', '7')).toBe('La chasse du phare');
    expect(label('enigme', '11')).toBe('en création');
  });

  test('keeps the saved title and drops the title warning', () => {
    window.onChampSimpleMisAJour('post_title', 11, 'Le phare rouge', 'enigme', null);

    expect(label('enigme', '11')).toBe('Le phare rouge');
    expect(document.querySelector('.titre-objet[data-cpt="enigme"]').textContent).toBe('Le phare rouge');
    const gaps = document.getElementById('enigme-gaps-11');
    expect(gaps.textContent).not.toContain('Titre');
    expect(gaps.textContent).toContain('Image');
    expect(document.querySelector('.app-nav__marker--incomplete')).not.toBeNull();
  });

  test('removes the warning when the title was the last missing item', () => {
    document.querySelector('#enigme-gaps-11 li:last-child').remove();
    window.onChampSimpleMisAJour('post_title', '11', 'Le phare rouge', 'enigme', null);

    expect(document.getElementById('enigme-gaps-11')).toBeNull();
    expect(document.querySelector('.app-nav__marker--incomplete')).toBeNull();
  });

  test('removes a deleted riddle from the submenu before leaving the page', () => {
    window.retirerEnigmeMenuShell(11);

    expect(document.querySelector('[data-nav-id="11"]')).toBeNull();
    expect(document.querySelector('[data-nav-id="12"]')).not.toBeNull();
    expect(label('chasse', '7')).toBe('Nouvelle chasse');

    const source = fs.readFileSync(path.join(theme, 'enigme-edit.js'), 'utf8');
    expect(source).toContain('window.retirerEnigmeMenuShell(postId)');
  });
});
