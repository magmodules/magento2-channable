# E2E Tests

Browser-based end-to-end tests for the Channable module, powered by [Playwright](https://playwright.dev/). Tests run against a real Magento instance — orders are submitted via the Channable webhook endpoint and the resulting Magento state is verified through the admin panel and REST API.

Tests run automatically on release tags, pull requests, and when the `run_e2e_tests` label is added. They can also be triggered manually via GitHub Actions. The CI environment spins up a Dockerized Magento instance with sample data, configures the module, and runs the full suite.

## Cross-Border Tax

The most complex part of order import is tax calculation. Channable sends prices as shown on the marketplace (gross, including destination tax), but Magento may need to recalculate tax based on its own tax rules and origin country.

These tests validate that the module correctly handles the interplay between Magento's tax configuration and cross-border scenarios. All scenarios use a fixed product price of €12.10 and verify the grand total stays consistent — regardless of whether prices include/exclude tax, whether cross-border trade (CBT) is enabled, or which destination country is involved.

| Test | Expected |
|------|----------|
| Domestic NL→NL, price incl tax | €12.10 grand total, 21% tax applied |
| Cross-border NL→DE, price incl tax, CBT off | €12.10 grand total, 19% tax — module compensates for rate difference |
| Cross-border NL→DE, price incl tax, CBT on | €12.10 grand total, 19% tax — Magento handles natively |
| Cross-border NL→DE, price excl tax, CBT off | €12.10 grand total, 19% tax — uses Channable's price_tax for stripping |
| Domestic NL→NL, price excl tax | €12.10 grand total, 21% tax applied |
| Cross-border NL→AT, price incl tax, CBT off | €12.10 grand total, 20% tax — verifies with a different destination rate |

Tax rules are provisioned during CI setup with rates for NL (21%), DE (19%), AT (20%), BE (21%), and FR (20%). Shipping origin is always set to NL (Netherlands).

## Order Import

Each test submits an order through the Channable webhook with a specific module configuration enabled, then navigates to the Magento admin to verify the order was created with the correct properties. This covers the full range of config-driven behavior: customer handling, invoicing, shipping, custom order IDs, discounts, and multi-currency support.

| Test | Expected |
|------|----------|
| Guest checkout (default) | Order created, customer group = NOT LOGGED IN |
| Customer creation enabled | Customer account created and linked to order |
| Business order (VAT exempt) | Tax = €0.00, VAT ID stored on order |
| LVB order (auto-shipped) | Order auto-shipped, shipment record present |
| Auto-invoice enabled | Invoice automatically created after import |
| Custom increment ID with prefix | Order increment ID starts with CHAN- |
| Custom increment ID (alphanumeric strip) | Special characters removed from channel order ID |
| Shipping cost | Shipping amount = €5.00 on order |
| Discount applied | Discount = -€2.00 reflected in order totals |
| Multiple quantities | Grand total correctly multiplied for qty 3 |
| Multi-currency (PLN) | Order created in PLN with correct currency conversion |

## Webhooks

Channable periodically polls Magento for order status updates and shipment information. These tests verify the read-only GET endpoints return the correct data structure and content, ensuring the bi-directional sync between Magento and Channable works as expected.

| Test | Expected |
|------|----------|
| Order status — pending/processing | Returns order id and valid status string |
| Order status — invalid ID | Returns validated=false with error details |
| Shipments — recent order | Returns shipment array for known orders |
| Shipments — LVB with tracking | Shipped order includes tracking information |

## Upcoming: Feed Generation

The next suite of E2E tests will cover feed generation — validating that product data is correctly exported to Channable based on attribute mapping, category filters, and feed configuration. This will include tests for price rendering, image URLs, stock status, and custom attribute handling.

## Running Locally

```bash
cd Test/End-2-end
npm install && npx playwright install chromium

BASE_URL="https://magento-248.test/" \
MAGENTO_CONTAINER="magento-248-phpfpm-1" \
MAGENTO_ADMIN_USER="e2e-admin" \
MAGENTO_ADMIN_PASS="E2eTest1234!" \
CHANNABLE_TOKEN="<token>" \
PRODUCT_ID="3" \
npx playwright test
```

Run a specific suite:
```bash
npx playwright test tests/order/cross-border-tax.spec.ts
npx playwright test tests/order/order-import.spec.ts
npx playwright test tests/order/webhooks.spec.ts
```
