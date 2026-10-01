const fs = require('fs');
const path = require('path');

describe('hunt AJAX request security', () => {
  test('sidebar navigation sends its nonce', () => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/sidebar/sidebar.js'),
      'utf8'
    );
    expect(source).toContain("data.append('nonce', sidebarData.nonce);");
  });

  test('validation CTA refresh sends the riddle editor nonce', () => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/enigme-edit.js'),
      'utf8'
    );
    expect(source).toContain("nonce: window.CHP_ENIGME_DEFAUT?.nonce || ''");
  });
});
