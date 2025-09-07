const flush = () => new Promise(resolve => setTimeout(resolve, 0));

describe('initChampImage', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div class="champ-organisateur champ-img" data-champ="profil_public_logo_organisateur" data-cpt="organisateur" data-post-id="123">
        <img src="" />
        <input class="champ-input" type="hidden" />
        <div class="champ-feedback"></div>
      </div>`;

    global.ajaxurl = '/ajax';
    global.fetch = jest.fn(() => Promise.resolve({ json: () => Promise.resolve({ success: true }) }));
    global.mettreAJourResumeInfos = jest.fn();
    global.mettreAJourVisuelCPT = jest.fn();

    const handlers = {};
    const frame = {
      open: jest.fn(),
      on: (event, cb) => { handlers[event] = cb; },
      state: () => ({
        get: () => ({
          first: () => ({
            id: 42,
            attributes: {
              url: 'full.png',
              sizes: {
                thumbnail: { url: 'thumb.png' },
                medium: { url: 'med.png' },
                'chasse-fiche': { url: 'fiche.png' }
              }
            }
          })
        })
      }),
      __handlers: handlers
    };
    const mediaFn = jest.fn(() => frame);
    mediaFn.view = { settings: { post: {} } };
    global.wp = { media: mediaFn };

    const fs = require('fs');
    const path = require('path');
    const script = fs.readFileSync(
      path.resolve(
        __dirname,
        '../../wp-content/themes/chassesautresor/assets/js/core/image-utils.js'
      ),
      'utf8'
    );
    eval(script);
    global.initChampImage = initChampImage;
  });

  it('envoie modifier_champ_organisateur', async () => {
    const bloc = document.querySelector('.champ-organisateur');
    initChampImage(bloc);
    bloc.__ouvrirMedia();
    bloc.__mediaFrame.__handlers.select();
    await flush();
    const params = fetch.mock.calls[0][1].body;
    expect(params.get('action')).toBe('modifier_champ_organisateur');
  });
});
