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
});
