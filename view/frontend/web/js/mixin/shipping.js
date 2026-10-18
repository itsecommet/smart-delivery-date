/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * Blocks "Next" on the shipping step until a valid delivery date is chosen (when required).
 * The server checks again, so this is only for quick feedback.
 */
define(["Ecommet_SmartDeliveryDate/js/model/delivery-date"], function (model) {
  "use strict";

  return function (Shipping) {
    return Shipping.extend({
      /** @inheritdoc */
      validateShippingInformation: function () {
        var isValid = this._super(),
          isDateValid = model.validate();

        return isValid && isDateValid;
      },
    });
  };
});
