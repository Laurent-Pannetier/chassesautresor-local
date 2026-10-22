const fs = require('fs');
const path = require('path');

describe('notification center client', () => {
  const source = fs.readFileSync(
    path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/notifications.js'),
    'utf8'
  );

  test('authenticates every request with the localized nonce', () => {
    expect(source).toContain("body.set('nonce', config.nonce || '');");
    expect(source).toContain("credentials: 'same-origin'");
  });

  test('uses dedicated read and overlay endpoints', () => {
    expect(source).toContain("'cat_notification_mark_read'");
    expect(source).toContain("'cat_notification_overlay_next'");
    expect(source).toContain("'cat_notification_overlay_heartbeat'");
    expect(source).toContain("'cat_notification_overlay_seen'");
  });

  test('renders server text through textContent', () => {
    expect(source).toContain("title.textContent = notification.title || '';");
    expect(source).toContain('body.textContent = notification.body;');
    expect(source).not.toContain('innerHTML');
  });

  test('claims overlays only in a visible tab and acknowledges them on dismissal', () => {
    expect(source).toContain("document.visibilityState === 'hidden'");
    expect(source).toContain("document.addEventListener('visibilitychange', loadNextOverlay);");
    expect(source).toContain('claim_token: notification.claimToken');
    expect(source).toContain('}, 45000);');
    expect(source).toContain('}, 125000);');

    const closeHandler = source.indexOf("close.addEventListener('click'");
    const acknowledgment = source.indexOf('acknowledge(notification)', closeHandler);

    expect(closeHandler).toBeGreaterThan(-1);
    expect(acknowledgment).toBeGreaterThan(closeHandler);
  });
});
