const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(
  path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/riddle-step-player.js'),
  'utf8'
);

describe('self-contained riddle widgets', () => {
  beforeAll(() => {
    eval(source);
  });

  beforeEach(() => {
    document.body.innerHTML = `
      <form class="riddle-step-colors-form">
        <input name="reponse"><output class="riddle-colors__sequence"></output>
        <button type="button" class="riddle-color" data-color="pink">Rose</button>
      </form>
      <form class="riddle-step-numbers-form">
        <input name="reponse"><output class="riddle-numbers__sequence"></output>
        <button type="button" class="riddle-number" data-number="0">0</button>
        <button type="button" class="riddle-number" data-number="7">7</button>
        <button type="button" class="riddle-widget-reset">Reset</button>
      </form>
      <form class="riddle-step-safe_dial-form">
        <input name="reponse"><output class="riddle-safe__sequence"></output>
        <div class="riddle-safe" tabindex="0" data-value="0" aria-valuenow="0">
          <span class="riddle-safe__direction">✱</span>
          <span class="riddle-safe__value">0</span>
        </div>
      </form>`;
  });

  test('builds color and numeric sequences with leading zeroes', () => {
    document.querySelector('.riddle-color').click();
    document.querySelector('[data-number="0"]').click();
    document.querySelector('[data-number="7"]').click();

    expect(document.querySelector('.riddle-step-colors-form [name="reponse"]').value).toBe('pink');
    expect(document.querySelector('.riddle-color-dot')).not.toBeNull();
    expect(document.querySelector('.riddle-step-numbers-form [name="reponse"]').value).toBe('07');
    expect(document.querySelectorAll('.riddle-numbers__sequence span')).toHaveLength(2);

    document.querySelector('.riddle-widget-reset').click();
    expect(document.querySelector('.riddle-step-numbers-form [name="reponse"]').value).toBe('');
  });

  test('records keyboard dial movement on release with H and A notation', () => {
    const dial = document.querySelector('.riddle-safe');
    dial.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));
    dial.dispatchEvent(new KeyboardEvent('keyup', { key: 'ArrowRight', bubbles: true }));
    dial.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true }));
    dial.dispatchEvent(new KeyboardEvent('keyup', { key: 'ArrowLeft', bubbles: true }));

    expect(document.querySelector('.riddle-step-safe_dial-form [name="reponse"]').value).toBe('H1,A0');
    expect(document.querySelector('.riddle-safe__sequence').textContent).toContain('↻ 1');
    expect(document.querySelector('.riddle-safe__sequence').textContent).toContain('↺ 0');
  });

  test('accumulates small pointer movements before recording on release', () => {
    const dial = document.querySelector('.riddle-safe');
    dial.setPointerCapture = jest.fn();
    dial.getBoundingClientRect = () => ({ left: 0, top: 0, width: 200, height: 200 });
    const pointer = (type, clientX, clientY) => {
      const event = new Event(type, { bubbles: true, cancelable: true });
      Object.assign(event, { pointerId: 1, clientX, clientY });
      dial.dispatchEvent(event);
    };

    pointer('pointerdown', 100, 0);
    pointer('pointermove', 103, 0);
    pointer('pointermove', 106, 0);
    pointer('pointermove', 109, 0);
    pointer('pointermove', 112, 1);
    pointer('pointerup', 112, 1);

    expect(Number(dial.dataset.value)).toBeGreaterThan(0);
    expect(document.querySelector('.riddle-step-safe_dial-form [name="reponse"]').value).toMatch(/^H\d+$/);
  });
});
