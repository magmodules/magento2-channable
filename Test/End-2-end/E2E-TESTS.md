# Channable Order Import — E2E Test Suite

## Overview

End-to-end tests for the Channable order import module. Orders are POSTed to the Channable webhook endpoint and the resulting Magento order state is verified in the admin panel and via API endpoints.

## Test Suites

### 1. Cross-Border Tax (`tests/order/cross-border-tax.spec.ts`)

Verifies tax calculations across different Magento tax configurations and country combinations.

| Scenario | Config | Country | Expected |
|----------|--------|---------|----------|
| Domestic NL→NL, price incl tax | priceIncTax=1, CBT=0 | NL | €12.10, 21% |
| Cross-border NL→DE, price incl tax, CBT off | priceIncTax=1, CBT=0 | DE | €12.10, 19% |
| Cross-border NL→DE, price incl tax, CBT on | priceIncTax=1, CBT=1 | DE | €12.10, 19% |
| Cross-border NL→DE, price excl tax, CBT off | priceIncTax=0, CBT=0 | DE | €12.10, 19% |
| Domestic NL→NL, price excl tax | priceIncTax=0, CBT=0 | NL | €12.10, 21% |
| Cross-border NL→AT, price incl tax, CBT off | priceIncTax=1, CBT=0 | AT | €12.10, 20% |

### 2. Order Import Options (`tests/order/order-import.spec.ts`)

Tests config-driven order import behavior: customer handling, invoicing, shipping, custom IDs, etc.

| Scenario | Key Config | Assert |
|----------|-----------|--------|
| Guest checkout (default) | import_customer=0 | Customer group = NOT LOGGED IN |
| Customer creation | import_customer=1 | Customer account created |
| Business order (VAT exempt) | business_order=1 | Tax = €0.00 |
| LVB order (auto-shipped) | lvb=1, lvb_ship=1 | Shipment created |
| Auto-invoice | invoice_order=1 | Invoice created |
| Custom increment ID with prefix | channel_orderid=1, orderid_prefix=CHAN- | ID starts with CHAN- |
| Custom increment ID (alphanumeric strip) | channel_orderid=1, orderid_alphanumeric=1 | Special chars removed |
| Shipping cost | — | Shipping amount = €5.00 |
| Discount | — | Discount = -€2.00 |
| Multiple quantities | — | Grand total = 3× price |
| Multi-currency (PLN) | — | Order created with PLN currency, totals correct |

### 3. Webhooks (`tests/order/webhooks.spec.ts`)

Tests the read-only GET endpoints for order status and shipments.

| Scenario | Endpoint | Assert |
|----------|----------|--------|
| Order status — pending/processing | GET `/channable/order/status` | Has id, valid status |
| Order status — invalid ID | GET `/channable/order/status` | validated=false, has errors |
| Shipments — recent order | GET `/channable/order/shipments` | Returns array |
| Shipments — LVB with tracking | GET `/channable/order/shipments` | Contains shipped order |

## Tax Setup (Prerequisites)

| Country | Tax Rate |
|---------|----------|
| NL      | 21%      |
| DE      | 19%      |
| AT      | 20%      |
| BE      | 21%      |
| FR      | 20%      |

- **Shipping origin**: NL (Netherlands)
- **Tax calculation based on**: Shipping address
- All rates linked to product tax class "Taxable Goods" and customer tax class "Retail Customer"

## Config Paths Reference

### Tax Config
| Path | Description |
|------|-------------|
| `tax/calculation/price_includes_tax` | Catalog prices include tax (1/0) |
| `tax/calculation/cross_border_trade_enabled` | Cross-border trade mode (1/0) |

### Order Import Config
| Path | Description |
|------|-------------|
| `magmodules_channable_marketplace/order/import_customer` | Create customer account (1/0) |
| `magmodules_channable_marketplace/order/customers_group` | Customer group ID |
| `magmodules_channable_marketplace/order/invoice_order` | Auto-invoice (1/0) |
| `magmodules_channable_marketplace/order/channel_orderid` | Use channel ID as increment ID (1/0) |
| `magmodules_channable_marketplace/order/orderid_prefix` | Increment ID prefix |
| `magmodules_channable_marketplace/order/orderid_alphanumeric` | Strip non-alphanumeric chars (1/0) |
| `magmodules_channable_marketplace/order/lvb` | Accept LVB orders (1/0) |
| `magmodules_channable_marketplace/order/lvb_ship` | Auto-ship LVB orders (1/0) |
| `magmodules_channable_marketplace/order/business_order` | Accept business orders (1/0) |

## How Channable Price Fields Work

| Field | Description |
|-------|-------------|
| `products[].price` | Product price as displayed on the marketplace (gross, including tax) |
| `products[].price_tax` | Tax amount included in the price (based on destination country rate) |

## Cross-Border Tax Logic Flow

```
getProductPrice()
├── Business order with price_tax=0? → return price as-is (tax class set to 0)
├── priceIncludesTax = false?
│   └── stripTaxFromPrice()
│       ├── Cross-border + CBT disabled + price_tax > 0?
│       │   └── return price - price_tax (use Channable's tax amount)
│       └── Otherwise
│           └── return price / (100 + percent) * 100 (standard Magento calc)
└── priceIncludesTax = true?
    └── compensateCrossBorderTax()
        ├── CBT enabled? → return price (no compensation needed)
        ├── Domestic order? → return price (same origin/dest)
        └── Cross-border + CBT disabled?
            └── return price * (100 + originRate) / (100 + destRate)
```

## Cross-Border Tax Scenario Details

### Domestic NL→NL, price includes tax
Standard domestic — Magento applies 21% NL tax, price stays €12.10 as-is.

### Cross-border NL→DE, price includes tax, CBT disabled
`compensateCrossBorderTax()` pre-adjusts: €12.10 × 121/119 ≈ €12.30 → Magento strips 21%, adds 19% → €12.10.

### Cross-border NL→DE, price includes tax, CBT enabled
CBT enabled → skip compensation. Magento applies DE 19% to the order. Price stays €12.10.

### Cross-border NL→DE, price excludes tax, CBT disabled
`stripTaxFromPrice()` uses Channable's price_tax: €12.10 - €1.93 = €10.17 → Magento adds 19% → €12.10.

### Cross-border NL→AT, price includes tax, CBT disabled
Same as NL→DE but with 20% AT rate: €12.10 × 121/120 ≈ €12.20 → strip 21%, add 20% → €12.10.

## Running Locally

```bash
cd Test/End-2-end
npm install
npx playwright install chromium

BASE_URL="https://magento-248.test/" \
MAGENTO_ADMIN_USER="Admin" \
MAGENTO_ADMIN_PASS="Test12345" \
CHANNABLE_TOKEN="<token>" \
MAGENTO_CONTAINER="magento-248-phpfpm-1" \
PRODUCT_ID="3" \
npx playwright test
```

Run a specific suite:
```bash
npx playwright test tests/order/cross-border-tax.spec.ts
npx playwright test tests/order/order-import.spec.ts
npx playwright test tests/order/webhooks.spec.ts
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `BASE_URL` | Magento base URL | `https://magento.test/` |
| `MAGENTO_ADMIN_USER` | Admin username | `exampleuser` |
| `MAGENTO_ADMIN_PASS` | Admin password | `examplepassword123` |
| `CHANNABLE_TOKEN` | Channable webhook token | (required) |
| `MAGENTO_CONTAINER` | Docker container name for config changes | (uses PHP helper if empty) |
| `PRODUCT_ID` | Product ID to use in test orders | `1` |
