/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * One-page checkout state shared by the delivery-date component and the shipping-step mixins.
 */
define(["ko", "mage/translate"], function (ko, $t) {
  "use strict";

  var config = (window.checkoutConfig &&
      window.checkoutConfig.ecommetDeliveryDate) || { active: false },
    selection = config.selection || {},
    model = {
      config: config,
      isActive: !!config.active,
      isEnabled: !!(config.active && selection.enabled),
      isRequired: !!(config.active && selection.enabled && selection.required),
      availableDates: ko.observableArray(selection.dates || []),
      selectedDate: ko.observable(selection.selected || ""),
      error: ko.observable(""),

      /**
       * @param {String} date Y-m-d
       * @return {Boolean}
       */
      isAvailable: function (date) {
        return model.availableDates.indexOf(date) !== -1;
      },

      /**
       * @return {Boolean}
       */
      validate: function () {
        var date = model.selectedDate();

        if (!model.isEnabled) {
          return true;
        }
        if (!date && model.isRequired) {
          model.error($t("Please choose a delivery date."));
          return false;
        }
        if (date && !model.isAvailable(date)) {
          model.error(
            $t(
              "This delivery date is no longer available. Please choose another date.",
            ),
          );
          return false;
        }
        model.error("");

        return true;
      },
    };

  model.selectedDate.subscribe(function () {
    model.error("");
  });

  return model;
});
