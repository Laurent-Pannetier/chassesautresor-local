describe('enigme-cards-reorder dragover behavior', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div class="cards-grid" data-chasse-id="1">
        <div id="card1" class="carte-enigme" data-enigme-id="1" draggable="true"></div>
        <div id="card2" class="carte-enigme" data-enigme-id="2" draggable="true"></div>
      </div>`;
    global.wp = { i18n: { __: (s) => s } };
    jest.resetModules();
    require('../../wp-content/themes/chassesautresor/assets/js/enigme-cards-reorder.js');
    document.dispatchEvent(new Event('DOMContentLoaded'));
  });

  test('reorders based on dominant axis', () => {
    const grid = document.querySelector('.cards-grid');
    const card1 = document.getElementById('card1');
    const card2 = document.getElementById('card2');
    card2.getBoundingClientRect = () => ({ left: 100, top: 0, width: 100, height: 100 });

    const dataTransfer = { setData: jest.fn() };
    const dragstart = new Event('dragstart', { bubbles: true });
    dragstart.dataTransfer = dataTransfer;
    Object.defineProperty(dragstart, 'clientX', { value: 0 });
    Object.defineProperty(dragstart, 'clientY', { value: 0 });
    card1.dispatchEvent(dragstart);

    const dragoverAt = (x, y) => {
      const ev = new Event('dragover', { bubbles: true });
      ev.dataTransfer = dataTransfer;
      ev.preventDefault = () => {};
      Object.defineProperty(ev, 'clientX', { value: x });
      Object.defineProperty(ev, 'clientY', { value: y });
      card2.dispatchEvent(ev);
    };

    dragoverAt(200, 10);
    expect(Array.from(grid.children).map((el) => el.id)).toEqual(['card2', 'card1']);

    dragoverAt(100, 10);
    expect(Array.from(grid.children).map((el) => el.id)).toEqual(['card1', 'card2']);

    dragoverAt(150, 200);
    expect(Array.from(grid.children).map((el) => el.id)).toEqual(['card2', 'card1']);

    dragoverAt(150, -50);
    expect(Array.from(grid.children).map((el) => el.id)).toEqual(['card1', 'card2']);
  });
});

