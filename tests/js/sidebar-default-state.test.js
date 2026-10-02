const fs = require('fs');
const path = require('path');

describe('riddle desktop sidebar default state', () => {
  test('starts collapsed but still exposes the public show method', () => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/sidebar/sidebar.js'),
      'utf8'
    );

    expect(source).toContain('hideAside();\n    window.sidebarAside');
    expect(source).toContain('window.enigmeAside = window.sidebarAside;');
  });

  test('opens after an automatic riddle is solved', () => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/reponse-automatique.js'),
      'utf8'
    );

    expect(source).toContain('window.enigmeAside.show();');
  });
});
