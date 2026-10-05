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
      nativeSizeLabel: 'Image en taille originale'
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

  test('shows an ephemeral escape-game wrong-answer notice', () => {
    jest.useFakeTimers();
    const host = document.createElement('p');
    host.className = 'riddle-step-click-form__feedback';
    document.body.appendChild(host);

    window.showRiddleEphemeralNotice('Cette réponse n’est pas correcte.', {
      tone: 'wrong',
      duration: 1000,
      anchor: host
    });

    const notice = host.querySelector('.riddle-ephemeral-notice');
    expect(notice).not.toBeNull();
    expect(notice.classList.contains('riddle-ephemeral-notice--wrong')).toBe(true);
    expect(notice.querySelector('.riddle-ephemeral-notice__eyebrow').textContent).toBe(
      'Accès refusé'
    );
    expect(notice.querySelector('.riddle-ephemeral-notice__message').textContent).toBe(
      'Cette réponse n’est pas correcte.'
    );

    jest.advanceTimersByTime(1300);
    expect(host.querySelector('.riddle-ephemeral-notice')).toBeNull();
    jest.useRealTimers();
  });

  test('keeps variant hints soft, persistent and session-scoped', () => {
    jest.useFakeTimers();
    window.EnigmeImageViewer.hintEyebrow = 'Piste';
    const host = document.createElement('div');
    host.className = 'reponse-feedback';
    document.body.appendChild(host);
    const storageKey = 'riddle-hint:42:final';

    window.showRiddleEphemeralNotice('Regarde https://www.youtube.com/watch?v=TEx7Pu-Ok5E&t=2s', {
      tone: 'hint',
      persistent: true,
      storageKey,
      anchor: host
    });

    const hint = host.querySelector('.riddle-ephemeral-notice--hint');
    expect(hint).not.toBeNull();
    expect(hint.classList.contains('riddle-session-hint')).toBe(true);
    expect(hint.getAttribute('role')).toBe('status');
    expect(hint.querySelector('.riddle-ephemeral-notice__eyebrow').textContent).toBe('Piste');
    const link = hint.querySelector('a');
    expect(link).not.toBeNull();
    expect(link.getAttribute('href')).toBe('https://www.youtube.com/watch?v=TEx7Pu-Ok5E&t=2s');
    expect(window.sessionStorage.getItem(storageKey)).toContain('youtube.com');

    window.showRiddleEphemeralNotice('Cette réponse n’est pas correcte.', {
      tone: 'wrong',
      duration: 1000,
      anchor: host
    });

    expect(host.querySelector('.riddle-ephemeral-notice--hint')).not.toBeNull();
    expect(host.querySelector('.riddle-ephemeral-notice--wrong')).not.toBeNull();

    jest.advanceTimersByTime(1300);
    expect(host.querySelector('.riddle-ephemeral-notice--wrong')).toBeNull();
    expect(host.querySelector('.riddle-ephemeral-notice--hint')).not.toBeNull();

    host.replaceChildren();
    window.restoreRiddleSessionHint(storageKey, host);
    expect(host.querySelector('.riddle-ephemeral-notice--hint')).not.toBeNull();

    window.clearRiddleSessionHint(storageKey);
    expect(window.sessionStorage.getItem(storageKey)).toBeNull();
    expect(host.querySelector('.riddle-ephemeral-notice--hint')).toBeNull();
    jest.useRealTimers();
  });

  test('builds a stable session storage key from the answer form', () => {
    document.body.innerHTML = `
      <form class="formulaire-reponse-auto">
        <input type="hidden" name="enigme_id" value="12">
      </form>
      <form class="riddle-step-text-form">
        <input type="hidden" name="enigme_id" value="12">
        <input type="hidden" name="etape_id" value="7">
      </form>
    `;
    eval(source);

    expect(window.buildRiddleHintStorageKey(document.querySelector('.formulaire-reponse-auto'))).toBe(
      'riddle-hint:12:final'
    );
    expect(window.buildRiddleHintStorageKey(document.querySelector('.riddle-step-text-form'))).toBe(
      'riddle-hint:12:step:7'
    );
  });
});
