(function () {
  "use strict";

  var root = document.querySelector("[data-progression-player]");
  if (!root) {
    return;
  }

  var endpoint = root.getAttribute("data-ajax-url") || "";
  var nonce = root.getAttribute("data-nonce") || "";
  var huntId = root.getAttribute("data-hunt-id") || "0";
  var errorMessage = root.getAttribute("data-error-message") || "";

  function request(action, values, target) {
    var body = new URLSearchParams({
      action: action,
      nonce: nonce,
      hunt_id: huntId,
    });
    Object.keys(values).forEach(function (key) {
      body.set(key, String(values[key]));
    });
    target.setAttribute("aria-busy", "true");

    return window
      .fetch(endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
      })
      .then(function (response) {
        return response.json();
      })
      .then(function (payload) {
        if (!payload || !payload.success || !payload.data) {
          throw new Error("progression_request_failed");
        }
        target.innerHTML = payload.data.html || "";
      })
      .catch(function () {
        target.innerHTML =
          '<p class="progression-ajax-error" role="alert">' + errorMessage + "</p>";
      })
      .finally(function () {
        target.removeAttribute("aria-busy");
      });
  }

  root.addEventListener("click", function (event) {
    var leaderboardButton = event.target.closest("[data-leaderboard-page]");
    if (leaderboardButton && !leaderboardButton.disabled) {
      var leaderboard = root.querySelector("[data-progression-leaderboard]");
      if (leaderboard) {
        request(
          "cat_player_progression_leaderboard",
          { page: leaderboardButton.getAttribute("data-leaderboard-page") || "1" },
          leaderboard
        );
      }
      return;
    }

    var toggle = event.target.closest("[data-riddle-attempts]");
    if (toggle) {
      var card = toggle.closest(".progression-riddle");
      var panel = card ? card.querySelector("[data-riddle-attempts-panel]") : null;
      var panelRow = card ? card.querySelector("[data-riddle-attempts-row]") : null;
      if (!panel || !panelRow) {
        return;
      }
      var expanded = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", expanded ? "false" : "true");
      panelRow.hidden = expanded;
      if (!expanded && !panel.hasAttribute("data-loaded")) {
        panel.setAttribute("data-loaded", "true");
        panel.setAttribute("data-riddle-id", toggle.getAttribute("data-riddle-attempts") || "0");
        request(
          "cat_player_progression_attempts",
          { riddle_id: panel.getAttribute("data-riddle-id"), page: 1 },
          panel
        );
      }
      return;
    }

    var attemptPage = event.target.closest("[data-attempt-page]");
    if (attemptPage && !attemptPage.disabled) {
      var attemptsPanel = attemptPage.closest("[data-riddle-attempts-panel]");
      if (attemptsPanel) {
        request(
          "cat_player_progression_attempts",
          {
            riddle_id: attemptsPanel.getAttribute("data-riddle-id") || "0",
            page: attemptPage.getAttribute("data-attempt-page") || "1",
          },
          attemptsPanel
        );
      }
    }
  });
})();
