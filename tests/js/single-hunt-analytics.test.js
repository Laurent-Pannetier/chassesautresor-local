/**
 * @jest-environment jsdom
 */

describe('single-hunt analytics', () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.className = '';
        document.body.innerHTML = '';
        window.gtag = jest.fn();
    });

    test('tracks an explicit homepage action without personal data', () => {
        document.body.innerHTML = `
            <a href="/enigmes" data-single-hunt-event="single_hunt_riddles_access">Accéder aux énigmes</a>
        `;

        require('../../wp-content/themes/chassesautresor/assets/js/single-hunt-analytics.js');
        document.querySelector('a').click();

        expect(window.gtag).toHaveBeenCalledWith('event', 'single_hunt_riddles_access', {
            action_label: 'Accéder aux énigmes',
            destination: '/enigmes',
        });
    });

    test('tracks a riddle resolution emitted by the answer workflow', () => {
        require('../../wp-content/themes/chassesautresor/assets/js/single-hunt-analytics.js');
        document.dispatchEvent(new CustomEvent('cta:riddle-resolved'));

        expect(window.gtag).toHaveBeenCalledWith('event', 'single_hunt_riddle_resolved', {});
    });
});
