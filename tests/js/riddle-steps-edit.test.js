const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(
  path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/riddle-steps-edit.js'),
  'utf8'
);

describe('riddle steps editor widget visibility', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <section class="riddle-steps-editor" data-riddle-id="7" data-structure-locked="1">
        <div class="riddle-steps-editor__overview">
          <ol class="riddle-steps-editor__list">
            <li class="riddle-step-card" data-step-id="12">
              <button type="button" class="riddle-step-edit">Modifier</button>
            </li>
          </ol>
          <p class="riddle-steps-editor__feedback"></p>
        </div>
        <form class="riddle-step-form" hidden>
          <h3 class="riddle-step-form__heading"></h3>
          <input type="hidden" name="etape_id" value="">
          <input name="titre" type="text">
          <div class="riddle-step-form__content-editor" contenteditable="true"></div>
          <textarea name="contenu" hidden></textarea>
          <input type="hidden" name="image_id" value="">
          <div class="riddle-step-form__image-preview"></div>
          <button type="button" class="riddle-step-image-remove" hidden></button>
          <fieldset data-widget-readonly="1">
            <select name="widget" disabled>
              <option value="click">Simple clic</option>
              <option value="directions">Pavé</option>
            </select>
            <div class="riddle-step-widget-config" data-widget="click">
              <input name="button_label" value="Continuer" disabled>
            </div>
            <div class="riddle-step-widget-config" data-widget="directions" hidden>
              <textarea name="direction_sequences" disabled></textarea>
            </div>
            <input name="accepted_answers" disabled>
            <input name="case_sensitive" type="checkbox" disabled>
            <textarea name="variants" disabled></textarea>
            <textarea name="color_sequences" disabled></textarea>
            <textarea name="number_sequences" disabled></textarea>
            <textarea name="safe_dial_sequences" disabled></textarea>
            <textarea name="piano_sequences" disabled></textarea>
            <input name="gps_coordinates" disabled>
            <input name="gps_tolerance" disabled>
          </fieldset>
          <select name="widget_affichage">
            <option value="always">Toujours visible</option>
            <option value="hotspot">Point & click</option>
          </select>
          <div class="riddle-step-hotspot-editor__canvas" hidden>
            <div class="riddle-step-hotspot-editor__stage">
              <img class="riddle-step-hotspot-editor__image" hidden>
              <div class="riddle-step-hotspot-editor__zone" hidden></div>
            </div>
            <input type="hidden" name="hotspot_zone" value="">
            <button type="button" class="riddle-step-hotspot-clear" hidden></button>
          </div>
          <p class="riddle-step-form__feedback"></p>
        </form>
      </section>
    `;

    global.RiddleStepsEdit = {
      ajaxUrl: '/ajax',
      nonce: 'nonce',
      riddleId: 7,
      texts: {
        editTitle: 'Modifier l’étape',
        newTitle: 'Nouvelle étape',
        edit: 'Modifier',
        error: 'Erreur',
        confirmDelete: 'Supprimer ?',
        imageTitle: 'Image',
      },
    };
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  test('loads and shows the answer widget even when the structure is locked', async () => {
    global.fetch = jest.fn().mockResolvedValue({
      json: () => Promise.resolve({
        success: true,
        data: {
          title: 'Coffre',
          content: '<p>Ouvre le coffre</p>',
          image_id: 9,
          image_url: 'preview.jpg',
          widget: 'directions',
          button_label: 'Continuer',
          direction_sequences: 'N,E,S',
          widget_affichage: 'always',
          hotspot_zone: '',
        },
      }),
    });

    eval(source);
    document.dispatchEvent(new Event('DOMContentLoaded'));

    document.querySelector('.riddle-step-edit').dispatchEvent(
      new MouseEvent('click', { bubbles: true, cancelable: true })
    );
    await new Promise(resolve => window.setTimeout(resolve, 0));

    const form = document.querySelector('.riddle-step-form');
    expect(form.hidden).toBe(false);
    expect(form.querySelector('[name="widget"]').value).toBe('directions');
    expect(form.querySelector('[name="widget"]').disabled).toBe(true);
    expect(form.querySelector('[data-widget="directions"]').hidden).toBe(false);
    expect(form.querySelector('[name="direction_sequences"]').value).toBe('N,E,S');
    expect(form.querySelector('[data-widget="click"]').hidden).toBe(true);
  });
});
