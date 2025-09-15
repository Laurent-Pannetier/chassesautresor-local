const fs = require('fs');
const path = require('path');

describe('initChampDeclencheur', () => {
  let script;

  beforeAll(() => {
    script = fs.readFileSync(
      path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/core/champ-init.js'),
      'utf8'
    );
    eval(script);
    global.initChampDeclencheur = initChampDeclencheur;
  });

  beforeEach(() => {
    global.initChampImage = jest.fn();
  });

  it('ouvre la médiathèque si le champ est vide', () => {
    document.body.innerHTML = `
      <div class="champ-enigme champ-img champ-vide" data-champ="illustration" data-post-id="1" data-cpt="enigme">
        <button class="champ-modifier trigger" data-champ="illustration" data-post-id="1" data-cpt="enigme"></button>
        <button class="champ-modifier real"></button>
      </div>`;

    const bloc = document.querySelector('.champ-enigme');
    bloc.__ouvrirMedia = jest.fn();
    const trigger = bloc.querySelector('.trigger');
    const vrai = bloc.querySelector('.real');
    vrai.click = jest.fn();

    initChampDeclencheur(trigger);
    trigger.click();

    expect(bloc.__ouvrirMedia).toHaveBeenCalledTimes(1);
    expect(vrai.click).not.toHaveBeenCalled();
  });

  it('ouvre le panneau si une illustration existe', () => {
    document.body.innerHTML = `
      <div class="champ-enigme champ-img champ-rempli" data-champ="illustration" data-post-id="1" data-cpt="enigme">
        <button class="champ-modifier trigger" data-champ="illustration" data-post-id="1" data-cpt="enigme"></button>
        <button class="champ-modifier real"></button>
      </div>`;

    const bloc = document.querySelector('.champ-enigme');
    bloc.__ouvrirMedia = jest.fn();
    const trigger = bloc.querySelector('.trigger');
    const vrai = bloc.querySelector('.real');
    vrai.click = jest.fn();

    initChampDeclencheur(trigger);
    trigger.click();

    expect(bloc.__ouvrirMedia).not.toHaveBeenCalled();
    expect(vrai.click).toHaveBeenCalledTimes(1);
  });

  it("n'ouvre pas la médiathèque si le bouton ouvre un panneau images", () => {
    document.body.innerHTML = `
      <div class="champ-enigme champ-img champ-vide" data-champ="illustration" data-post-id="1" data-cpt="enigme">
        <button class="champ-modifier trigger ouvrir-panneau-images" data-champ="illustration" data-post-id="1" data-cpt="enigme"></button>
      </div>`;

    const bloc = document.querySelector('.champ-enigme');
    bloc.__ouvrirMedia = jest.fn();
    const trigger = bloc.querySelector('.trigger');

    initChampDeclencheur(trigger);
    trigger.click();

    expect(bloc.__ouvrirMedia).not.toHaveBeenCalled();
  });
});
