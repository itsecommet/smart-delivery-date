# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the module uses
[Semantic Versioning](https://semver.org/).

## [2.0.0] - 2026-10-18

Complete rewrite.

### Added
- Customer-selected delivery date at one-page checkout: a calendar in which excluded dates,
  carrier days off and dates before the earliest delivery date cannot be chosen.
- Server-side validation of the chosen date when the shipping step is saved and again when the
  order is placed (also for REST API orders). Excluded dates can no longer be ordered.
- Multi-address checkout: estimate on the address step; estimate and delivery date per address
  on the shipping step; summary on the review step; date saved on each order.
- "Required" option, selectable period, custom field label and note.
- Recurring ("every year") excluded dates.
- Per-product "Processing Time (days)" attribute; in the cart the slowest product decides.
- Delivery date on the admin order view, the order grid (column and filter), the customer
  account order view, order e-mails (`{{var ecommet_delivery_date}}`) and the order REST API.
- Hyvä templates (Alpine.js, strict-CSP compatible) and Tailwind registration.
- Per-store-view messages, date format (ICU, localised) and visibility per page type.
- Option to hide the estimate for out-of-stock products.
- Unit tests for the date engine.
- User guide (`docs/USER_GUIDE.md`).

### Fixed
- The cut-off time was compared in a hard-coded timezone (Asia/Kolkata); all dates now use the
  store timezone.
- The countdown dropped whole days ("30 hours" showed as "6h").
- Storefront texts ("between", "and", "on", "Hurry! …") were not translatable; dates were
  always in English.
- A configuration without working days made the calculation loop forever.
- The admin date picker used an inline script that is blocked by the 2.4.7+ Content Security
  Policy; it now uses a native date input.

### Changed
- Requires Magento 2.4.6+ and PHP 8.1 – 8.5.
- Configuration paths are unchanged; existing settings and excluded dates are kept.
- The ACL resource is now `Ecommet_SmartDeliveryDate::config`. Re-assign it to admin roles that
  had `Ecommet_SmartDeliveryDate::smartdeliverydate`.
- The PHP service interfaces of 1.x (`Api\*ServiceInterface`) and the helper were removed in
  favour of `Model\EstimateCalculator` and `Model\DeliveryDateService`.
- The storefront endpoint moved from `smartdelivery/delivery/ajax` to `smartdelivery/estimate/index`.

## [1.0.0]

- First release: estimated delivery date on product, cart and checkout pages.
