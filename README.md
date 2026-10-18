# Ecommet Smart Delivery Date for Magento 2

Show shoppers when their order will arrive, and let them choose a delivery date at checkout
that you can actually deliver on.

* **Estimated delivery** on the product page, in the cart and at checkout:
  "Order within **2h 15m** and get it on **Friday, October 23**".
* **Customer-selected delivery date** in one-page and **multi-address** checkout. Excluded
  dates, carrier days off and too-early dates are blocked in the calendar **and re-checked on
  the server when the order is placed**.
* Processing days, carrier delivery days, same-day **cut-off time** and **excluded dates**
  (one-off or every year), all in the **store timezone**.
* Optional **delivery window** ("between Monday and Wednesday").
* **Per-product processing time** attribute; in the cart the slowest product decides.
* Chosen date on the **admin order view**, **order grid** (filterable), **customer account**,
  **order e-mails** (`{{var ecommet_delivery_date}}`) and the **REST API**.
* **Luma and Hyvä** (Alpine.js, CSP compatible) templates in one module.
* Translatable, with customisable messages and date format per store view.

## Requirements

| | |
| --- | --- |
| Magento Open Source / Adobe Commerce | 2.4.6 – 2.4.9 |
| PHP | 8.1 – 8.5 |
| Themes | Luma (and Luma-based), Hyvä 1.3+ with Luma checkout fallback |

## Installation

```bash
composer require ecommet/module-smart-delivery-date
bin/magento module:enable Ecommet_SmartDeliveryDate
bin/magento setup:upgrade
bin/magento cache:flush
```

Then configure it in **Stores → Configuration → Ecommet Extensions → Smart Delivery Date**.

The [User Guide](docs/USER_GUIDE.md) covers installation, every setting, how the date is
calculated (with a worked example), multi-address checkout, Hyvä setup and troubleshooting.

## For developers

| | |
| --- | --- |
| Date engine | `Model\EstimateCalculator` (estimate and selectable dates), `Model\Calendar\WorkCalendar` |
| Validation | `Model\DeliveryDateService::validate()`: used by the shipping-information plugin, the `sales_model_service_quote_submit_before` observer and the multishipping observers |
| Storefront data | `GET smartdelivery/estimate/index?product_id=<id>` or `?cart=1` (not cacheable; product and cart pages stay full-page cached) |
| Checkout | `window.checkoutConfig.ecommetDeliveryDate`; date sent as `addressInformation.extension_attributes.ecommet_delivery_date` |
| Storage | `ecommet_delivery_date` (DATE) on `quote`, `quote_address`, `sales_order`, `sales_order_grid` |
| API | `OrderInterface` extension attribute `ecommet_delivery_date` |
| Hyvä | `hyva_*` layout handles switch to `templates/hyva/*`; `hyva_config_generate_before` registers the module for Tailwind |

Run the unit tests from the Magento root:

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Ecommet/SmartDeliveryDate/Test/Unit
```

## Support

📧 [ecommet.solutions@gmail.com](mailto:ecommet.solutions@gmail.com) · 🌐 [ecommet.in](https://ecommet.in)

## License

Proprietary. See [LICENSE.txt](LICENSE.txt).
