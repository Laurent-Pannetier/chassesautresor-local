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

    const renderer = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/inc/sidebar.php'),
      'utf8'
    );
    expect(renderer).toContain("if (in_array($context, ['chasse', 'enigme'], true)) {");
    expect(renderer).toContain("$aside_classes[] = 'is-hidden';");
  });

  test('opens after an automatic riddle is solved', () => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/reponse-automatique.js'),
      'utf8'
    );

    expect(source).toContain('window.enigmeAside.show();');
  });
});
