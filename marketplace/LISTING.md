# Commerce Marketplace listing: Smart Delivery Date 2.0.0

Copy for the Adobe Commerce Marketplace listing form. Checked against the
[listing content](https://developer.adobe.com/commerce/marketplace/guides/sellers/content) and
[marketing review](https://developer.adobe.com/commerce/marketplace/guides/sellers/marketing-review-guidelines)
guidelines (October 2026).

## Assets in this folder

| File | Listing field | Notes |
| --- | --- | --- |
| `icon.png` (512×512), `icon.svg` | Product icon | No text, no Adobe/Magento marks, no developer logo |
| `gallery/01-cover.png` … `gallery/15-*.png` | Image gallery | 15 images (the maximum), PNG, each under 1 MB. Upload in numeric order; image 1 becomes the main image |
| `user-guide.pdf` | Documentation | 16 pages, 1.7 MB (limit 5 MB), English, covers every feature |
| `../` package | Technical submission | Build with `git archive` (see the end of this file) |

---

## Title

    Smart Delivery Date

(3 words; no "Magento", "M2", "Adobe", "Extension", "Module" or "Plugin"; no version or developer name.)

## Short description

    Show shoppers when their order will arrive with a live cut-off countdown, and let them choose a delivery date at checkout that skips your days off and holidays.

## Long description (paste as HTML)

```html
<p>Smart Delivery Date shows shoppers exactly when their order will arrive and lets them choose a delivery date at checkout. It knows your processing days, your carrier's delivery days, your daily cut-off time and your holidays, so every date it shows or accepts is one you can actually deliver on.</p>

<h3>Estimated delivery, everywhere it matters</h3>
<ul>
  <li>"Order within 2h 15m and get it on Friday, October 23" on the product page, in the cart and at checkout</li>
  <li>Live countdown to your same-day cut-off time; the estimate moves on automatically when the cut-off passes</li>
  <li>Single date or delivery window ("between Monday and Wednesday")</li>
  <li>Works with full-page cache: the estimate is always calculated for the current moment</li>
  <li>Optional: hide the estimate for out-of-stock products</li>
</ul>

<h3>Customer-selected delivery date</h3>
<ul>
  <li>Delivery date calendar on the shipping step of the checkout</li>
  <li>Days off, holidays and dates before the earliest possible delivery are greyed out and cannot be chosen</li>
  <li>The date is checked again on the server when the order is placed, so an excluded or expired date can never be ordered, also through the REST API</li>
  <li>Optional or required, with a configurable selectable period, label and note</li>
</ul>

<h3>Multi-address checkout</h3>
<ul>
  <li>Estimate on the address step</li>
  <li>Estimate and delivery date for every shipping address</li>
  <li>Chosen dates listed on the review step and saved on each order</li>
</ul>

<h3>Your calendar, your rules</h3>
<ul>
  <li>Separate working days for your warehouse and your carrier</li>
  <li>Processing time and transit time in working days</li>
  <li>Excluded dates for holidays and closures, one-off or repeating every year</li>
  <li>Per-product processing time for made-to-order or slow items; in the cart the slowest product decides</li>
  <li>All dates and the cut-off time use the store timezone</li>
</ul>

<h3>For your team</h3>
<ul>
  <li>Delivery date on the admin order page and as a filterable column in the order grid</li>
  <li>Delivery date in the customer account, in order e-mails and in the order REST API</li>
</ul>

<h3>Built for today's storefronts</h3>
<ul>
  <li>Luma and Luma-based themes</li>
  <li>Hyvä themes: Alpine.js templates included (tested with Hyvä 1.5); the checkout uses the Hyvä Luma checkout fallback</li>
  <li>Translatable texts, localised dates, and messages you can change per store view</li>
  <li>Declarative schema, no core changes, passes the Commerce coding standard</li>
</ul>

<h3>Compatibility</h3>
<ul>
  <li>Magento Open Source and Adobe Commerce 2.4.6 – 2.4.9</li>
  <li>PHP 8.1 – 8.5</li>
</ul>
```

## Release notes: 2.0.0

    Complete rewrite.
    New: customer-selected delivery date at checkout with excluded dates blocked and validated on the server when the order is placed; multi-address checkout support (estimate, per-address date, review summary); required-date option; recurring excluded dates; per-product processing time; delivery window; delivery date on the admin order view, order grid, customer account, order e-mails and REST API; Hyvä templates.
    Fixed: cut-off time used a fixed timezone instead of the store timezone; countdown dropped whole days; storefront texts were not translatable; admin date picker was blocked by the Content Security Policy.
    Requires 2.4.6 or later and PHP 8.1 or later.

## Listing settings

| Field | Value |
| --- | --- |
| Category | Shipping & Fulfillment (secondary: Checkout) |
| Editions / versions | Magento Open Source 2.4.6, 2.4.7, 2.4.8, 2.4.9 · Adobe Commerce (on-premises and cloud) 2.4.6 – 2.4.9 |
| Hyvä compatible | Yes (tested with Hyvä Theme 1.5.3; one-page and multi-address checkout via the Hyvä Luma checkout fallback) |
| Price | **Your decision.** Adobe recommends $99 for Open Source and $199 for Adobe Commerce; the minimum is $25. If you add paid support or subscriptions, state the fee in bold in the first paragraph of the long description. It must match the price on ecommet.in. |
| Support | ecommet.solutions@gmail.com |
| Video | Optional, up to 3 YouTube links. None yet. |

## Note to the review team (resubmission)

    Thank you for the review of version 1.0.0. Version 2.0.0 addresses both issues:

    1. "User is able to place an order on excluded dates."
       Customers now choose a delivery date at checkout (Stores > Configuration > Ecommet Extensions > Smart Delivery Date > Customer Delivery Date Selection). Excluded dates, carrier days off and dates before the earliest possible delivery are disabled in the calendar. The date is validated on the server when the shipping step is saved and again when the order is placed, so an order for an excluded date is rejected even if the browser is bypassed (also through the REST API). With "Delivery Date Is Required" = Yes, an order cannot be placed without a date.
       To test: add an excluded date that falls within the next week, enable the module and "Delivery Date Is Required", then go to checkout: the excluded date cannot be selected, and the order cannot be placed without a valid date.

    2. "Expected delivery date notification is not showing while selecting checkout with multiple address."
       The estimate is now shown on the address step of multi-address checkout. The shipping method step shows an estimate and a delivery date field for every address, the review step lists the chosen dates, and each order stores the date of its address.

    The cut-off time is now evaluated in the store timezone (version 1.0.0 used a fixed timezone, which made estimates wrong in other timezones).

    The user guide (PDF) was expanded as requested: installation, every setting with its scope and default, how the date is calculated with a worked example, checkout and multi-address checkout, per-product processing time, where the date appears, Hyvä setup, troubleshooting and uninstalling, with screenshots.

---

## Building the package for the technical submission

From the module folder (the `marketplace/` and `docs/images/` folders are excluded through `.gitattributes`):

    git archive --format=zip --output=../ecommet-module-smart-delivery-date-2.0.0.zip HEAD
