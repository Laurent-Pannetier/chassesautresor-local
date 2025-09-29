const fs = require('fs');
const path = require('path');

const script = fs.readFileSync(
  path.resolve(__dirname, '../../wp-content/themes/chassesautresor/assets/js/core/champ-init.js'),
  'utf8'
);
eval(script);
global.initChampDeclencheur = initChampDeclencheur;
global.initZoneClicEdition = initZoneClicEdition;

describe('initChampDeclencheur', () => {

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

describe('initZoneClicEdition', () => {
  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('n’active pas la zone lorsque le champ est désactivé', () => {
    document.body.innerHTML = `
      <ul>
        <li class="champ-chasse champ-desactive" data-champ="chasse_principale_image">
          <button class="champ-modifier" aria-disabled="true"></button>
        </li>
      </ul>`;

    const zone = document.querySelector('.champ-chasse');
    const bouton = zone.querySelector('.champ-modifier');
    const zoneSpy = jest.spyOn(zone, 'addEventListener');

    initZoneClicEdition(bouton);

    expect(zone.style.cursor).toBe('default');
    expect(zoneSpy).not.toHaveBeenCalled();

    zoneSpy.mockRestore();
  });

  it('ajoute un gestionnaire lorsque le champ est actif', () => {
    document.body.innerHTML = `
      <ul>
        <li class="champ-chasse" data-champ="chasse_principale_image">
          <button class="champ-modifier"></button>
        </li>
      </ul>`;

    const zone = document.querySelector('.champ-chasse');
    const bouton = zone.querySelector('.champ-modifier');
    const zoneSpy = jest.spyOn(zone, 'addEventListener');

    initZoneClicEdition(bouton);

    expect(zone.style.cursor).toBe('pointer');
    expect(zoneSpy).toHaveBeenCalledTimes(1);

    zoneSpy.mockRestore();
  });
});
