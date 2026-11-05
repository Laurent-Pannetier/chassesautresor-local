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
    expect(slides[0].hidden).toBe(false);
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

    const close = overlay.querySelector('.enigme-lightbox__close');
    expect(close.textContent).toBe('×');
    expect(close.querySelector('.enigme-lightbox__close-key')).toBeNull();
    expect(close.getAttribute('aria-label')).toBe('Fermer');

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    expect(document.querySelector('.enigme-lightbox-overlay')).toBeNull();
    expect(document.body.classList.contains('no-scroll')).toBe(false);
  });

  const useFinePointer = () => {
    const previous = window.matchMedia;
    window.matchMedia = (query) => ({
      matches: query === '(hover: hover) and (pointer: fine)',
      media: query,
      addEventListener() {},
      removeEventListener() {},
    });
    return () => {
      window.matchMedia = previous;
    };
  };

  const boxImage = (img, rect) => {
    img.getBoundingClientRect = () => ({
      left: rect.left,
      top: rect.top,
      width: rect.width,
      height: rect.height,
      right: rect.left + rect.width,
      bottom: rect.top + rect.height,
      x: rect.left,
      y: rect.top,
      toJSON() {},
    });
  };

  class MousePointerEvent extends MouseEvent {
    constructor(type, init = {}) {
      super(type, init);
      this.pointerId = init.pointerId ?? 1;
      this.pointerType = init.pointerType || 'mouse';
    }
  }

  const pointMouse = (type, target, x, y) => {
    target.dispatchEvent(
      new MousePointerEvent(type, {
        bubbles: true,
        pointerId: 1,
        pointerType: 'mouse',
        clientX: x,
        clientY: y,
        button: 0,
      })
    );
  };

  const clickMouse = (target, x, y) => {
    target.dispatchEvent(
      new MouseEvent('click', {
        bubbles: true,
        clientX: x,
        clientY: y,
        button: 0,
      })
    );
  };

  test('arms a 4x lens on click and turns it off on a second click or Escape', () => {
    const restorePointer = useFinePointer();
    try {
      const trigger = document.querySelector('.riddle-player-step__zoom');
      const img = trigger.querySelector('img');
      boxImage(img, { left: 100, top: 50, width: 200, height: 100 });
      const lens = document.querySelector('.enigme-loupe');
      const zoomed = lens.querySelector('.enigme-loupe__image');

      pointMouse('pointermove', img, 150, 80);
      expect(lens.hidden).toBe(true);

      clickMouse(img, 150, 80);
      expect(document.querySelector('.enigme-lightbox-overlay')).toBeNull();
      expect(lens.hidden).toBe(false);
      expect(lens.querySelector('.enigme-loupe__cross')).not.toBeNull();
      expect(zoomed.getAttribute('src')).toBe('step-full.jpg');
      expect(zoomed.style.width).toBe('800px');
      expect(zoomed.style.height).toBe('400px');
      expect(zoomed.style.left).toBe('-80px');
      expect(zoomed.style.top).toBe('0px');
      expect(lens.style.left).toBe('30px');
      expect(lens.style.top).toBe('-40px');
      expect(trigger.classList.contains('is-loupe-target')).toBe(true);

      pointMouse('pointermove', img, 10, 10);
      expect(lens.hidden).toBe(true);
      pointMouse('pointermove', img, 150, 80);
      expect(lens.hidden).toBe(false);

      clickMouse(img, 150, 80);
      expect(lens.hidden).toBe(true);
      pointMouse('pointermove', img, 160, 90);
      expect(lens.hidden).toBe(true);

      clickMouse(img, 150, 80);
      expect(lens.hidden).toBe(false);
      document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
      expect(lens.hidden).toBe(true);
      pointMouse('pointermove', img, 150, 80);
      expect(lens.hidden).toBe(true);
    } finally {
      restorePointer();
    }
  });

  test('arms the lens when the reader retargets the click onto the stage', () => {
    const restorePointer = useFinePointer();
    try {
      const trigger = document.querySelector('[data-enigme-lightbox-src="full-1.jpg"]');
      const img = trigger.querySelector('img');
      const stage = document.querySelector('.galerie-enigme__stage');
      boxImage(img, { left: 0, top: 0, width: 400, height: 300 });

      pointMouse('pointerdown', img, 120, 80);
      pointMouse('pointerup', img, 120, 80);
      clickMouse(stage, 120, 80);

      const lens = document.querySelector('.enigme-loupe');
      expect(lens.hidden).toBe(false);
      expect(lens.querySelector('.enigme-loupe__image').getAttribute('src')).toBe('full-1.jpg');
      expect(trigger.classList.contains('is-loupe-target')).toBe(true);
    } finally {
      restorePointer();
    }
  });

  test('hides the lens while dragging and ignores the letterbox around a contained image', () => {
    const restorePointer = useFinePointer();
    try {
    const trigger = document.querySelector('[data-enigme-lightbox-src="full-1.jpg"]');
    const img = trigger.querySelector('img');
    img.style.objectFit = 'contain';
    Object.defineProperty(img, 'naturalWidth', { configurable: true, get: () => 400 });
    Object.defineProperty(img, 'naturalHeight', { configurable: true, get: () => 200 });
    boxImage(img, { left: 0, top: 0, width: 200, height: 200 });

    pointMouse('pointermove', img, 100, 10);
    expect(document.querySelector('.enigme-loupe').hidden).toBe(true);

    clickMouse(img, 100, 100);
    pointMouse('pointerdown', img, 100, 100);
    pointMouse('pointermove', img, 100, 100);
    const lens = document.querySelector('.enigme-loupe');
    const zoomed = lens.querySelector('.enigme-loupe__image');
    expect(lens.hidden).toBe(false);
    expect(zoomed.style.width).toBe('800px');
    expect(zoomed.style.height).toBe('400px');
    expect(zoomed.style.left).toBe('-280px');
    expect(zoomed.style.top).toBe('-80px');

    pointMouse('pointermove', img, 140, 100);
    expect(lens.hidden).toBe(true);

    pointMouse('pointerup', img, 140, 100);
    expect(lens.hidden).toBe(false);
    } finally {
      restorePointer();
    }
  });

  test('keeps the lightbox on the picture when older hotspot data is present', () => {
    const trigger = document.querySelector('[data-enigme-lightbox-src="full-2.jpg"]');
    trigger.dataset.riddleHotspotZone = '40,40,20,20';
    trigger.dataset.riddleHotspotStep = '12';
    trigger.dataset.riddleHotspotLabel = 'Zone';
    class TouchPointerEvent extends MouseEvent {
      constructor(type, init = {}) {
        super(type, init);
        this.pointerId = init.pointerId ?? 1;
        this.pointerType = init.pointerType || 'touch';
      }
    }
    const tap = (x, y) => {
      trigger.dispatchEvent(new TouchPointerEvent('pointerup', {
        bubbles: true,
        pointerId: 1,
        pointerType: 'touch',
        clientX: x,
        clientY: y,
      }));
    };
    tap(20, 20);
    tap(22, 22);

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

  test('appends every page of one step and opens the first of that series', () => {
    const gallery = document.querySelector('[data-enigme-gallery]');
    const first = window.EnigmeGallery.appendPage({
      imageId: 51,
      stepId: 7,
      previewUrl: 'a.jpg',
      fullUrl: 'a-full.jpg',
      thumbUrl: 'a-thumb.jpg',
      alt: 'Page A',
      select: false,
    });
    const second = window.EnigmeGallery.appendPage({
      imageId: 52,
      stepId: 7,
      previewUrl: 'b.jpg',
      fullUrl: 'b-full.jpg',
      thumbUrl: 'b-thumb.jpg',
      alt: 'Page B',
      select: false,
    });

    expect(first).toBe(2);
    expect(second).toBe(3);
    expect(document.querySelectorAll('.galerie-enigme__slide[data-gallery-step-id="7"]')).toHaveLength(2);
    expect(document.querySelector('[data-gallery-index="2"]').classList.contains('is-active')).toBe(false);
    window.EnigmeGallery.selectSlide(gallery, first);
    expect(document.querySelector('[data-gallery-index="2"]').classList.contains('is-active')).toBe(true);
    expect(document.querySelector('.galerie-enigme__page-label').textContent).toBe('Page 3 / 4');
  });

  test('serves an unlocked step page with the same srcset as the hero', () => {
    const index = window.EnigmeGallery.appendPage({
      imageId: 99,
      stepId: 8,
      previewUrl: 'step-large.jpg',
      fullUrl: 'step-full.jpg',
      thumbUrl: 'step-thumb.jpg',
      alt: 'Planche',
      srcset: 'step-720.jpg 720w, step-1000.jpg 1000w, step-full.jpg 1380w',
      webpSrcset: 'step-720.webp 720w, step-full.webp 1380w',
      sizes: 'min(100vw, 1380px)',
      width: '1024',
      height: '576',
    });

    const slide = document.querySelector(`[data-gallery-index="${index}"]`);
    const image = slide.querySelector('img');
    const source = slide.querySelector('source');
    expect(image.getAttribute('src')).toBe('step-large.jpg');
    expect(image.getAttribute('srcset')).toBe(
      'step-720.jpg 720w, step-1000.jpg 1000w, step-full.jpg 1380w'
    );
    expect(image.getAttribute('sizes')).toBe('min(100vw, 1380px)');
    expect(image.getAttribute('width')).toBe('1024');
    expect(source.getAttribute('type')).toBe('image/webp');
    expect(source.getAttribute('srcset')).toBe('step-720.webp 720w, step-full.webp 1380w');
    expect(source.getAttribute('sizes')).toBe(image.getAttribute('sizes'));
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

describe('webtoon reader', () => {
  class TestPointerEvent extends MouseEvent {
    constructor(type, init = {}) {
      super(type, init);
      this.pointerId = init.pointerId ?? 1;
      this.pointerType = init.pointerType || 'touch';
    }
  }

  const point = (type, x, y, pointerId = 1, pointerType = 'touch') => {
    document.querySelector('.enigme-media-zoom').dispatchEvent(
      new TestPointerEvent(type, {
        bubbles: true,
        pointerId,
        pointerType,
        clientX: x,
        clientY: y,
        button: 0,
      })
    );
  };

  beforeEach(() => {
    document.body.innerHTML = `
      <header class="app-topbar">
        <a class="app-topbar__login" href="/login">Connexion</a>
      </header>
      <div class="page-enigme page-enigme--reader">
        <header class="bloc-titre"><h1>Titre</h1></header>
        <div class="galerie-enigme-wrapper" data-enigme-gallery data-webtoon id="galerie-enigme-9">
          <div class="galerie-enigme__stage">
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
          </div>
        </div>
        <article class="contenu-principal"><div class="enigme-texte">Un texte</div></article>
      </div>
      <section class="participation">
        <div class="participation-dock">
          <div class="zone-reponse">
            <div class="reponse-launcher" data-riddle-reply-key="final-9">
              <form class="formulaire-reponse-auto is-reply-widget" data-riddle-reply-key="final-9">
                <input type="text" name="reponse">
              </form>
            </div>
          </div>
          <div class="zone-indices" data-hint-display="global">
            <a href="#" class="indice-link" data-indice-id="4">Indice</a>
          </div>
        </div>
      </section>
    `;
    document.body.className = '';
    window.EnigmeImageViewer = {
      closeLabel: 'Fermer',
      nativeSizeLabel: 'Image en taille originale',
      homeLabel: 'Accueil',
      homeUrl: '/',
    };
    Element.prototype.scrollIntoView = jest.fn();
    eval(source);
  });

  afterEach(() => {
    document.body.innerHTML = '';
    document.body.className = '';
  });

  test('keeps the page scroll and leaves the answer under the strip', () => {
    expect(document.body.classList.contains('comic-reader')).toBe(false);
    expect(document.body.style.overflow).not.toBe('hidden');
    expect(document.querySelector('.comic-hud, .is-comic-hud, .comic-page-counter')).toBeNull();
    expect(document.querySelector('.participation .reponse-launcher')).not.toBeNull();
    expect(document.querySelector('.page-enigme .contenu-principal')).not.toBeNull();
    expect(document.querySelector('.page-enigme .participation')).toBeNull();

    const slides = document.querySelectorAll('.galerie-enigme__slide');
    expect(slides).toHaveLength(2);
    expect(slides[0].hidden).toBe(false);
    expect(slides[1].hidden).toBe(false);
  });

  test('does not turn the strip on a swipe or arrow key', () => {
    point('pointerdown', 200, 80);
    point('pointermove', 40, 80);
    point('pointerup', 40, 80);
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));

    const slides = document.querySelectorAll('.galerie-enigme__slide');
    expect(slides[0].hidden).toBe(false);
    expect(slides[1].hidden).toBe(false);
    expect(slides[0].classList.contains('is-active')).toBe(true);
    expect(document.body.classList.contains('is-hud-visible')).toBe(false);
    expect(document.querySelector('.enigme-lightbox-overlay')).toBeNull();
  });

  test('scrolls to the first newly unlocked image', () => {
    const gallery = document.querySelector('[data-enigme-gallery]');
    const index = window.EnigmeGallery.appendPage({
      imageId: 77,
      stepId: 4,
      previewUrl: 'next.jpg',
      fullUrl: 'next-full.jpg',
      alt: 'Suite',
      select: false,
    });
    const slide = gallery.querySelector(`[data-gallery-index="${index}"]`);
    expect(slide.hidden).toBe(false);
    expect(slide.classList.contains('is-active')).toBe(false);

    window.EnigmeGallery.selectSlide(gallery, index);
    expect(slide.classList.contains('is-active')).toBe(true);
    expect(slide.previousElementSibling.hidden).toBe(false);
    expect(Element.prototype.scrollIntoView).toHaveBeenCalled();
    expect(Element.prototype.scrollIntoView.mock.instances.at(-1)).toBe(slide);
  });

  test('opens the original on a double tap and ignores a single tap', () => {
    point('pointerup', 40, 40);
    expect(document.querySelector('.enigme-lightbox-overlay')).toBeNull();

    point('pointerup', 42, 41);
    const image = document.querySelector('.enigme-lightbox__image');
    expect(image).not.toBeNull();
    expect(image.getAttribute('src')).toBe('full-1.jpg');
  });

  test('keeps Accueil visible and clickable below the admin bar', () => {
    const home = document.querySelector('.galerie-enigme__home');
    expect(home).not.toBeNull();
    expect(home.getAttribute('href')).toBe('/');
    expect(home.textContent).toContain('Accueil');
    expect(home.style.getPropertyValue('--galerie-home-top')).toBe('12px');
    expect(home.classList.contains('is-edge-visible')).toBe(false);

    const click = new MouseEvent('click', { bubbles: true, cancelable: true });
    home.dispatchEvent(click);
    expect(click.defaultPrevented).toBe(false);

    document.body.classList.add('admin-bar');
    window.dispatchEvent(new Event('resize'));
    expect(home.style.getPropertyValue('--galerie-home-top')).toBe('44px');
  });

  test('leaves the answer in place when edition mode starts', async () => {
    document.body.classList.add('edition-active-enigme');
    await Promise.resolve();

    expect(document.body.classList.contains('comic-reader')).toBe(false);
    expect(document.querySelector('.participation .reponse-launcher')).not.toBeNull();
    expect(document.querySelector('.page-enigme .contenu-principal')).not.toBeNull();
  });

  test('loads the visible bands first and the rest only after they are served', () => {
    window.__enigmeViewerDispose();
    document.body.innerHTML = `
      <div class="galerie-enigme-wrapper" data-enigme-gallery data-webtoon id="galerie-enigme-4">
        <div class="galerie-enigme__stage">
          <figure class="galerie-enigme__slide is-active">
            <button type="button" class="enigme-media-zoom">
              <span class="webtoon-bands">
                <img class="webtoon-band" data-webtoon-band data-band-src="band-0.jpg" src="band-0.jpg" alt="" width="1380" height="1000">
                <img class="webtoon-band" data-webtoon-band data-band-src="band-1.jpg" alt="" width="1380" height="1000">
                <img class="webtoon-band" data-webtoon-band data-band-src="band-2.jpg" alt="" width="1380" height="1000">
              </span>
            </button>
          </figure>
        </div>
      </div>
    `;
    const images = [...document.querySelectorAll('[data-webtoon-band]')];
    const boxes = [
      { top: 0, bottom: 500, height: 500 },
      { top: 1800, bottom: 2300, height: 500 },
      { top: 2400, bottom: 2900, height: 500 },
    ];
    images.forEach((image, index) => {
      Object.defineProperty(image, 'complete', { configurable: true, get: () => false });
      image.getBoundingClientRect = () => ({
        top: boxes[index].top,
        bottom: boxes[index].bottom,
        height: boxes[index].height,
        left: 0,
        right: 400,
        width: 400,
        x: 0,
        y: boxes[index].top,
        toJSON() {
          return {};
        },
      });
    });

    window.EnigmeGallery.initReader(document.querySelector('[data-enigme-gallery]'));

    expect(images[0].getAttribute('src')).toBe('band-0.jpg');
    expect(images[1].getAttribute('src')).toBeNull();
    expect(images[2].getAttribute('src')).toBeNull();

    boxes[1] = { top: 100, bottom: 600, height: 500 };
    window.dispatchEvent(new Event('scroll'));
    expect(images[1].getAttribute('src')).toBe('band-1.jpg');
    expect(images[2].getAttribute('src')).toBeNull();

    images[0].dispatchEvent(new Event('load'));
    images[1].dispatchEvent(new Event('load'));
    expect(images[2].getAttribute('src')).toBe('band-2.jpg');
  });
});
