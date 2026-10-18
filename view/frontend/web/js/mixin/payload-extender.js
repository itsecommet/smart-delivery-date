/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * Sends the chosen date with the shipping step (shipping-information REST call).
 */
define([
  "mage/utils/wrapper",
  "Ecommet_SmartDeliveryDate/js/model/delivery-date",
], function (wrapper, model) {
  "use strict";

  return function (payloadExtender) {
    return wrapper.wrap(payloadExtender, function (original, payload) {
      var result = original(payload);

      if (model.isEnabled && result && result.addressInformation) {
        result.addressInformation["extension_attributes"] =
          result.addressInformation["extension_attributes"] || {};
        result.addressInformation["extension_attributes"][
          "ecommet_delivery_date"
        ] = model.selectedDate() || "";
      }

      return result;
    });
  };
});
