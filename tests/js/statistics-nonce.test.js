describe('statistics requests', () => {
  beforeEach(() => {
    jest.resetModules();
    global.fetch = jest.fn(() => Promise.resolve({
      json: () => Promise.resolve({ success: true, data: {} }),
    }));
  });

  test('includes the localized nonce in riddle statistics requests', () => {
    document.body.innerHTML = `
      <div id="enigme-stats"></div>
      <select id="enigme-periode"><option value="mois">Mois</option></select>
    `;
    global.EnigmeStats = { ajaxUrl: '/ajax', enigmeId: 42, nonce: 'riddle-nonce' };

    require('../../wp-content/themes/chassesautresor/assets/js/enigme-stats.js');
    document.getElementById('enigme-periode').dispatchEvent(new Event('change'));

    const request = fetch.mock.calls[0][1];
    expect(request.body.get('nonce')).toBe('riddle-nonce');
    expect(request.body.get('enigme_id')).toBe('42');
  });

  test('includes the localized nonce in hunt statistics requests', () => {
    document.body.innerHTML = `
      <div id="chasse-stats"></div>
      <select id="chasse-periode"><option value="semaine">Semaine</option></select>
    `;
    global.ChasseStats = { ajaxUrl: '/ajax', chasseId: 24, nonce: 'hunt-nonce' };

    require('../../wp-content/themes/chassesautresor/assets/js/chasse-stats.js');
    document.getElementById('chasse-periode').dispatchEvent(new Event('change'));

    const body = fetch.mock.calls[0][1].body;
    expect(body.get('nonce')).toBe('hunt-nonce');
    expect(body.get('chasse_id')).toBe('24');
  });

  test('loads one player hint list when the count is opened', async () => {
    document.body.innerHTML = `
      <div id="enigme-stats"></div>
      <select id="enigme-periode"></select>
      <div id="enigme-tab-stats">
        <div class="liste-participants">
          <table><tbody><tr>
            <td><button type="button" class="participant-hints" data-user-id="7">2</button></td>
          </tr></tbody></table>
        </div>
      </div>
    `;
    global.EnigmeStats = { ajaxUrl: '/ajax', enigmeId: 42, nonce: 'riddle-nonce' };
    global.fetch = jest.fn(() => Promise.resolve({
      json: () => Promise.resolve({ success: true, data: { html: '<ul><li>Carte</li></ul>' } }),
    }));

    const readyState = Object.getOwnPropertyDescriptor(document, 'readyState');
    Object.defineProperty(document, 'readyState', {
      configurable: true,
      get: () => 'loading',
    });
    require('../../wp-content/themes/chassesautresor/assets/js/enigme-stats.js');
    document.dispatchEvent(new Event('DOMContentLoaded'));
    if (readyState) {
      Object.defineProperty(document, 'readyState', readyState);
    }
    document.querySelector('.participant-hints').click();
    await new Promise((resolve) => setTimeout(resolve, 0));

    const body = fetch.mock.calls[0][1].body;
    expect(body.get('action')).toBe('enigme_indices_participant');
    expect(body.get('nonce')).toBe('riddle-nonce');
    expect(body.get('user_id')).toBe('7');
    expect(body.get('enigme_id')).toBe('42');
    const detail = document.querySelector('.participant-hints-detail');
    expect(detail.hidden).toBe(false);
    expect(detail.textContent).toContain('Carte');
    expect(fetch).toHaveBeenCalledTimes(1);
  });
});
