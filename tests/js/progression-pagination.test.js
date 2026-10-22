describe('player progression pagination', () => {
  beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = `
      <section data-progression-player data-ajax-url="/ajax" data-nonce="player-nonce"
        data-hunt-id="12" data-error-message="Erreur">
        <div data-progression-leaderboard>
          <button data-leaderboard-page="2">2</button>
        </div>
        <article class="progression-riddle">
          <button data-riddle-attempts="40" aria-expanded="false">Soumissions</button>
          <div data-riddle-attempts-row hidden>
            <div data-riddle-attempts-panel></div>
          </div>
        </article>
      </section>`;
    global.fetch = jest.fn(() => new Promise(() => {}));
  });

  test('requests a leaderboard page without reloading', () => {
    require('../../wp-content/themes/chassesautresor/assets/js/player-progression.js');
    document.querySelector('[data-leaderboard-page]').click();

    expect(fetch).toHaveBeenCalledWith('/ajax', expect.objectContaining({
      method: 'POST',
      body: 'action=cat_player_progression_leaderboard&nonce=player-nonce&hunt_id=12&page=2',
    }));
  });

  test('opens and loads only the current player submissions', () => {
    require('../../wp-content/themes/chassesautresor/assets/js/player-progression.js');
    const button = document.querySelector('[data-riddle-attempts]');
    button.click();

    expect(button.getAttribute('aria-expanded')).toBe('true');
    expect(document.querySelector('[data-riddle-attempts-row]').hidden).toBe(false);
    expect(fetch).toHaveBeenCalledWith('/ajax', expect.objectContaining({
      method: 'POST',
      body: 'action=cat_player_progression_attempts&nonce=player-nonce&hunt_id=12&riddle_id=40&page=1',
    }));
  });
});

describe('riddle ranking pagination', () => {
  beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = `
      <section data-player-ranking data-ajax-url="/ajax" data-nonce="player-nonce"
        data-hunt-id="12" data-error-message="Erreur">
        <div data-progression-leaderboard>
          <button data-leaderboard-page="2">2</button>
        </div>
        <div data-riddle-ranking data-riddle-id="40">
          <button data-riddle-ranking-page="3">3</button>
        </div>
      </section>`;
    global.fetch = jest.fn(() => new Promise(() => {}));
  });

  test('requests one riddle ranking page without reloading', () => {
    require('../../wp-content/themes/chassesautresor/assets/js/player-progression.js');
    document.querySelector('[data-riddle-ranking-page]').click();

    expect(fetch).toHaveBeenCalledWith('/ajax', expect.objectContaining({
      method: 'POST',
      body: 'action=cat_player_progression_riddle_ranking&nonce=player-nonce&hunt_id=12&riddle_id=40&page=3',
    }));
  });
});

describe('team ranking pagination', () => {
  beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = `
      <section data-team-performance data-ajax-url="/ajax" data-nonce="team-nonce"
        data-hunt-id="12" data-error-message="Erreur">
        <div data-team-ranking><button data-team-ranking-page="3">3</button></div>
      </section>`;
    global.fetch = jest.fn(() => new Promise(() => {}));
  });

  test('requests the selected team ranking page', () => {
    require('../../wp-content/themes/chassesautresor/assets/js/team-performance.js');
    document.querySelector('[data-team-ranking-page]').click();

    expect(fetch).toHaveBeenCalledWith('/ajax', expect.objectContaining({
      method: 'POST',
      body: 'action=cat_team_performance_ranking&nonce=team-nonce&hunt_id=12&page=3',
    }));
  });
});
