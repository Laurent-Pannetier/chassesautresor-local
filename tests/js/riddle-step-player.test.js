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
        <div class="riddle-safe" tabindex="0" data-value="99" style="--safe-angle: -356.4deg">
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

    dial.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true }));

    expect(dial.dataset.value).toBe('0');
    expect(dial.querySelector('.riddle-safe__value').textContent).toBe('0');
    expect(dial.style.getPropertyValue('--safe-angle')).toBe('-360deg');

    dial.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true }));

    expect(dial.dataset.value).toBe('1');
    expect(dial.style.getPropertyValue('--safe-angle')).toBe('-363.6deg');
  });

  test('uses the outer marker value and the net pointer direction', () => {
    document.body.innerHTML = `
      <form class="riddle-step-safe_dial-form">
        <input type="hidden" name="reponse"><output class="riddle-safe__sequence"></output>
        <div class="riddle-safe" tabindex="0" data-value="0">
          <span class="riddle-safe__direction">*</span><span class="riddle-safe__value">0</span>
        </div>
      </form>`;
    global.RiddleStepPlayer = {
      safeValueLabel: 'Dial value',
      clockwiseLabel: 'Clockwise',
      counterclockwiseLabel: 'Counterclockwise'
    };
    eval(source);
    const dial = document.querySelector('.riddle-safe');
    dial.setPointerCapture = jest.fn();
    dial.getBoundingClientRect = () => ({ left: 0, top: 0, width: 100, height: 100 });
    const pointerEvent = (type, angle) => {
      const radians = angle * Math.PI / 180;
      const event = new MouseEvent(type, {
        bubbles: true,
        clientX: 50 + Math.sin(radians) * 40,
        clientY: 50 - Math.cos(radians) * 40
      });
      Object.defineProperty(event, 'pointerId', { value: 1 });
      return event;
    };

    dial.dispatchEvent(pointerEvent('pointerdown', 0));
    dial.dispatchEvent(pointerEvent('pointermove', 54));
    expect(dial.dataset.value).toBe('85');
    expect(dial.dataset.direction).toBe('H');

    dial.dispatchEvent(pointerEvent('pointermove', 50.4));
    expect(dial.dataset.value).toBe('86');
    expect(dial.dataset.direction).toBe('H');

    dial.dispatchEvent(pointerEvent('pointermove', 356.4));
    expect(dial.dataset.value).toBe('1');
    expect(dial.dataset.direction).toBe('A');
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
    const appendPage = jest.fn().mockReturnValue(1);
    window.EnigmeGallery = {
      appendPage,
      getGallery: () => document.querySelector('[data-enigme-gallery]'),
    };
    document.body.innerHTML = `
      <div class="galerie-enigme-wrapper" data-enigme-gallery></div>
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
              <article
                class="riddle-player-step is-current"
                data-player-step-id="2"
                data-step-page-image-id="99"
                data-step-page-preview="preview.jpg"
                data-step-page-full="full.jpg"
                data-step-page-thumb="thumb.jpg"
                data-step-page-alt="Page"
              >Next</article>
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
    expect(appendPage).toHaveBeenCalledWith({
      imageId: '99',
      stepId: '2',
      previewUrl: 'preview.jpg',
      fullUrl: 'full.jpg',
      thumbUrl: 'thumb.jpg',
      alt: 'Page',
      hotspotZone: '',
      hotspotLabel: '',
    });
    expect(Element.prototype.scrollIntoView).toHaveBeenCalledTimes(1);
    expect(Element.prototype.scrollIntoView.mock.instances[0]).toBe(
      document.querySelector('[data-enigme-gallery]')
    );
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

  test('unlocks the next step when a portaled immersive hotspot form succeeds', async () => {
    window.sessionStorage.clear();
    window.EnigmeGallery = {
      appendPage: jest.fn().mockReturnValue(1),
      getGallery: () => document.querySelector('[data-enigme-gallery]'),
    };
    document.body.innerHTML = `
      <div class="enigme-lightbox-overlay"></div>
      <div class="galerie-enigme-wrapper" data-enigme-gallery></div>
      <section class="riddle-steps-player">
        <article class="riddle-player-step is-current has-hotspot" data-player-step-id="12">
          <form class="riddle-step-directions-form is-hotspot-widget is-immersive-open">
            <input name="enigme_id" value="42">
            <input name="etape_id" value="12">
            <button type="submit">Validate</button>
            <p class="riddle-step-click-form__feedback"></p>
          </form>
        </article>
      </section>
    `;
    const form = document.querySelector('form');
    document.body.appendChild(form);
    document.body.classList.add('riddle-widget-immersive-open', 'no-scroll');
    global.RiddleStepPlayer = { ajaxUrl: '/ajax', error: 'Error', wrong: 'Wrong' };
    global.fetch = jest.fn().mockResolvedValue({
      json: () => Promise.resolve({
        success: true,
        data: {
          resultat: 'bon',
          current_step_id: 13,
          response_html: `
            <section class="riddle-steps-player">
              <article class="riddle-player-step is-completed" data-player-step-id="12"></article>
              <article
                class="riddle-player-step is-current"
                data-player-step-id="13"
                data-step-page-preview="p.jpg"
                data-step-page-full="f.jpg"
              >Next step</article>
            </section>
          `
        }
      })
    });

    eval(source);
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    await new Promise(resolve => window.setTimeout(resolve, 0));

    expect(document.querySelector('[data-player-step-id="12"]')).toBeNull();
    expect(document.querySelector('[data-player-step-id="13"]').textContent).toBe('Next step');
    expect(document.querySelector('.enigme-lightbox-overlay')).toBeNull();
    expect(document.querySelector('form.is-hotspot-widget')).toBeNull();
    expect(document.body.classList.contains('riddle-widget-immersive-open')).toBe(false);
  });

  test('opens and closes the immersive hotspot widget from the lightbox', () => {
    window.sessionStorage.clear();
    document.body.innerHTML = `
      <div class="enigme-lightbox-overlay">
        <div class="enigme-lightbox">
          <div class="enigme-lightbox__hotspot-stage" data-riddle-hotspot-zone="40,40,20,20" data-riddle-hotspot-step="12">
            <img class="enigme-lightbox__image" width="100" height="100">
            <button type="button" class="riddle-gallery-hotspot" data-riddle-open-widget></button>
          </div>
        </div>
      </div>
      <article class="riddle-player-step is-current has-hotspot" data-player-step-id="12">
        <form class="riddle-step-directions-form is-hotspot-widget" hidden>
          <button type="button" data-riddle-close-widget>Close</button>
          <button type="submit">Validate</button>
        </form>
      </article>
    `;
    eval(source);

    const form = document.querySelector('form.is-hotspot-widget');
    expect(form.hasAttribute('hidden')).toBe(true);

    document.querySelector('[data-riddle-open-widget]').dispatchEvent(
      new MouseEvent('click', { bubbles: true, cancelable: true })
    );
    expect(form.hidden).toBe(false);
    expect(form.classList.contains('is-immersive-open')).toBe(true);
    expect(form.parentElement).toBe(document.body);
    expect(document.body.classList.contains('riddle-widget-immersive-open')).toBe(true);
    expect(document.querySelector('.riddle-widget-immersive-backdrop')).not.toBeNull();

    document.querySelector('[data-riddle-close-widget]').dispatchEvent(
      new MouseEvent('click', { bubbles: true, cancelable: true })
    );
    expect(form.hidden).toBe(true);
    expect(form.classList.contains('is-immersive-open')).toBe(false);
    expect(form.closest('.riddle-player-step')).not.toBeNull();
    expect(document.querySelector('.riddle-widget-immersive-backdrop')).toBeNull();
  });

  test('opens the widget when clicking inside the lightbox hotspot zone', () => {
    window.sessionStorage.clear();
    document.body.innerHTML = `
      <div class="enigme-lightbox__hotspot-stage" data-riddle-hotspot-zone="40,40,20,20" data-riddle-hotspot-step="12">
        <img class="enigme-lightbox__image" width="100" height="100">
      </div>
      <article class="riddle-player-step is-current" data-player-step-id="12">
        <form class="riddle-step-directions-form is-hotspot-widget" hidden>
          <button type="submit">Validate</button>
        </form>
      </article>
    `;
    eval(source);
    const image = document.querySelector('.enigme-lightbox__image');
    image.getBoundingClientRect = () => ({ left: 0, top: 0, width: 100, height: 100 });

    image.dispatchEvent(new MouseEvent('click', {
      bubbles: true,
      cancelable: true,
      clientX: 50,
      clientY: 50
    }));

    const form = document.querySelector('form.is-hotspot-widget');
    expect(form.classList.contains('is-immersive-open')).toBe(true);
    expect(form.parentElement).toBe(document.body);
  });
});
