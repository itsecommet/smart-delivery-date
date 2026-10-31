/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * Shipping step: delivery estimate and the delivery date calendar. Days that cannot be delivered
 * (carrier days off, excluded dates, too early, too far ahead) are disabled in the calendar.
 */
define([
  "jquery",
  "ko",
  "uiComponent",
  "Ecommet_SmartDeliveryDate/js/model/delivery-date",
  "Ecommet_SmartDeliveryDate/js/model/message",
  "mage/calendar",
], function ($, ko, Component, model, message) {
  "use strict";

  /**
   * @param {Date} date
   * @return {String} Y-m-d
   */
  function toKey(date) {
    return [
      date.getFullYear(),
      ("0" + (date.getMonth() + 1)).slice(-2),
      ("0" + date.getDate()).slice(-2),
    ].join("-");
  }

  /**
   * @param {String} key Y-m-d
   * @return {Date}
   */
  function fromKey(key) {
    var parts = key.split("-");

    return new Date(+parts[0], +parts[1] - 1, +parts[2]);
  }

  return Component.extend({
    defaults: {
      template: "Ecommet_SmartDeliveryDate/checkout/delivery-date",
    },

    model: model,
    input: null,

    /** @inheritdoc */
    initialize: function () {
      var config = model.config;

      this._super();
      this.segments = ko.observableArray([]);
      this.isVisible =
        model.isActive && (config.showEstimate || model.isEnabled);
      this.selection = config.selection || {};
      this.inputId = "ecommet-delivery-date";
      this.errorId = "ecommet-delivery-date-error";

      if (model.isActive) {
        this.setEstimate(config.estimate);
      }

      return this;
    },

    /**
     * @param {Object} estimate
     */
    setEstimate: function (estimate) {
      var self = this,
        deadline = Date.now() + Math.max(1, estimate.secondsToCutoff) * 1000,
        render = function () {
          var seconds = Math.max(0, Math.round((deadline - Date.now()) / 1000));

          self.segments(
            model.config.showEstimate
              ? message.build(estimate, model.config.phrases, seconds)
              : [],
          );
        };

      clearInterval(this.ticker);
      clearTimeout(this.refresher);
      render();
      if (model.config.phrases.showCountdown) {
        this.ticker = setInterval(render, 1000);
      }
      this.refresher = setTimeout(
        this.refresh.bind(this),
        Math.min(deadline - Date.now() + 1000, 2147483000),
      );
    },

    /**
     * After the cut-off the earliest date moves on: reload the estimate and the selectable dates
     */
    refresh: function () {
      var self = this;

      $.ajax({
        url: model.config.url,
        type: "GET",
        dataType: "json",
        global: false,
      }).done(function (data) {
        if (!data || !data.success) {
          return;
        }
        self.setEstimate(data.estimate);
        model.availableDates(data.selectableDates || []);
        if (self.input) {
          $(self.input).datepicker("refresh");
        }
        if (model.selectedDate() && !model.isAvailable(model.selectedDate())) {
          model.validate();
        }
      });
    },

    /**
     * @param {HTMLElement} element
     */
    initCalendar: function (element) {
      var dates = model.availableDates(),
        selected = model.selectedDate();

      this.input = element;
      $(element).calendar({
        showOn: "both",
        changeMonth: false,
        changeYear: false,
        showButtonPanel: false,
        minDate: dates.length ? fromKey(dates[0]) : 0,
        maxDate: dates.length ? fromKey(dates[dates.length - 1]) : 0,
        beforeShowDay: function (date) {
          var available = model.isAvailable(toKey(date));

          return [
            available,
            available ? "ecommet-available" : "ecommet-unavailable",
          ];
        },
        onSelect: function () {
          var date = $(element).datepicker("getDate");

          model.selectedDate(date ? toKey(date) : "");
        },
      });
      if (selected) {
        $(element).datepicker("setDate", fromKey(selected));
      }
    },

    /**
     * Remove the chosen date (optional field only)
     */
    clear: function () {
      model.selectedDate("");
      if (this.input) {
        $(this.input).datepicker("setDate", null);
      }
    },
  });
});
