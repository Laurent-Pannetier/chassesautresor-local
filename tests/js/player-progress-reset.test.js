describe('player progress reset', () => {
  beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = '<button data-reset-my-progress>Recommencer</button>';
    global.playerProgressReset = {
      ajax_url: '/ajax',
      nonce: 'player-reset-nonce',
      confirm: 'Continuer ?',
      error: 'Failed',
      ajaxError: 'Offline',
    };
    global.confirm = jest.fn(() => true);
    global.fetch = jest.fn(() => new Promise(() => {}));
  });

  test('asks for confirmation and restarts only the current player', () => {
    require('../../wp-content/themes/chassesautresor/assets/js/player-progress-reset.js');
    document.dispatchEvent(new Event('DOMContentLoaded'));
    document.querySelector('[data-reset-my-progress]').click();

    expect(confirm).toHaveBeenCalledWith('Continuer ?');
    expect(fetch).toHaveBeenCalledWith('/ajax', expect.objectContaining({
      method: 'POST',
      body: 'action=cta_reset_my_progress&nonce=player-reset-nonce',
    }));
    expect(document.querySelector('[data-reset-my-progress]').disabled).toBe(true);
  });

  test('leaves the page unchanged when confirmation is refused', () => {
    global.confirm = jest.fn(() => false);
    require('../../wp-content/themes/chassesautresor/assets/js/player-progress-reset.js');
    document.dispatchEvent(new Event('DOMContentLoaded'));
    document.querySelector('[data-reset-my-progress]').click();

    expect(fetch).not.toHaveBeenCalled();
  });
});
