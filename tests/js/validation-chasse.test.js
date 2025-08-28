const html = `
<section class="msg-important">
  <p class="message-info">Message <button type="button" class="message-close" data-key="correction_chasse_123">×</button></p>
</section>
<form class="form-validation-chasse">
  <input type="hidden" name="chasse_id" value="123">
  <button type="submit" class="bouton-cta bouton-validation-chasse">VALIDATION</button>
</form>
`;

describe('validation chasse', () => {
  beforeEach(() => {
    document.body.innerHTML = html;
    global.ctaMyAccount = { ajaxUrl: '/admin-ajax.php' };
    global.fetch = jest.fn(() => Promise.resolve());
  });

  test('dismisses correction message on confirmation', () => {
    const form = document.querySelector('.form-validation-chasse');
    form.submit = jest.fn();

    require('../../wp-content/themes/chassesautresor/assets/js/validation-chasse.js');
    document.dispatchEvent(new Event('DOMContentLoaded'));

    const trigger = document.querySelector('.bouton-validation-chasse');
    trigger.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));

    const checkbox = document.querySelector('#confirm-validation');
    checkbox.checked = true;
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));

    const confirmBtn = document.querySelector('.confirmer-envoi');
    confirmBtn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

    expect(fetch).toHaveBeenCalledWith('/admin-ajax.php', expect.objectContaining({
      method: 'POST',
      body: 'action=cta_dismiss_message&key=correction_chasse_123'
    }));
    expect(document.querySelector('.msg-important').innerHTML.trim()).toBe('');
    expect(form.submit).toHaveBeenCalled();
  });
});
