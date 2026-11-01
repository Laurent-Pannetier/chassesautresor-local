const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(
  path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/hunt-lifecycle-switch.js'),
  'utf8'
);

describe('edit switch reveals the add-riddle icon', () => {
  afterEach(() => {
    delete window.ctaHuntLifecycle;
    delete window.ctaSyncAddRiddleAvailability;
    document.body.innerHTML = '';
  });

  beforeEach(() => {
    document.body.innerHTML = `
      <ul id="app-nav-outils-enigmes"></ul>
      <div data-hunt-lifecycle data-hunt-id="7" data-state="active">
        <span data-hunt-lifecycle-badge-label>Active</span>
        <i data-hunt-lifecycle-icon class="fas fa-circle-check"></i>
        <label data-hunt-lifecycle-label-off></label>
        <input type="checkbox" data-hunt-lifecycle-toggle checked>
        <label data-hunt-lifecycle-label-on></label>
        <p data-hunt-lifecycle-help></p>
      </div>
    `;
    window.ctaHuntLifecycle = {
      ajaxUrl: '/ajax',
      nonces: { 7: 'nonce' },
      errorMessage: 'erreur',
    };
    window.alert = () => {};
    eval(source);
  });

  test('shows the icon after Éditer when the server allows adding', async () => {
    window.ctaSyncAddRiddleAvailability = jest.fn();
    global.fetch = jest.fn().mockResolvedValue({
      json: async () => ({
        success: true,
        data: {
          view: {
            checked: false,
            disabled: false,
            state: 'editable',
            badge_label: 'Éditable',
            badge_icon: 'fa-pen',
            help: '',
            can_add_riddle: true,
            add_riddle_url: 'https://example.test/creer-enigme/?chasse_id=7&nonce=abc',
          },
        },
      }),
    });

    const toggle = document.querySelector('[data-hunt-lifecycle-toggle]');
    toggle.checked = false;
    toggle.dispatchEvent(new Event('change'));
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(window.ctaSyncAddRiddleAvailability).toHaveBeenCalledWith(
      true,
      'https://example.test/creer-enigme/?chasse_id=7&nonce=abc'
    );
    expect(document.querySelector('[data-hunt-lifecycle]').getAttribute('data-state')).toBe('editable');
  });

  test('hides the icon after Activer when adding is no longer allowed', async () => {
    window.ctaSyncAddRiddleAvailability = jest.fn();
    global.fetch = jest.fn().mockResolvedValue({
      json: async () => ({
        success: true,
        data: {
          view: {
            checked: true,
            disabled: false,
            state: 'active',
            badge_label: 'Active',
            badge_icon: 'fa-circle-check',
            help: '',
            can_add_riddle: false,
            add_riddle_url: '',
          },
        },
      }),
    });

    const toggle = document.querySelector('[data-hunt-lifecycle-toggle]');
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change'));
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(window.ctaSyncAddRiddleAvailability).toHaveBeenCalledWith(false, '');
  });
});
