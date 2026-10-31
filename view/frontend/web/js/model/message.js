/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * Builds the estimate message as text segments, so it can be rendered without innerHTML.
 * The Hyvä template (view/frontend/templates/hyva/estimate.phtml) mirrors this logic.
 */
define([], function () {
  "use strict";

  /**
   * @param {Number} seconds
   * @param {Object} units translated "{n}d", "{n}h", "{n}m", "{n}s"
   * @return {String}
   */
  function formatCountdown(seconds, units) {
    var days = Math.floor(seconds / 86400),
      hours = Math.floor((seconds % 86400) / 3600),
      minutes = Math.floor((seconds % 3600) / 60),
      parts = [];

    if (days) {
      parts.push(units.d.replace("{n}", days));
    }
    if (days || hours) {
      parts.push(units.h.replace("{n}", hours));
    }
    parts.push(units.m.replace("{n}", minutes));
    if (!days) {
      parts.push(units.s.replace("{n}", seconds % 60));
    }

    return parts.join(" ");
  }

  /**
   * Split "text {key} text" into segments, replacing each {key} with values[key] (an array of segments)
   *
   * @param {String} template
   * @param {Object} values
   * @return {Array}
   */
  function fill(template, values) {
    var result = [];

    String(template)
      .split(/(\{[a-z]+\})/)
      .forEach(function (piece) {
        var key = piece.slice(1, -1);

        if (piece.charAt(0) === "{" && values.hasOwnProperty(key)) {
          result = result.concat(values[key]);
        } else if (piece !== "") {
          result.push({ text: piece, strong: false });
        }
      });

    return result;
  }

  return {
    /**
     * @param {Object} estimate from the server: earliestLabel, latestLabel
     * @param {Object} phrases from the server
     * @param {Number} secondsToCutoff
     * @return {Array} [{text, strong}]
     */
    build: function (estimate, phrases, secondsToCutoff) {
      var delivery = estimate.latestLabel
        ? fill(phrases.range, {
            from: [{ text: estimate.earliestLabel, strong: true }],
            to: [{ text: estimate.latestLabel, strong: true }],
          })
        : fill(phrases.single, {
            date: [{ text: estimate.earliestLabel, strong: true }],
          });

      if (phrases.showCountdown && secondsToCutoff > 0) {
        return fill(phrases.messageCountdown, {
          countdown: [
            {
              text: formatCountdown(secondsToCutoff, phrases.units),
              strong: true,
            },
          ],
          delivery: delivery,
        });
      }

      return fill(phrases.message, { delivery: delivery });
    },

    /**
     * Render segments into an element
     *
     * @param {HTMLElement} element
     * @param {Array} segments
     */
    render: function (element, segments) {
      element.textContent = "";
      segments.forEach(function (segment) {
        var node = segment.strong
          ? document.createElement("strong")
          : document.createTextNode(segment.text);

        if (segment.strong) {
          node.textContent = segment.text;
        }
        element.appendChild(node);
      });
    },
  };
});
