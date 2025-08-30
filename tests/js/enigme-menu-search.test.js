beforeEach(() => {
  jest.useFakeTimers();
  jest.resetModules();
  document.body.innerHTML = `<aside class="menu-lateral"><section class="enigme-navigation"><input class="enigme-menu__search" type="search"><ul class="enigme-menu"></ul></section></aside>`;
  window.sidebarData = { ajaxUrl: '#' };
  window.matchMedia = jest.fn().mockReturnValue({ matches: true, addListener: () => {}, removeListener: () => {} });
});

test('filters items across grouped enigmes', () => {
  const menu = document.querySelector('.enigme-menu');
  for (let g = 0; g < 5; g++) {
    const group = document.createElement('li');
    group.className = 'enigme-menu__group';
    const btn = document.createElement('button');
    btn.className = 'enigme-menu__group-toggle';
    btn.type = 'button';
    btn.setAttribute('aria-expanded', 'false');
    btn.textContent = `Chapitre ${g}`;
    const sub = document.createElement('ul');
    sub.className = 'enigme-menu__group-list';
    sub.hidden = true;
    for (let i = 0; i < 12; i++) {
      const li = document.createElement('li');
      li.dataset.enigmeId = g * 12 + i + 1;
      li.textContent = `Enigme ${g * 12 + i}`;
      sub.appendChild(li);
    }
    group.appendChild(btn);
    group.appendChild(sub);
    menu.appendChild(group);
  }
  require('../../wp-content/themes/chassesautresor/assets/sidebar/sidebar.js');
  document.dispatchEvent(new Event('DOMContentLoaded'));
  const input = document.querySelector('.enigme-menu__search');
  input.value = 'Enigme 59';
  input.dispatchEvent(new Event('input', { bubbles: true }));
  jest.advanceTimersByTime(350);
  const visibleItems = menu.querySelectorAll('li[data-enigme-id]:not([hidden])');
  expect(visibleItems).toHaveLength(1);
  const visibleGroups = menu.querySelectorAll('.enigme-menu__group:not([hidden])');
  expect(visibleGroups).toHaveLength(1);
  const expanded = visibleGroups[0].querySelector('.enigme-menu__group-toggle').getAttribute('aria-expanded');
  expect(expanded).toBe('true');
});

test('group toggle collapses and expands', () => {
  const menu = document.querySelector('.enigme-menu');
  const group = document.createElement('li');
  group.className = 'enigme-menu__group';
  const btn = document.createElement('button');
  btn.className = 'enigme-menu__group-toggle';
  btn.type = 'button';
  btn.setAttribute('aria-expanded', 'false');
  btn.textContent = 'Chapitre';
  const sub = document.createElement('ul');
  sub.className = 'enigme-menu__group-list';
  sub.hidden = true;
  const li = document.createElement('li');
  li.dataset.enigmeId = 1;
  li.textContent = 'Enigme 1';
  sub.appendChild(li);
  group.appendChild(btn);
  group.appendChild(sub);
  menu.appendChild(group);
  require('../../wp-content/themes/chassesautresor/assets/sidebar/sidebar.js');
  document.dispatchEvent(new Event('DOMContentLoaded'));
  btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
  expect(btn.getAttribute('aria-expanded')).toBe('true');
  expect(sub.hidden).toBe(false);
  btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
  expect(btn.getAttribute('aria-expanded')).toBe('false');
  expect(sub.hidden).toBe(true);
});

test('search works on narrow screens', () => {
  jest.resetModules();
  document.body.innerHTML = `<aside class="menu-lateral"><section class="enigme-navigation"><input class="enigme-menu__search" type="search"><ul class="enigme-menu"><li data-enigme-id="1">Alpha</li><li data-enigme-id="2">Beta</li></ul></section></aside>`;
  window.sidebarData = { ajaxUrl: '#' };
  window.matchMedia = jest.fn().mockReturnValue({ matches: false, addListener: () => {}, removeListener: () => {} });
  jest.useFakeTimers();
  require('../../wp-content/themes/chassesautresor/assets/sidebar/sidebar.js');
  document.dispatchEvent(new Event('DOMContentLoaded'));
  const input = document.querySelector('.enigme-menu__search');
  const menu = document.querySelector('.enigme-menu');
  input.value = 'beta';
  input.dispatchEvent(new Event('input', { bubbles: true }));
  jest.advanceTimersByTime(350);
  const visibleItems = menu.querySelectorAll('li[data-enigme-id]:not([hidden])');
  expect(visibleItems).toHaveLength(1);
  expect(visibleItems[0].textContent).toBe('Beta');
});
