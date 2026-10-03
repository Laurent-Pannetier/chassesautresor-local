describe('portable account dashboard navigation', () => {
  test('loads an administrator section with the Core nonce', async () => {
    document.body.innerHTML = `
      <a href="/mon-compte/?section=outils" data-account-section="outils">Outils</a>
      <div id="cat-core-account-section"></div>`;
    window.chassesAuTresorAccount = {
      ajaxUrl: '/wp-admin/admin-ajax.php',
      nonce: 'account-nonce',
      loading: 'Chargement',
      error: 'Erreur'
    };
    global.fetch = jest.fn(() => Promise.resolve({
      json: () => Promise.resolve({ success: true, data: { html: '<section>Outils Core</section>' } })
    }));
    jest.spyOn(window.history, 'replaceState').mockImplementation(() => {});
    require('../../wp-content/plugins/chassesautresor-core/assets/js/account.js');

    document.querySelector('a').dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    await new Promise((resolve) => setTimeout(resolve, 0));

    const requestUrl = new URL(fetch.mock.calls[0][0]);
    expect(requestUrl.searchParams.get('action')).toBe('cta_load_admin_section');
    expect(requestUrl.searchParams.get('section')).toBe('outils');
    expect(requestUrl.searchParams.get('nonce')).toBe('account-nonce');
    expect(document.getElementById('cat-core-account-section').textContent).toContain('Outils Core');
  });
});
