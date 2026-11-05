const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(
  path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/reponse-automatique.js'),
  'utf8'
);

const flush = async () => {
  for (let step = 0; step < 12; step += 1) {
    await Promise.resolve();
  }
};

describe('automatic reply success', () => {
  beforeEach(() => {
    jest.useFakeTimers();
    document.body.innerHTML = `
      <div class="reponse-launcher" data-riddle-reply-key="final-9">
        <form class="formulaire-reponse-auto is-reply-widget" data-riddle-reply-key="final-9">
          <h3>Votre réponse</h3>
          <div class="reponse-feedback"></div>
          <input name="reponse" value="orpheline">
          <input type="hidden" name="enigme_id" value="9">
          <input type="hidden" name="nonce" value="abc">
          <button type="submit">Valider</button>
        </form>
      </div>
    `;
    window.closeImmersiveReplyWidget = (form) => {
      form.hidden = true;
      form.classList.remove('is-immersive-open');
      document.body.classList.remove('riddle-widget-immersive-open');
      document.querySelector('.riddle-widget-immersive-backdrop')?.remove();
    };
    global.fetch = jest.fn(() => Promise.resolve({
      text: async () => JSON.stringify({
        success: true,
        data: { resultat: 'bon', points: 12, retry: { blocked: false } }
      })
    }));
    eval(source);
    document.dispatchEvent(new Event('DOMContentLoaded'));
  });

  afterEach(() => {
    jest.useRealTimers();
    delete window.closeImmersiveReplyWidget;
    delete global.fetch;
    document.body.innerHTML = '';
    document.body.className = '';
  });

  test('keeps the confirmation, then retires the inline widget', async () => {
    document.querySelector('form').dispatchEvent(
      new Event('submit', { bubbles: true, cancelable: true })
    );
    await flush();

    expect(document.querySelector('.reponse-feedback').textContent).toContain('Bonne réponse');
    expect(document.querySelector('form.is-reply-widget')).not.toBeNull();

    jest.advanceTimersByTime(1500);

    expect(document.querySelector('form.is-reply-widget')).toBeNull();
    expect(document.querySelector('.riddle-widget-immersive-backdrop')).toBeNull();
    expect(document.body.classList.contains('riddle-widget-immersive-open')).toBe(false);
    expect(document.querySelector('.reponse-launcher__solved').textContent).toContain('Énigme résolue');
  });
});
