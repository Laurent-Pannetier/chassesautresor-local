const fs = require('fs');
const path = require('path');

describe('riddle retry countdown', () => {
  beforeEach(() => {
    jest.useFakeTimers();
    document.body.innerHTML = '<form><input name="answer"><button type="submit">Go</button></form>';
    window.RiddleRetryCountdownConfig = { message: 'Retry in' };
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/riddle-retry-countdown.js'),
      'utf8'
    );
    window.eval(source);
  });

  afterEach(() => jest.useRealTimers());

  test('disables controls and re-enables them at the server deadline', () => {
    const form = document.querySelector('form');
    const now = Date.now();
    window.RiddleRetryCountdown.apply(form, {
      retry_at: new Date(now + 2000).toISOString(),
      server_now: new Date(now).toISOString(),
      message: 'Wait'
    });

    expect(form.querySelector('input').disabled).toBe(true);
    expect(form.querySelector('button').disabled).toBe(true);
    expect(form.querySelector('[data-retry-countdown]').textContent).toContain('Wait 0:02');

    jest.advanceTimersByTime(2000);

    expect(form.querySelector('input').disabled).toBe(false);
    expect(form.querySelector('button').disabled).toBe(false);
  });

  test('formats remaining time consistently', () => {
    expect(window.RiddleRetryCountdown.formatRemaining(65)).toBe('1:05');
  });

  test('initializes a server-rendered retry state', () => {
    const form = document.querySelector('form');
    const now = Date.now();
    form.dataset.retryState = JSON.stringify({
      retry_at: new Date(now + 1000).toISOString(),
      server_now: new Date(now).toISOString(),
      message: 'Initial wait'
    });

    window.RiddleRetryCountdown.initialize(document);

    expect(form.querySelector('input').disabled).toBe(true);
    expect(form.querySelector('[data-retry-countdown]').textContent).toContain('Initial wait');
  });
});
