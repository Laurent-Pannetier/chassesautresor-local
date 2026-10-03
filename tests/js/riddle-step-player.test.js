const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(
  path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/riddle-step-player.js'),
  'utf8'
);

describe('riddle step player positioning', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <section class="riddle-steps-player">
        <article data-player-step-id="1"><img id="before"></article>
        <article data-player-step-id="2"></article>
      </section>
    `;
    Object.defineProperty(document, 'fonts', {
      configurable: true,
      value: { ready: Promise.resolve() }
    });
    Object.defineProperty(window, 'matchMedia', {
      configurable: true,
      value: jest.fn(() => ({ matches: true }))
    });
    window.requestAnimationFrame = callback => callback();
    Element.prototype.scrollIntoView = jest.fn();
    window.sessionStorage.setItem('riddleStepScrollTarget', '2');
  });

  afterEach(() => {
    window.sessionStorage.clear();
    jest.restoreAllMocks();
  });

  test('keeps the safe dial scale aligned when crossing zero', () => {
    document.body.innerHTML = `
      <form class="riddle-step-safe_dial-form">
        <input type="hidden" name="reponse"><output class="riddle-safe__sequence"></output>
        <div class="riddle-safe" tabindex="0" data-value="99" style="--safe-angle: 356.4deg">
          <span class="riddle-safe__direction">*</span><span class="riddle-safe__value">99</span>
        </div>
      </form>`;
    global.RiddleStepPlayer = {
      safeValueLabel: 'Dial value',
      clockwiseLabel: 'Clockwise',
      counterclockwiseLabel: 'Counterclockwise'
    };
    eval(source);
    const dial = document.querySelector('.riddle-safe');

    dial.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));

    expect(dial.dataset.value).toBe('0');
    expect(dial.querySelector('.riddle-safe__value').textContent).toBe('0');
    expect(dial.style.getPropertyValue('--safe-angle')).toBe('360deg');

    dial.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));

    expect(dial.dataset.value).toBe('1');
    expect(dial.style.getPropertyValue('--safe-angle')).toBe('363.6deg');
  });

  test('positions immediately, then corrects after preceding images load', async () => {
    const image = document.querySelector('#before');
    Object.defineProperty(image, 'complete', { configurable: true, value: false });

    eval(source);
    document.dispatchEvent(new Event('DOMContentLoaded'));
    expect(Element.prototype.scrollIntoView).toHaveBeenCalledTimes(1);

    image.dispatchEvent(new Event('load'));
    await new Promise(resolve => window.setTimeout(resolve, 0));
    expect(Element.prototype.scrollIntoView).toHaveBeenCalledTimes(2);
    expect(window.sessionStorage.getItem('riddleStepScrollTarget')).toBeNull();
  });

  test('adds the unlocked step without reloading the page', async () => {
    window.sessionStorage.clear();
    document.body.innerHTML = `
      <section class="riddle-steps-player">
        <article class="riddle-player-step is-current" data-player-step-id="1">
          <div class="riddle-player-step__content"><p>&nbsp;</p></div>
          <form class="riddle-step-click-form">
            <input name="enigme_id" value="42">
            <button type="submit">Continue</button>
            <p class="riddle-step-click-form__feedback"></p>
          </form>
        </article>
      </section>
    `;
    global.RiddleStepPlayer = { ajaxUrl: '/ajax', error: 'Error' };
    global.fetch = jest.fn().mockResolvedValue({
      json: () => Promise.resolve({
        success: true,
        data: {
          current_step_id: 2,
          response_html: `
            <section class="riddle-steps-player">
              <article class="riddle-player-step is-completed" data-player-step-id="1"></article>
              <article class="riddle-player-step is-current" data-player-step-id="2">Next</article>
            </section>
          `
        }
      })
    });

    eval(source);
    document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    await new Promise(resolve => window.setTimeout(resolve, 0));

    expect(document.querySelector('[data-player-step-id="1"] form')).toBeNull();
    expect(document.querySelector('[data-player-step-id="1"]')).toBeNull();
    expect(document.querySelector('[data-player-step-id="2"]').textContent).toBe('Next');
    expect(document.activeElement).toBe(document.querySelector('[data-player-step-id="2"]'));
    expect(document.activeElement.getAttribute('tabindex')).toBe('-1');
    expect(Element.prototype.scrollIntoView).toHaveBeenCalledTimes(1);
  });

  test('adds and initializes the final answer after the last step', async () => {
    window.sessionStorage.clear();
    document.body.innerHTML = `
      <div class="zone-reponse">
        <section class="riddle-steps-player">
          <article class="riddle-player-step is-current" data-player-step-id="1">
            <form class="riddle-step-click-form">
              <button type="submit">Continue</button>
              <p class="riddle-step-click-form__feedback"></p>
            </form>
          </article>
        </section>
      </div>
    `;
    const updated = jest.fn();
    document.addEventListener('riddle-step-content-updated', updated, { once: true });
    global.RiddleStepPlayer = { ajaxUrl: '/ajax', error: 'Error' };
    global.fetch = jest.fn().mockResolvedValue({
      json: () => Promise.resolve({
        success: true,
        data: {
          current_step_id: null,
          final_answer_unlocked: true,
          response_html: `
            <section class="riddle-steps-player">
              <article class="riddle-player-step is-completed" data-player-step-id="1"></article>
            </section>
            <form class="formulaire-reponse-auto"><button type="submit">Answer</button></form>
          `
        }
      })
    });

    eval(source);
    document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    await new Promise(resolve => window.setTimeout(resolve, 0));

    expect(document.querySelector('.formulaire-reponse-auto')).not.toBeNull();
    expect(document.activeElement).toBe(document.querySelector('.formulaire-reponse-auto'));
    expect(updated).toHaveBeenCalledTimes(1);
    expect(Element.prototype.scrollIntoView).toHaveBeenCalledTimes(1);
  });

  test('restores the form and announces a malformed AJAX response', async () => {
    window.sessionStorage.clear();
    document.body.innerHTML = `
      <article class="riddle-player-step is-current">
        <form class="riddle-step-text-form" data-widget-action="soumettre_reponse_etape">
          <input name="reponse" value="answer">
          <button type="submit">Submit</button>
          <p class="riddle-step-click-form__feedback" role="status"></p>
        </form>
      </article>
    `;
    global.RiddleStepPlayer = { ajaxUrl: '/ajax', error: 'Unable to submit' };
    global.fetch = jest.fn().mockResolvedValue({
      json: () => Promise.reject(new SyntaxError('Invalid JSON'))
    });

    eval(source);
    const form = document.querySelector('form');
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    expect(form.getAttribute('aria-busy')).toBe('true');
    await new Promise(resolve => window.setTimeout(resolve, 0));

    expect(form.getAttribute('aria-busy')).toBe('false');
    expect(form.querySelector('button').disabled).toBe(false);
    expect(form.querySelector('[role="alert"]').textContent).toBe('Unable to submit');
  });

  test('builds and resets a direction sequence', () => {
    document.body.innerHTML = `
      <form class="riddle-step-directions-form">
        <input type="hidden" name="reponse"><output class="riddle-directions__sequence"></output>
        <button type="button" class="riddle-direction" data-direction="NW">↖</button>
        <button type="button" class="riddle-directions-reset">↻</button>
      </form>`;
    eval(source);
    document.querySelector('.riddle-direction').click();
    expect(document.querySelector('[name="reponse"]').value.split(',').every(value => value === 'NW')).toBe(true);
    expect(document.querySelector('.riddle-directions__sequence').textContent).not.toContain('NW');
    expect(document.querySelector('.riddle-directions__sequence').textContent).toContain('↖');
    document.querySelector('.riddle-directions-reset').click();
    expect(document.querySelector('[name="reponse"]').value).toBe('');
  });
});
