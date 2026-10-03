describe('demo statistics reset shortcut', () => {
  beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = '<button data-reset-stats>Reset stats</button>';
    global.resetStatsCard = {
      ajax_url: '/ajax',
      nonce: 'demo-reset-nonce',
      confirm: 'Confirm?',
      success: 'Done',
      error: 'Failed',
      ajaxError: 'Offline',
    };
    global.confirm = jest.fn(() => true);
    global.fetch = jest.fn(() => new Promise(() => {}));
  });

  test('confirms the destructive action and submits the localized nonce', () => {
    require('../../wp-content/themes/chassesautresor/assets/js/reset-stats-card.js');
    document.dispatchEvent(new Event('DOMContentLoaded'));
    document.querySelector('[data-reset-stats]').click();

    expect(confirm).toHaveBeenCalledWith('Confirm?');
    expect(fetch).toHaveBeenCalledWith('/ajax', expect.objectContaining({
      method: 'POST',
      body: 'action=cta_reset_stats&nonce=demo-reset-nonce',
    }));
    expect(document.querySelector('[data-reset-stats]').disabled).toBe(true);
  });
});
