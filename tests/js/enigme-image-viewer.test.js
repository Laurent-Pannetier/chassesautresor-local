const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(
  path.resolve(
    __dirname,
    '../../wp-content/themes/chassesautresor/assets/js/enigme-image-viewer.js'
  ),
  'utf8'
);

describe('enigme image viewer', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div class="galerie-enigme-wrapper" data-enigme-gallery>
        <div class="galerie-enigme__stage">
          <figure class="galerie-enigme__slide is-active" data-gallery-index="0">
            <button type="button" data-enigme-lightbox-src="full-1.jpg" data-enigme-lightbox-alt="One">
              <img class="image-active" id="image-enigme-active" src="preview-1.jpg" alt="One">
            </button>
          </figure>
          <figure class="galerie-enigme__slide" data-gallery-index="1" hidden>
            <button type="button" data-enigme-lightbox-src="full-2.jpg" data-enigme-lightbox-alt="Two">
              <img src="preview-2.jpg" alt="Two">
            </button>
          </figure>
        </div>
        <div class="galerie-enigme__thumbs" role="tablist">
          <button type="button" class="galerie-enigme__thumb is-active" data-gallery-goto="0" aria-selected="true">1</button>
          <button type="button" class="galerie-enigme__thumb" data-gallery-goto="1" aria-selected="false">2</button>
        </div>
      </div>
      <section class="riddle-steps-player" data-step-total="3" data-step-current="1">
        <p class="riddle-steps-player__progress" data-template="Étape %1$d / %2$d">Étape 1 / 3</p>
        <article class="riddle-player-step is-current" data-player-step-id="10"></article>
      </section>
      <button
        type="button"
        class="riddle-player-step__zoom"
        data-enigme-lightbox-src="step-full.jpg"
        data-enigme-lightbox-alt="Step"
      >
        <img src="step-preview.jpg" alt="Step">
      </button>
    `;
    document.body.className = '';
    window.EnigmeImageViewer = {
      closeLabel: 'Fermer',
      nativeSizeLabel: 'Image en taille originale',
      stepProgressTemplate: 'Étape %1$d / %2$d'
    };
    eval(source);
  });

  afterEach(() => {
    document.body.innerHTML = '';
    document.body.className = '';
  });

  test('switches visible slide from thumbnail click', () => {
    document.querySelector('[data-gallery-goto="1"]').click();

    const slides = document.querySelectorAll('.galerie-enigme__slide');
    expect(slides[0].hidden).toBe(true);
    expect(slides[0].classList.contains('is-active')).toBe(false);
    expect(slides[1].hidden).toBe(false);
    expect(slides[1].classList.contains('is-active')).toBe(true);
    expect(document.querySelector('[data-gallery-goto="1"]').getAttribute('aria-selected')).toBe(
      'true'
    );
  });

  test('opens native-size lightbox from step image click and closes on Escape', () => {
    document.querySelector('.riddle-player-step__zoom').click();

    const overlay = document.querySelector('.enigme-lightbox-overlay');
    const image = overlay.querySelector('.enigme-lightbox__image');
    expect(overlay).not.toBeNull();
    expect(image.getAttribute('src')).toBe('step-full.jpg');
    expect(image.getAttribute('style') || '').not.toMatch(/max-width|max-height/);
    expect(document.body.classList.contains('no-scroll')).toBe(true);

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    expect(document.querySelector('.enigme-lightbox-overlay')).toBeNull();
    expect(document.body.classList.contains('no-scroll')).toBe(false);
  });

  test('updates step progress after a completed step', () => {
    const player = document.querySelector('.riddle-steps-player');
    player.querySelector('.riddle-player-step').classList.remove('is-current');
    player.querySelector('.riddle-player-step').classList.add('is-completed');
    const next = document.createElement('article');
    next.className = 'riddle-player-step is-current';
    next.dataset.playerStepId = '11';
    player.append(next);

    window.EnigmeImageViewer.updateStepProgress(player);

    expect(player.querySelector('.riddle-steps-player__progress').textContent).toBe('Étape 2 / 3');
    expect(player.dataset.stepCurrent).toBe('2');
  });
});
