describe('initChampImage', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div class="champ-img" data-champ="indice_image" data-cpt="enigme" data-post-id="1"><img><input class="champ-input" value="123"><div class="champ-feedback"></div></div>';
    global.window = window;
    global.wp = {
      media: {
        attachment: jest.fn(() => ({
          attributes: {
            url: 'full.jpg',
            sizes: {
              thumbnail: { url: 'thumb.jpg' },
              medium: { url: 'medium.jpg' },
            },
          },
          fetch: jest.fn(() => Promise.resolve()),
        })),
        view: { settings: { post: {} } },
      },
    };
    global.ajaxurl = '/ajax';
    delete require.cache[require.resolve('../../wp-content/themes/chassesautresor/assets/js/core/image-utils.js')];
    global.initChampImage = require('../../wp-content/themes/chassesautresor/assets/js/core/image-utils.js');
  });

  test('loads existing image from input value', async () => {
    const bloc = document.querySelector('.champ-img');
    initChampImage(bloc);
    await Promise.resolve();
    await Promise.resolve();
    const img = bloc.querySelector('img');
    expect(img.src).toContain('thumb.jpg');
    expect(bloc.classList.contains('champ-vide')).toBe(false);
    expect(wp.media.attachment).toHaveBeenCalledWith(123);
  });
});
