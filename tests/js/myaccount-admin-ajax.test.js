const html = `
<div class="myaccount-layout">
  <aside class="myaccount-sidebar">
    <nav class="dashboard-nav admin-nav">
      <a href="/mon-compte/organisateurs/" class="dashboard-nav-link" data-section="organisateurs">Organisateurs</a>
      <a href="/mon-compte/statistiques/" class="dashboard-nav-link" data-section="statistiques">Statistiques</a>
      <a href="/mon-compte/outils/" class="dashboard-nav-link" data-section="outils">Outils</a>
    </nav>
  </aside>
  <div class="myaccount-main">
    <main class="myaccount-content">init</main>
  </div>
</div>
`;

describe('myaccount admin navigation', () => {
  let initModule;

  beforeEach(() => {
    document.body.innerHTML = html;
    global.ctaMyAccount = { ajaxUrl: '/admin-ajax.php' };
    global.fetch = jest.fn((url) => Promise.resolve({
      ok: true,
      json: () => Promise.resolve({ success: true, data: { html: `<p>${url}</p>`, messages: '' } })
    }));
    jest.spyOn(window.history, 'pushState').mockImplementation(() => {});
    jest.spyOn(window.history, 'replaceState').mockImplementation(() => {});
    initModule = () => {
      jest.resetModules();
      require('../../wp-content/themes/chassesautresor/assets/js/myaccount.js');
      document.dispatchEvent(new Event('DOMContentLoaded'));
    };
  });

  test.each([
    'organisateurs',
    'statistiques',
    'outils'
  ])('loads %s section via ajax', async (section) => {
    initModule();
    const link = document.querySelector(`a[data-section="${section}"]`);
    link.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    await Promise.resolve();
    await Promise.resolve();
    expect(fetch).toHaveBeenCalledWith(`/admin-ajax.php?action=cta_load_admin_section&section=${section}`, expect.any(Object));
    expect(document.querySelector('.myaccount-content').innerHTML).toContain('<section class="msg-important"></section>');
    expect(window.history.pushState).toHaveBeenCalled();
  });

  test('loads section from query parameter', async () => {
    const originalURLSearchParams = URLSearchParams;
    global.URLSearchParams = jest.fn(() => ({ get: () => 'organisateurs' }));
    initModule();
    await Promise.resolve();
    await Promise.resolve();
    expect(fetch).toHaveBeenCalledWith('/admin-ajax.php?action=cta_load_admin_section&section=organisateurs', expect.any(Object));
    expect(window.history.replaceState).toHaveBeenCalledWith(null, '', '/mon-compte/');
    expect(document.querySelector('a[data-section="organisateurs"]').classList.contains('active')).toBe(true);
    global.URLSearchParams = originalURLSearchParams;
  });

  test.skip('falls back to full reload on error', async () => {
    fetch.mockImplementationOnce(() => Promise.resolve({ ok: false }));
    initModule();
    const link = document.querySelector('a[data-section="organisateurs"]');
    link.href = '/mon-compte/organisateurs/';
    link.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    await Promise.resolve();
    await Promise.resolve();
  });
});
