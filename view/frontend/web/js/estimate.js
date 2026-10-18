/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * Luma estimate widget: fetches the estimate, runs the countdown and refreshes after the cut-off.
 */
define(["Ecommet_SmartDeliveryDate/js/model/message"], function (message) {
  "use strict";

  return function (config, element) {
    var estimate = null,
      deadline = 0,
      ticker = null,
      refresher = null,
      textElement =
        element.querySelector('[data-role="ecommet-estimate-text"]') || element;

    function secondsLeft() {
      return Math.max(0, Math.round((deadline - Date.now()) / 1000));
    }

    function render() {
      message.render(
        textElement,
        message.build(estimate, config.phrases, secondsLeft()),
      );
    }

    function load() {
      clearInterval(ticker);
      clearTimeout(refresher);

      fetch(config.url, {
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then(function (response) {
          return response.ok ? response.json() : { success: false };
        })
        .then(function (data) {
          if (!data.success) {
            element.hidden = true;
            return;
          }
          estimate = data.estimate;
          // Count down from the server's figure, so a wrong clock on the device does not matter.
          deadline = Date.now() + Math.max(1, estimate.secondsToCutoff) * 1000;
          render();
          element.hidden = false;

          if (config.phrases.showCountdown) {
            ticker = setInterval(render, 1000);
          }
          // setTimeout cannot wait longer than ~24.8 days.
          refresher = setTimeout(
            load,
            Math.min(deadline - Date.now() + 1000, 2147483000),
          );
        })
        .catch(function () {
          element.hidden = true;
        });
    }

    load();
  };
});
