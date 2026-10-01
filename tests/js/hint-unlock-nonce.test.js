const fs = require('fs');
const path = require('path');

describe('hint unlock request security', () => {
  test('sends the localized nonce with every unlock request', () => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/indices-deblocage.js'),
      'utf8'
    );

    expect(source).toContain("fd.append('nonce', indicesUnlock.nonce);");
  });
});
