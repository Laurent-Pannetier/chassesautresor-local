describe('portable public fallback', () => {
  beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = `
      <section class="participation">
        <form class="formulaire-reponse-automatique">
          <textarea name="reponse">trésor</textarea>
          <input name="nonce" value="answer-nonce">
        </form>
        <div class="cat-core-feedback"></div>
        <div class="zone-indices">
          <a href="#" class="indice-link" data-indice-id="42" data-cout="0" data-unlocked="0">Indice</a>
          <div class="indice-display"></div>
        </div>
      </section>`;
    window.chassesAuTresorCore = {
      ajaxUrl: '/wp-admin/admin-ajax.php',
      errorMessage: 'Erreur',
      successMessage: 'Enregistré',
      hintNonce: 'hint-nonce',
      unlockHint: 'Débloquer',
      close: 'Fermer'
    };
  });

  test('submits automatic answers and unlocks hints through Core endpoints', async () => {
    global.fetch = jest.fn()
      .mockResolvedValueOnce({ json: () => Promise.resolve({ success: true, data: { message: 'Bravo' } }) })
      .mockResolvedValueOnce({
        json: () => Promise.resolve({ success: true, data: { html: '<p>Contenu</p>', points: 10 } })
      });
    require('../../wp-content/plugins/chassesautresor-core/assets/js/public.js');

    document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    await new Promise((resolve) => setTimeout(resolve, 0));
    const answerData = fetch.mock.calls[0][1].body;
    expect(answerData.get('action')).toBe('soumettre_reponse_automatique');
    expect(answerData.get('nonce')).toBe('answer-nonce');
    expect(document.querySelector('.cat-core-feedback').textContent).toBe('Bravo');

    document.querySelector('.indice-link').dispatchEvent(
      new MouseEvent('click', { bubbles: true, cancelable: true })
    );
    await new Promise((resolve) => setTimeout(resolve, 0));
    const hintData = fetch.mock.calls[1][1].body;
    expect(hintData.get('action')).toBe('debloquer_indice');
    expect(hintData.get('indice_id')).toBe('42');
    expect(hintData.get('nonce')).toBe('hint-nonce');
    expect(document.querySelector('.indice-display').textContent).toContain('Contenu');
  });
});
