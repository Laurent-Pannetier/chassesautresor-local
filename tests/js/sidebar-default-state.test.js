const fs = require('fs');
const path = require('path');

describe('legacy riddle sidebar is removed', () => {
  const theme = path.resolve(__dirname, '../../wp-content/themes/chassesautresor');

  test('no longer ships the lateral panel or its scripts', () => {
    [
      'inc/sidebar.php',
      'assets/sidebar/sidebar.js',
      'assets/sidebar/menu-toggle.js',
      'assets/js/enigme-panel.js',
      'assets/js/enigme-gagnants.js',
      'template-parts/common/sidebar-section.php',
    ].forEach((relativePath) => {
      expect(fs.existsSync(path.join(theme, relativePath))).toBe(false);
    });
  });

  test('enigma and hunt templates do not render the hamburger panel', () => {
    const affichage = fs.readFileSync(path.join(theme, 'inc/enigme/affichage.php'), 'utf8');
    const chasse = fs.readFileSync(path.join(theme, 'single-chasse.php'), 'utf8');

    [affichage, chasse].forEach((source) => {
      expect(source).not.toContain('enigme-mobile-panel');
      expect(source).not.toContain('menu-lateral');
      expect(source).not.toContain('render_sidebar');
    });
  });
});
