(function () {
  const onReady = (callback) => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  };

  onReady(() => {
    const wrapper = document.querySelector('[data-home-hero-wrapper]');

    if (!wrapper) {
      return;
    }

    const initialHero = wrapper.querySelector('[data-home-hero="initial"]');
    const latestHero = wrapper.querySelector('[data-home-hero="latest"]');

    if (!initialHero || !latestHero) {
      return;
    }

    const prefersReducedMotion = typeof window.matchMedia === 'function'
      ? window.matchMedia('(prefers-reduced-motion: reduce)')
      : null;
    const delay = 5000;

    initialHero.setAttribute('aria-hidden', 'false');
    latestHero.setAttribute('aria-hidden', 'true');

    const swapHeroes = () => {
      initialHero.classList.add('is-home-hero-hidden');
      initialHero.setAttribute('aria-hidden', 'true');

      latestHero.classList.add('is-home-hero-visible');
      latestHero.setAttribute('aria-hidden', 'false');
    };

    if (prefersReducedMotion && prefersReducedMotion.matches) {
      swapHeroes();
      return;
    }

    window.setTimeout(swapHeroes, delay);
  });
})();
