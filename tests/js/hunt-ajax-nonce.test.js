const fs = require('fs');
const path = require('path');

describe('hunt AJAX request security', () => {
  test('legacy sidebar no longer posts hunt navigation from the theme', () => {
    const sidebarJs = path.resolve(
      __dirname,
      '../../wp-content/themes/chassesautresor/assets/sidebar/sidebar.js'
    );

    expect(fs.existsSync(sidebarJs)).toBe(false);
  });

  test('validation CTA refresh sends the riddle editor nonce', () => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/enigme-edit.js'),
      'utf8'
    );
    expect(source).toContain("nonce: window.CHP_ENIGME_DEFAUT?.nonce || ''");
  });
});
