const fs = require('fs');
const path = require('path');

describe('riddle desktop sidebar default state', () => {
  test('starts collapsed but still exposes the public show method', () => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/sidebar/sidebar.js'),
      'utf8'
    );

    expect(source).toContain("document.body.classList.contains('single-enigme')");
    expect(source).toContain('hideAside();\n    window.sidebarAside');
    expect(source).toContain('window.enigmeAside = window.sidebarAside;');
    expect(source).toContain("document.body.classList.contains('comic-reader')");

    const renderer = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/inc/sidebar.php'),
      'utf8'
    );
    expect(renderer).toContain("if (\$context === 'chasse') {");
    expect(renderer).toContain("$aside_classes[] = 'is-hidden';");
    expect(renderer).toContain("if (\$context === 'enigme') {");
    expect(renderer).not.toContain('menu-lateral__accordeons');

    const functions = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/functions.php'),
      'utf8'
    );
    expect(functions).toContain("if (is_singular('chasse')) {\n        wp_enqueue_script(\n            'sidebar',");
    expect(functions).not.toContain("'accordeon'");
    expect(functions).not.toContain("is_singular(['enigme', 'chasse']) {\n        wp_enqueue_script(\n            'sidebar',");
  });

  test('does not create a reveal control on an enigma page', () => {
    document.body.className = 'single-enigme comic-reader';
    document.body.innerHTML = '<aside class="menu-lateral is-hidden" data-context="enigme"></aside>';
    delete window.enigmeAside;
    delete window.sidebarAside;

    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/sidebar/sidebar.js'),
      'utf8'
    );
    eval(source);

    expect(document.querySelector('.menu-lateral__reveal')).toBeNull();
    expect(window.enigmeAside).toBeUndefined();
  });

  test('does not open a lateral panel after an automatic riddle is solved', () => {
    const source = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/reponse-automatique.js'),
      'utf8'
    );

    expect(source).not.toContain('enigmeAside.show');
    expect(source).not.toContain('menu-lateral__accordeons');
  });
});
