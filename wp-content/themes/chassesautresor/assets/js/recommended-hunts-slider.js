(function () {
  const SLIDER_SELECTOR = '[data-recommended-slider]';
  const TRACK_SELECTOR = '[data-recommended-slider-track]';
  const SLIDE_SELECTOR = '[data-recommended-slide]';
  const PREV_SELECTOR = '[data-recommended-slider-prev]';
  const NEXT_SELECTOR = '[data-recommended-slider-next]';

  function toElement(root) {
    if (root && typeof root.querySelectorAll === 'function') {
      return root;
    }
    return document;
  }

  function initSlider(slider) {
    if (!slider || slider.dataset.sliderReady === '1') {
      return;
    }

    const track = slider.querySelector(TRACK_SELECTOR);
    const viewport = slider.querySelector('[data-recommended-slider-viewport]');
    const slides = track ? Array.from(track.querySelectorAll(SLIDE_SELECTOR)) : [];

    if (!track || !slides.length || !viewport) {
      return;
    }

    const prev = slider.querySelector(PREV_SELECTOR);
    const next = slider.querySelector(NEXT_SELECTOR);
    const totalSlides = slides.length;
    let currentIndex = 0;

    const label = slider.getAttribute('data-slider-label');
    if (label) {
      slider.setAttribute('aria-label', label);
    }

    slider.setAttribute('role', 'region');
    slider.setAttribute('aria-roledescription', 'carousel');
    slider.setAttribute('tabindex', '0');
    slider.setAttribute('data-slides-count', String(totalSlides));
    slider.style.setProperty('--recommended-slider-count', String(totalSlides));
    track.style.setProperty('--recommended-slider-count', String(totalSlides));

    slides.forEach((slide, index) => {
      slide.setAttribute('role', 'group');
      slide.setAttribute('aria-roledescription', 'slide');
      slide.setAttribute('aria-label', `${index + 1} / ${totalSlides}`);
    });

    function update() {
      const offsetPercent = totalSlides > 0 ? (currentIndex / totalSlides) * 100 : 0;
      track.style.transform = `translate3d(-${offsetPercent}%, 0, 0)`;
      slides.forEach((slide, index) => {
        const isActive = index === currentIndex;
        slide.classList.toggle('is-active', isActive);
        slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      });

      if (prev) {
        prev.disabled = currentIndex === 0;
      }
      if (next) {
        next.disabled = currentIndex === totalSlides - 1;
      }
    }

    function goTo(index) {
      if (index === currentIndex) {
        return;
      }

      const clampedIndex = Math.max(0, Math.min(index, totalSlides - 1));
      if (clampedIndex === currentIndex) {
        return;
      }

      currentIndex = clampedIndex;
      update();
    }

    if (prev) {
      prev.addEventListener('click', () => {
        goTo(currentIndex - 1);
      });
    }

    if (next) {
      next.addEventListener('click', () => {
        goTo(currentIndex + 1);
      });
    }

    slider.addEventListener('keydown', (event) => {
      if (event.defaultPrevented) {
        return;
      }

      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        goTo(currentIndex - 1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        goTo(currentIndex + 1);
      }
    });

    let observer = null;
    const cleanup = () => {
      slider.removeEventListener('recommended-slider:destroy', cleanup);
      if (observer) {
        observer.disconnect();
      }
    };

    slider.addEventListener('recommended-slider:destroy', cleanup);

    if (typeof MutationObserver === 'function') {
      observer = new MutationObserver(() => {
        if (!document.body.contains(slider)) {
          cleanup();
        }
      });

      observer.observe(document.body, { childList: true, subtree: true });
    }

    slider.dataset.sliderReady = '1';
    update();
  }

  function init(root) {
    const context = toElement(root);
    const sliders = context.querySelectorAll(SLIDER_SELECTOR);
    sliders.forEach(initSlider);
  }

  window.caRecommendedSlider = window.caRecommendedSlider || {};
  window.caRecommendedSlider.init = function initRecommendedSlider(root) {
    init(root);
  };
})();
