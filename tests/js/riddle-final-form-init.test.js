const fs = require('fs');
const path = require('path');

describe('dynamically unlocked final answer', () => {
  test.each([
    ['reponse-automatique.js', 'initFormulaireAutomatique'],
    ['reponse-manuelle.js', 'initFormulaireManuel']
  ])('%s listens for the step content update event', (file, initializer) => {
    const source = fs.readFileSync(
      path.resolve(__dirname, `../../wp-content/themes/chassesautresor/assets/js/${file}`),
      'utf8'
    );

    expect(source).toContain(`document.addEventListener('riddle-step-content-updated', ${initializer});`);
    expect(source).toContain("form.dataset.responseHandlerReady === '1'");
  });
});
