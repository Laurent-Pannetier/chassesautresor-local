const fs = require('fs');
const path = require('path');

describe('riddle sidebar request security', () => {
  test.each([
    ['enigme-gagnants.js', "data.append('nonce', RiddleSidebarAjax.nonce);"],
    ['reponse-automatique.js', "dataW.append('nonce', RiddleSidebarAjax.nonce);"],
    ['reponse-automatique.js', "dataP.append('nonce', RiddleSidebarAjax.nonce);"],
  ])('%s sends its localized nonce', (filename, expected) => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js', filename),
      'utf8'
    );
    expect(source).toContain(expected);
  });
});
