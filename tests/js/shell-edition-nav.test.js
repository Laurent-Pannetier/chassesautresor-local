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
