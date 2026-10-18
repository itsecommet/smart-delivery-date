/**
 * Copyright © Ecommet Solutions. All rights reserved.
 * See LICENSE.txt for license details.
 */
var config = {
  config: {
    mixins: {
      "Magento_Checkout/js/model/shipping-save-processor/payload-extender": {
        "Ecommet_SmartDeliveryDate/js/mixin/payload-extender": true,
      },
      "Magento_Checkout/js/view/shipping": {
        "Ecommet_SmartDeliveryDate/js/mixin/shipping": true,
      },
    },
  },
};
