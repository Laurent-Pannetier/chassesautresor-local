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
});
