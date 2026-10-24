const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(
  path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/app-shell.js'),
  'utf8'
);

function mount(context, expanded) {
  document.body.innerHTML = ''
    + '<div class="app-shell">'
    + '<button type="button" class="app-nav__disclosure" data-app-nav-disclosure'
    + ' data-outils-context="' + context + '"'
    + ' aria-expanded="' + (expanded ? 'true' : 'false') + '"'
    + ' aria-controls="app-nav-outils-enigmes">Chasse et énigmes</button>'
    + '<ul id="app-nav-outils-enigmes" class="app-nav__sub"'
    + (expanded ? '' : ' hidden')
    + '><li><a href="/outils/?chasse=7">La chasse</a></li></ul>'
    + '</div>';
  eval(source);
}

describe('outils submenu disclosure', () => {
  beforeEach(() => {
    window.localStorage.clear();
    document.body.innerHTML = '';
    document.documentElement.removeAttribute('data-theme');
  });

  test('opens from a player page without leaving it and remembers the choice', () => {
    mount('0', false);

    const button = document.querySelector('[data-app-nav-disclosure]');
    const list = document.getElementById('app-nav-outils-enigmes');
    expect(list.hidden).toBe(true);

    button.click();

    expect(list.hidden).toBe(false);
    expect(button.getAttribute('aria-expanded')).toBe('true');
    expect(window.localStorage.getItem('cta_outils_submenu')).toBe('open');
    expect(list.querySelector('a').getAttribute('href')).toBe('/outils/?chasse=7');
  });

  test('restores an open list on the next player page', () => {
    window.localStorage.setItem('cta_outils_submenu', 'open');
    mount('0', false);

    const list = document.getElementById('app-nav-outils-enigmes');
    const button = document.querySelector('[data-app-nav-disclosure]');
    expect(list.hidden).toBe(false);
    expect(button.getAttribute('aria-expanded')).toBe('true');
  });

  test('keeps the list open on Outils even when it was closed elsewhere', () => {
    window.localStorage.setItem('cta_outils_submenu', 'closed');
    mount('1', true);

    const list = document.getElementById('app-nav-outils-enigmes');
    const button = document.querySelector('[data-app-nav-disclosure]');
    expect(list.hidden).toBe(false);
    expect(button.getAttribute('aria-expanded')).toBe('true');
  });

  test('a manual close on Outils is remembered for player pages', () => {
    mount('1', true);
    document.querySelector('[data-app-nav-disclosure]').click();

    expect(document.getElementById('app-nav-outils-enigmes').hidden).toBe(true);
    expect(window.localStorage.getItem('cta_outils_submenu')).toBe('closed');

    mount('0', false);
    expect(document.getElementById('app-nav-outils-enigmes').hidden).toBe(true);
  });
});
