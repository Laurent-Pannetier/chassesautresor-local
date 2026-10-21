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
      <div class="galerie-enigme-wrapper" data-enigme-gallery id="galerie-enigme-1" data-gallery-page-count="2">
        <div class="galerie-enigme__stage">
          <button type="button" class="galerie-enigme__nav galerie-enigme__nav--prev" data-gallery-step="-1">‹</button>
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
          <button type="button" class="galerie-enigme__nav galerie-enigme__nav--next" data-gallery-step="1">›</button>
        </div>
        <div class="galerie-enigme__pager" aria-live="polite">
          <span class="galerie-enigme__page-label">Page 1 / 2</span>
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
      nativeSizeLabel: 'Image en taille originale',
      pageLabel: 'Page %1$d / %2$d',
      zoomHint: 'Agrandir',
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

  test('keeps the lightbox on the picture when older hotspot data is present', () => {
    const trigger = document.querySelector('[data-enigme-lightbox-src="full-2.jpg"]');
    trigger.dataset.riddleHotspotZone = '40,40,20,20';
    trigger.dataset.riddleHotspotStep = '12';
    trigger.dataset.riddleHotspotLabel = 'Zone';
    trigger.click();

    expect(document.querySelector('.enigme-lightbox__hotspot-stage')).toBeNull();
    expect(document.querySelector('.riddle-gallery-hotspot')).toBeNull();
    expect(document.querySelector('.enigme-lightbox__image').getAttribute('src')).toBe('full-2.jpg');
  });

  test('appends an unlocked step page into the comic gallery and selects it', () => {
    window.EnigmeImageViewer.pageLabel = 'Page %1$d / %2$d';
    const index = window.EnigmeGallery.appendPage({
      imageId: 99,
      stepId: 7,
      previewUrl: 'step-preview.jpg',
      fullUrl: 'step-full.jpg',
      thumbUrl: 'step-thumb.jpg',
      alt: 'Nouvelle page',
    });

    expect(index).toBe(2);
    const slides = document.querySelectorAll('.galerie-enigme__slide');
    expect(slides).toHaveLength(3);
    expect(slides[2].classList.contains('is-active')).toBe(true);
    expect(slides[2].dataset.galleryStepId).toBe('7');
    expect(document.querySelector('.galerie-enigme__page-label').textContent).toBe('Page 3 / 3');
    expect(document.querySelector('.galerie-enigme__nav--next').disabled).toBe(true);
  });

  test('turns pages with previous/next controls', () => {
    document.querySelector('[data-gallery-goto="1"]').click();
    expect(document.querySelector('[data-gallery-index="1"]').classList.contains('is-active')).toBe(
      true
    );

    document.querySelector('.galerie-enigme__nav--prev').click();
    expect(document.querySelector('[data-gallery-index="0"]').classList.contains('is-active')).toBe(
      true
    );
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

describe('comic reader', () => {
  class TestPointerEvent extends MouseEvent {
    constructor(type, init = {}) {
      super(type, init);
      this.pointerId = init.pointerId ?? 1;
      this.pointerType = init.pointerType || 'touch';
    }
  }

  const point = (type, x, y, pointerId = 1) => {
    document.querySelector('.enigme-media-zoom').dispatchEvent(
      new TestPointerEvent(type, {
        bubbles: true,
        pointerId,
        pointerType: 'touch',
        clientX: x,
        clientY: y,
        button: 0,
      })
    );
  };

  beforeEach(() => {
    jest.useFakeTimers();
    document.body.innerHTML = `
      <div class="page-enigme page-enigme--reader">
        <header class="bloc-titre"><h1>Titre</h1></header>
        <div class="galerie-enigme-wrapper" data-enigme-gallery data-comic-reader id="galerie-enigme-9" data-gallery-page-count="2">
          <div class="galerie-enigme__stage">
            <button type="button" class="galerie-enigme__nav galerie-enigme__nav--prev" data-gallery-step="-1">‹</button>
            <figure class="galerie-enigme__slide is-active" data-gallery-index="0">
              <button type="button" class="enigme-media-zoom" data-enigme-lightbox-src="full-1.jpg" data-enigme-lightbox-alt="One">
                <img class="image-active" id="image-enigme-active" src="preview-1.jpg" alt="One">
              </button>
            </figure>
            <figure class="galerie-enigme__slide" data-gallery-index="1" hidden>
              <button type="button" class="enigme-media-zoom" data-enigme-lightbox-src="full-2.jpg" data-enigme-lightbox-alt="Two">
                <img src="preview-2.jpg" alt="Two">
              </button>
            </figure>
            <button type="button" class="galerie-enigme__nav galerie-enigme__nav--next" data-gallery-step="1">›</button>
          </div>
          <div class="galerie-enigme__pager" aria-live="polite">
            <span class="galerie-enigme__page-label">Page 1 / 2</span>
          </div>
        </div>
        <article class="contenu-principal"><div class="enigme-texte">Un texte</div></article>
      </div>
      <section class="participation">
        <div class="participation-dock">
          <div class="zone-reponse">
            <div class="reponse-launcher">
              <button type="button" class="reponse-launcher__open" data-riddle-open-widget>Répondre</button>
            </div>
          </div>
          <div class="zone-indices" data-hint-display="global">
            <a href="#" class="indice-link indice-link--locked" data-indice-id="4" data-unlocked="0">
              <i class="fa-solid fa-lightbulb"></i>
              <span class="indice-link__label">Indice</span>
            </a>
            <div class="indice-display"></div>
          </div>
        </div>
      </section>
    `;
    document.body.className = '';
    window.sessionStorage.clear();
    window.EnigmeImageViewer = {
      closeLabel: 'Fermer',
      nativeSizeLabel: 'Image en taille originale',
      pageLabel: 'Page %1$d / %2$d',
    };
    eval(source);
  });

  afterEach(() => {
    window.__comicReaderTeardown?.();
    document.body.innerHTML = '';
    document.body.className = '';
    jest.useRealTimers();
  });

  test('shows the hud on the first visit, then hides it', () => {
    expect(document.body.classList.contains('comic-reader')).toBe(true);
    expect(document.body.classList.contains('is-hud-visible')).toBe(true);
    expect(document.querySelector('.comic-hud__controls .reponse-launcher__open')).not.toBeNull();
    expect(document.querySelector('.comic-hud__controls .galerie-enigme__pager')).not.toBeNull();
    expect(document.querySelector('.comic-hud__sheet .enigme-texte')).not.toBeNull();

    jest.advanceTimersByTime(2900);
    expect(document.body.classList.contains('is-hud-visible')).toBe(false);
  });

  test('single tap toggles the hud and does not open the loupe', () => {
    jest.advanceTimersByTime(2900);
    expect(document.body.classList.contains('is-hud-visible')).toBe(false);

    point('pointerdown', 40, 40);
    point('pointerup', 42, 41);
    document.querySelector('.enigme-media-zoom').click();

    expect(document.querySelector('.enigme-lightbox-overlay')).toBeNull();
    jest.advanceTimersByTime(TAP_WAIT);
    expect(document.body.classList.contains('is-hud-visible')).toBe(true);
  });

  test('double tap opens the native-size loupe', () => {
    jest.advanceTimersByTime(2900);

    point('pointerdown', 40, 40);
    point('pointerup', 41, 40);
    jest.advanceTimersByTime(120);
    point('pointerdown', 42, 41);
    point('pointerup', 43, 41);

    const image = document.querySelector('.enigme-lightbox__image');
    expect(image).not.toBeNull();
    expect(image.getAttribute('src')).toBe('full-1.jpg');
    expect(document.body.classList.contains('is-hud-visible')).toBe(false);
  });

  test('swipe left turns to the next page and does not wrap past the end', () => {
    jest.advanceTimersByTime(2900);

    point('pointerdown', 220, 80);
    point('pointermove', 80, 82);
    point('pointerup', 30, 84);

    expect(document.querySelector('[data-gallery-index="1"]').classList.contains('is-active')).toBe(
      true
    );
    expect(document.querySelector('.galerie-enigme__page-label').textContent).toBe('Page 2 / 2');
    expect(document.querySelector('.galerie-enigme__nav--next').disabled).toBe(true);

    point('pointerdown', 220, 80);
    point('pointermove', 40, 80);
    point('pointerup', 20, 80);
    expect(document.querySelector('[data-gallery-index="1"]').classList.contains('is-active')).toBe(
      true
    );
  });

  test('reveals the hud again when a hint becomes available', async () => {
    jest.advanceTimersByTime(2900);
    expect(document.body.classList.contains('is-hud-visible')).toBe(false);

    const link = document.querySelector('.indice-link');
    link.classList.remove('indice-link--locked');
    link.classList.add('indice-link--unlocked');
    link.dataset.unlocked = '1';
    await Promise.resolve();

    expect(document.body.classList.contains('is-hud-visible')).toBe(true);
  });

  test('places the comic below the site header', () => {
    const header = document.createElement('header');
    header.className = 'site-header';
    header.id = 'masthead';
    document.body.prepend(header);
    header.getBoundingClientRect = () => ({
      top: 32,
      bottom: 96,
      height: 64,
      left: 0,
      right: 100,
      width: 100,
      x: 0,
      y: 32,
      toJSON() {
        return {};
      },
    });

    window.dispatchEvent(new Event('resize'));

    expect(document.body.style.getPropertyValue('--comic-header-offset')).toBe('96px');
  });

  test('leaves the reader when edition mode starts', async () => {
    expect(document.querySelector('.participation .reponse-launcher')).not.toBeNull();
    document.body.classList.add('edition-active-enigme');
    await Promise.resolve();

    expect(document.body.classList.contains('comic-reader')).toBe(false);
    expect(document.querySelector('.galerie-enigme-wrapper .galerie-enigme__pager')).not.toBeNull();
    expect(document.querySelector('.participation .reponse-launcher')).not.toBeNull();
    expect(document.querySelector('.page-enigme .contenu-principal')).not.toBeNull();
  });
});

const TAP_WAIT = 320;
