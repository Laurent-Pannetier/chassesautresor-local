const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(
  path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/shell-edition-nav.js'),
  'utf8'
);

describe('activation gap marker hover', () => {
  const nativeMatches = Element.prototype.matches;

  afterEach(() => {
    Element.prototype.matches = nativeMatches;
    delete Element.prototype.showPopover;
    delete Element.prototype.hidePopover;
  });

  beforeEach(() => {
    document.body.innerHTML = `
      <button type="button" class="app-nav__marker app-nav__marker--incomplete"
        popovertarget="enigme-gaps-11">!</button>
      <div id="enigme-gaps-11" popover class="app-nav__status-pop">
        <p>Cette énigme bloque l’activation :</p>
        <ul><li>Réponse automatique : indiquez une réponse.</li></ul>
      </div>
    `;
    window.matchMedia = () => ({ matches: true });
    const nativeMatches = Element.prototype.matches;
    Element.prototype.matches = function matches(selector) {
      if (selector === ':popover-open') return this.dataset.open === '1';
      if (selector === ':focus-visible') return false;
      return nativeMatches.call(this, selector);
    };
    Element.prototype.showPopover = function showPopover() {
      this.dataset.open = '1';
    };
    Element.prototype.hidePopover = function hidePopover() {
      delete this.dataset.open;
    };
    eval(source);
  });

  test('places the blocking-item list next to the marker on hover', () => {
    const button = document.querySelector('.app-nav__marker--incomplete');
    const popover = document.getElementById('enigme-gaps-11');
    button.getBoundingClientRect = () => ({
      top: 40,
      right: 120,
      bottom: 60,
      left: 100,
      width: 20,
      height: 20,
    });
    Object.defineProperty(popover, 'offsetWidth', { value: 160 });
    Object.defineProperty(popover, 'offsetHeight', { value: 80 });

    button.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));

    expect(popover.dataset.open).toBe('1');
    expect(popover.style.left).toBe('128px');
    expect(popover.style.top).toBe('40px');
    expect(popover.textContent).toContain('indiquez une réponse');
  });
});

describe('sidebar gap marker follows the saved riddle', () => {
  const nativeMatches = Element.prototype.matches;

  afterEach(() => {
    Element.prototype.matches = nativeMatches;
    delete Element.prototype.showPopover;
    delete Element.prototype.hidePopover;
    delete window.ctaSyncRiddleNavGap;
    delete window.CtaShellEditionNav;
  });

  beforeEach(() => {
    document.body.innerHTML = `
      <ul id="app-nav-outils-enigmes">
        <li class="app-nav__subitem app-nav__subitem--riddle" data-riddle-id="11">
          <span class="app-nav__markers">
            <button type="button" class="app-nav__marker app-nav__marker--incomplete"
              id="enigme-gaps-btn-11" popovertarget="enigme-gaps-11"
              aria-label="Énigme incomplète : Titre">!</button>
            <div id="enigme-gaps-11" popover class="app-nav__status-pop">
              <p class="app-nav__status-pop-title">Cette énigme bloque l’activation :</p>
              <ul><li>Titre</li></ul>
            </div>
            <span class="app-nav__marker app-nav__marker--draft"></span>
          </span>
        </li>
        <li class="app-nav__subitem app-nav__subitem--riddle" data-riddle-id="12">
          <span class="app-nav__markers">
            <button type="button" class="app-nav__marker app-nav__marker--incomplete"
              id="enigme-gaps-btn-12" popovertarget="enigme-gaps-12">!</button>
            <div id="enigme-gaps-12" popover class="app-nav__status-pop">
              <ul><li>Illustrations</li></ul>
            </div>
          </span>
        </li>
        <li class="app-nav__subitem app-nav__subitem--add" hidden>
          <a class="app-nav__add">Ajouter une énigme</a>
        </li>
      </ul>
    `;
    window.matchMedia = () => ({ matches: true });
    window.CtaShellEditionNav = {
      incomplete: 'Énigme incomplète : %s',
      heading: 'Cette énigme bloque l’activation :',
    };
    Element.prototype.matches = function matches(selector) {
      if (selector === ':popover-open') return this.dataset.open === '1';
      if (selector === ':focus-visible') return false;
      return nativeMatches.call(this, selector);
    };
    Element.prototype.showPopover = function showPopover() {
      this.dataset.open = '1';
    };
    Element.prototype.hidePopover = function hidePopover() {
      delete this.dataset.open;
    };
    eval(source);
  });

  test('removes the exclamation once every required item is filled', () => {
    const popover = document.getElementById('enigme-gaps-11');
    popover.dataset.open = '1';

    window.ctaSyncRiddleNavGap(11, []);

    expect(document.getElementById('enigme-gaps-btn-11')).toBeNull();
    expect(document.getElementById('enigme-gaps-11')).toBeNull();
    expect(document.querySelector('[data-riddle-id="11"] .app-nav__marker--draft')).not.toBeNull();
    expect(document.querySelector('.app-nav__subitem--add').hidden).toBe(true);

    window.ctaSyncRiddleNavGap('12', []);

    expect(document.getElementById('enigme-gaps-btn-12')).toBeNull();
    expect(document.querySelector('.app-nav__subitem--add').hidden).toBe(false);
  });

  test('restores the exclamation with the items that still block activation', () => {
    window.ctaSyncRiddleNavGap(11, []);
    window.ctaSyncRiddleNavGap(11, ['Titre', 'Illustrations <photo>']);

    const button = document.getElementById('enigme-gaps-btn-11');
    const popover = document.getElementById('enigme-gaps-11');
    const draft = document.querySelector('[data-riddle-id="11"] .app-nav__marker--draft');

    expect(button).not.toBeNull();
    expect(button.getAttribute('aria-label')).toBe(
      'Énigme incomplète : Titre ; Illustrations <photo>'
    );
    expect(popover.nextElementSibling).toBe(draft);
    expect(popover.textContent).toContain('Titre');
    expect(popover.textContent).toContain('Illustrations <photo>');
    expect(popover.innerHTML).toContain('Illustrations &lt;photo&gt;');
    expect(popover.textContent).toContain('Cette énigme bloque l’activation');
    expect(document.querySelector('.app-nav__subitem--add').hidden).toBe(true);
  });
});

describe('add riddle icon follows the hunt lifecycle', () => {
  const nativeMatches = Element.prototype.matches;

  afterEach(() => {
    Element.prototype.matches = nativeMatches;
    delete window.ctaSyncAddRiddleAvailability;
    delete window.CtaShellEditionNav;
  });

  beforeEach(() => {
    document.body.innerHTML = '<ul id="app-nav-outils-enigmes"></ul>';
    window.CtaShellEditionNav = { addRiddle: 'Ajouter une énigme' };
    Element.prototype.matches = function matches(selector) {
      if (selector === ':popover-open') return this.dataset.open === '1';
      if (selector === ':focus-visible') return false;
      return nativeMatches.call(this, selector);
    };
    eval(source);
  });

  test('inserts the icon when adding becomes allowed', () => {
    const url = 'https://example.test/creer-enigme/?chasse_id=7&nonce=abc';

    window.ctaSyncAddRiddleAvailability(true, url);

    const item = document.querySelector('.app-nav__subitem--add');
    const link = item.querySelector('a.app-nav__add');
    expect(item.hidden).toBe(false);
    expect(link.getAttribute('href')).toBe(url);
    expect(link.querySelector('.fa-circle-plus')).not.toBeNull();
    expect(link.textContent).toContain('Ajouter une énigme');
  });

  test('keeps the icon hidden while a riddle is incomplete', () => {
    document.getElementById('app-nav-outils-enigmes').innerHTML =
      '<li class="app-nav__subitem--riddle">'
      + '<button class="app-nav__marker app-nav__marker--incomplete"></button>'
      + '</li>';

    window.ctaSyncAddRiddleAvailability(true, 'https://example.test/creer-enigme/?chasse_id=7');

    expect(document.querySelector('.app-nav__subitem--add').hidden).toBe(true);
  });

  test('removes the icon when adding is no longer allowed', () => {
    document.getElementById('app-nav-outils-enigmes').innerHTML =
      '<li class="app-nav__subitem app-nav__subitem--add">'
      + '<a class="app-nav__add" href="https://example.test/creer-enigme/">Ajouter</a>'
      + '</li>';

    window.ctaSyncAddRiddleAvailability(false, '');

    expect(document.querySelector('.app-nav__subitem--add')).toBeNull();
  });

  test('does not insert the icon when adding stays forbidden', () => {
    window.ctaSyncAddRiddleAvailability(false, '');

    expect(document.querySelector('.app-nav__subitem--add')).toBeNull();
  });
});
