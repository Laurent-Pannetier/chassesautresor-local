const fs = require('fs');
const path = require('path');

describe('removed riddle sidebar is not refreshed from the page', () => {
  test('automatic replies no longer call the sidebar endpoints', () => {
    const source = fs.readFileSync(
      path.resolve(
        __dirname,
        '../../wp-content/themes/chassesautresor/assets/js/reponse-automatique.js'
      ),
      'utf8'
    );

    expect(source).not.toContain('enigme_recuperer_gagnants');
    expect(source).not.toContain('enigme_recuperer_progression');
    expect(source).not.toContain('RiddleSidebarAjax');
    expect(source).not.toContain('enigme-menu');
  });
});
