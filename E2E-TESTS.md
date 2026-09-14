# E2E Test Cases — Channable Module

Browser- and API-based end-to-end tests for the Channable module, powered by [Playwright](https://playwright.dev/). Tests run against a real Magento instance: orders are submitted through the Channable webhook endpoints and the resulting Magento state is verified through the admin panel, the REST API and the module's own webhooks.

Tests run automatically on release tags, pull requests and when the `run_e2e_tests` label is added, and can be triggered manually through GitHub Actions. The CI environment spins up a Dockerized Magento instance with sample data, configures the module and runs the full suite.

## Cross-Border Tax (`tests/order/cross-border-tax.spec.ts`)

Channable sends prices as shown on the marketplace (gross, including destination tax), while Magento may recalculate tax based on its own rules and origin country. All scenarios use a product price of €12.10 and verify the grand total stays consistent. Tax rules are provisioned for NL (21%), DE (19%), AT (20%), BE (21%) and FR (20%); shipping origin is always NL.

1. Domestic NL→NL, price incl tax — €12.10 grand total, 21% tax applied
2. Cross-border NL→DE, price incl tax, CBT disabled — €12.10 grand total, 19% tax, module compensates for the rate difference
3. Cross-border NL→DE, price incl tax, CBT enabled — €12.10 grand total, 19% tax, Magento handles it natively
4. Cross-border NL→DE, price excl tax, CBT disabled — €12.10 grand total, 19% tax, uses Channable's `price_tax` for stripping
5. Domestic NL→NL, price excl tax — €12.10 grand total, 21% tax applied
6. Cross-border NL→AT, price incl tax, CBT disabled — €12.10 grand total, 20% tax, verifies a different destination rate

## Order Import (`tests/order/order-import.spec.ts`)

Each test submits an order through the Channable webhook with a specific module configuration enabled, then verifies in the Magento admin that the order was created with the correct properties.

7. Guest checkout (default) — order created, customer group is NOT LOGGED IN
8. Customer creation — customer account created and linked to the order
9. Business order (VAT exempt) — tax is €0.00, VAT ID stored on the order
10. LVB order (auto-shipped) — order auto-shipped, shipment record present
11. Auto-invoice — invoice created automatically after import
12. Custom increment ID with prefix — order increment ID starts with CHAN-
13. Custom increment ID (alphanumeric strip) — special characters removed from the channel order ID
14. Shipping cost in order — shipping amount is €5.00 on the order
15. Discount in order — discount of -€2.00 reflected in the order totals
16. Multiple quantities — grand total correctly multiplied for qty 3
17. LV order with pycountry region code — region resolved from the Channable region code
18. PL order without region — order created without a region set
19. Item-level discount: original price vs discounted price — item price and discount stored separately
20. Item-level discount: multi-qty grand total — discount multiplied by quantity
21. Item-level discount: price excl tax config — discount applied on the excl tax price
22. Item-level discount: price incl tax config — discount applied on the incl tax price
23. Item-level discount: multi-product — discounts per item across multiple products
24. Item-level discount: no order-level discount applied — item discounts do not duplicate into the order discount
25. Multi-currency order (PLN) — order created in PLN with the correct currency conversion
26. Customer created in correct store view — customer assigned to the store view the order was placed in
27. Item updates regression — order import leaves the `channable_items` table consistent

## Webhooks (`tests/order/webhooks.spec.ts`)

Channable periodically polls Magento for order status updates and shipment information. These tests verify the read-only GET endpoints return the correct structure and content.

28. Order status — processing — returns the order id and a valid status string
29. Order status — invalid ID — returns `validated=false` with error details
30. Shipments — recent order appears — returns a shipment array for known orders
31. Shipments — LVB order has fulfillment — shipped order includes its tracking information

## Delivery Bill ID (`tests/order/delivery-bill-id.spec.ts`)

Every shipment update carries a `delivery_bill_id`: the number of the delivery note physically included in the parcel, used by marketplaces such as Conrad to match their invoice to the delivery. It defaults to the Magento shipment increment ID, which is printed on Magento's standard packing slip. ERP systems that generate their own delivery note number can supply it through the `channable_delivery_bill_id` extension attribute, either when creating the shipment (`POST /V1/order/:id/ship`) or afterwards (`POST /V1/shipment`).

32. Shipments webhook — `delivery_bill_id` defaults to the shipment increment ID
33. Shipments webhook — entry exposes `shipment_id` next to the order `id`
34. Delivery bill ID does not replace the tracking data — `tracking_code` and `carrier_code` unchanged
35. Shipment without tracking still reports a delivery bill ID
36. ERP value supplied on shipment creation is used instead of the increment ID
37. ERP value is exposed as extension attribute on the shipment through the REST API
38. ERP value can be set on an existing shipment and is reflected in the webhook
39. Empty ERP value falls back to the shipment increment ID
40. Order status returns a delivery bill ID per shipment, plus a `fulfillments` array
41. Order without shipments has no `fulfillment` and no `fulfillments` key
42. Multiple shipments — each shipment keeps its own delivery bill ID and tracking data
43. Multiple shipments — `fulfillments` holds one entry per shipment, `fulfillment` the most recent

## Bundle Pricing (`tests/feed/bundle-pricing.spec.ts`)

Dynamic bundle products derive their prices from their children rather than having a fixed price. These tests validate that the feed reports bundle prices correctly across tax configurations, using indexed prices (`min_price`) instead of `getBaseAmount()`. Test products are created through the REST API: two simple children (€20 + €30), a dynamic bundle with two required options, a fixed bundle at €100, and a single-option dynamic bundle for the out-of-stock scenario.

**Group A — catalog prices including tax** (stored €50 = incl tax)

44. Dynamic bundle, add tax on — price = 50.00, min_price = 50.00
45. Dynamic bundle, add tax off — price = 50.00, min_price = 50.00
46. Dynamic bundle, include both — price_incl = 50.00, price_excl ≈ 41.32
47. Fixed bundle, add tax on — price = 100.00

**Group B — catalog prices excluding tax** (stored €50 = excl tax, incl = €60.50)

48. Dynamic bundle, add tax on — price ≈ 60.50, min_price ≈ 60.50
49. Dynamic bundle, add tax off — price = 50.00, min_price = 50.00
50. Dynamic bundle, include both — price_incl ≈ 60.50, price_excl = 50.00
51. Fixed bundle, add tax on — price ≈ 121.00

**Group C — stock scenarios** (prices incl tax)

52. Dynamic bundle with an out-of-stock child — min_price = 20.00, only in-stock children counted

## Credit Memo (`tests/order/credit-memo.spec.ts`)

Validates that orders placed through the Channable payment method can be refunded. Both tests create an auto-invoiced order and then create a credit memo.

53. Credit memo via admin UI — created through the invoice → Credit Memo → Refund Offline flow
54. Credit memo via REST API (`V1/invoice/:id/refund`) — returns 200 with a credit memo ID and the record exists

## Feed Generation (`tests/feed/feed-generation.spec.ts`)

Validates that the product feed endpoint returns valid data and handles configuration and token errors gracefully.

55. Feed endpoint returns valid JSON without errors
56. Feed endpoint returns a products array
57. Feed products contain the required id and title fields
58. Feed does not crash with the visibility filter enabled
59. Feed returns empty for an invalid token
60. Feed returns empty when the module is disabled
61. Single product feed through the `pid` parameter

## Item Updates (`tests/order/item-save.spec.ts`)

62. Saving a product in the admin does not cause a constraint violation on `channable_items`
