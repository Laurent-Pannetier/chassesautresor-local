const fs = require('fs');
const path = require('path');

describe('trophy holder list dismissal', () => {
  const source = fs.readFileSync(
    path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/trophy-wall.js'),
    'utf8'
  );

  const wall = () => `
    <div class="trophies-wall">
      <details class="trophy-card__details" open>
        <summary class="trophy-card__count">2</summary>
        <ul class="trophy-card__holders"><li>Alice</li></ul>
      </details>
      <details class="trophy-card__details">
        <summary class="trophy-card__count">1</summary>
        <ul class="trophy-card__holders"><li>Bob</li></ul>
      </details>
      <button type="button" class="outside">Ailleurs</button>
    </div>
  `;

  const lists = () => document.querySelectorAll('.trophy-card__details');

  beforeAll(() => {
    document.body.innerHTML = wall();
    eval(source);
  });

  beforeEach(() => {
    document.body.innerHTML = wall();
  });

  test('closes when pressing outside the open list', () => {
    document.querySelector('.outside').dispatchEvent(
      new MouseEvent('pointerdown', { bubbles: true })
    );

    expect(lists()[0].open).toBe(false);
  });

  test('closes on a click outside the open list', () => {
    document.querySelector('.outside').dispatchEvent(
      new MouseEvent('click', { bubbles: true })
    );

    expect(lists()[0].open).toBe(false);
  });

  test('stays open when the press starts on a name', () => {
    const name = document.querySelector('.trophy-card__holders li');
    name.dispatchEvent(new MouseEvent('pointerdown', { bubbles: true }));
    name.dispatchEvent(new MouseEvent('click', { bubbles: true }));

    expect(lists()[0].open).toBe(true);
  });

  test('a second press on the count closes the list', () => {
    const summary = document.querySelector('summary');
    const event = new MouseEvent('click', { bubbles: true, cancelable: true });
    summary.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(false);
    expect(lists()[0].open).toBe(false);
  });

  test('pressing another count closes the list already open', () => {
    const second = lists()[1].querySelector('summary');
    second.dispatchEvent(new MouseEvent('pointerdown', { bubbles: true }));
    second.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));

    expect(lists()[0].open).toBe(false);
    expect(lists()[1].open).toBe(true);
  });

  test('closes on Escape and returns focus to the count', () => {
    const summary = document.querySelector('summary');
    summary.tabIndex = 0;
    summary.focus();

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

    expect(lists()[0].open).toBe(false);
    expect(document.activeElement).toBe(summary);
  });

  test('Escape outside the list does not move focus', () => {
    const outside = document.querySelector('.outside');
    outside.focus();

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

    expect(lists()[0].open).toBe(false);
    expect(document.activeElement).toBe(outside);
  });

  test('closes the previous list when another one opens', () => {
    const open = lists();
    open[1].open = true;
    open[1].dispatchEvent(new Event('toggle', { bubbles: true }));

    expect(open[0].open).toBe(false);
    expect(open[1].open).toBe(true);
  });
});
