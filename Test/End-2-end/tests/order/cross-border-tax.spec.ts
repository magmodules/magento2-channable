/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';
import OrderViewPage from 'Pages/backend/OrderViewPage';

const channableApi = new ChannableApi();
const orderViewPage = new OrderViewPage();

/**
 * Helper: extract numeric price from a formatted string like "€12.10" or "$12.10".
 */
function parsePrice(priceStr: string): number {
  return parseFloat(priceStr.replace(/[^0-9.,\-]/g, '').replace(',', '.'));
}

/**
 * Helper: extract order increment ID from the Channable webhook response.
 * Response format: { "validated": "true", "order_id": "100001234" }
 */
function getOrderIncrementId(response: any): string {
  if (response.order_id) {
    return String(response.order_id);
  }
  throw new Error(`Order creation failed: ${JSON.stringify(response)}`);
}

// Get product ID from environment or use default sample data product
const PRODUCT_ID = parseInt(process.env.PRODUCT_ID || '1', 10);

/**
 * Cross-border tax calculation test matrix.
 *
 * Each case sets the Magento tax config, POSTs an order to the Channable webhook,
 * then verifies the resulting Magento order totals in the admin.
 *
 * Tax rates (set up in setup-tax-rules.php):
 * - NL: 21%
 * - DE: 19%
 * - AT: 20%
 *
 * All test prices: €12.10 (incl. tax from Channable's perspective)
 */
const testCases = [
  {
    title: 'Domestic NL→NL, price incl tax',
    config: { priceIncTax: true, crossBorder: false },
    order: { country: 'NL', price: 12.10, priceTax: 2.10 },
    expected: { grandTotal: 12.10, taxPercent: 21 },
  },
  {
    title: 'Cross-border NL→DE, price incl tax, CBT disabled',
    config: { priceIncTax: true, crossBorder: false },
    order: { country: 'DE', price: 12.10, priceTax: 1.93 },
    expected: { grandTotal: 12.10, taxPercent: 19 },
  },
  {
    // With CBT enabled, Magento still applies destination tax rate (19%) to orders,
    // but does not adjust the catalog price. Our module skips compensation so the
    // gross price stays at €12.10. Tax rate is destination-based (DE 19%).
    title: 'Cross-border NL→DE, price incl tax, CBT enabled',
    config: { priceIncTax: true, crossBorder: true },
    order: { country: 'DE', price: 12.10, priceTax: 2.10 },
    expected: { grandTotal: 12.10, taxPercent: 19 },
  },
  {
    title: 'Cross-border NL→DE, price excl tax, CBT disabled',
    config: { priceIncTax: false, crossBorder: false },
    order: { country: 'DE', price: 12.10, priceTax: 1.93 },
    expected: { grandTotal: 12.10, taxPercent: 19 },
  },
  {
    title: 'Domestic NL→NL, price excl tax',
    config: { priceIncTax: false, crossBorder: false },
    order: { country: 'NL', price: 12.10, priceTax: 2.10 },
    expected: { grandTotal: 12.10, taxPercent: 21 },
  },
  {
    title: 'Cross-border NL→AT, price incl tax, CBT disabled',
    config: { priceIncTax: true, crossBorder: false },
    order: { country: 'AT', price: 12.10, priceTax: 2.02 },
    expected: { grandTotal: 12.10, taxPercent: 20 },
  },
];

for (const testCase of testCases) {
  test(`Cross-border tax: ${testCase.title}`, async ({ page, baseURL }) => {
    // 1. Set Magento tax configuration via config-setter helper
    await channableApi.setMagentoConfig(baseURL, {
      'tax/calculation/price_includes_tax': testCase.config.priceIncTax ? '1' : '0',
      'tax/calculation/cross_border_trade_enabled': testCase.config.crossBorder ? '1' : '0',
    });

    // 2. Build and POST order to Channable webhook
    const orderData = channableApi.buildOrderData({
      country: testCase.order.country,
      price: testCase.order.price,
      priceTax: testCase.order.priceTax,
      productId: PRODUCT_ID,
    });

    const response = await channableApi.postOrder(baseURL, orderData);

    // 3. Extract order increment ID from response
    const incrementId = getOrderIncrementId(response);
    console.log(`Order created: ${incrementId} (${testCase.title})`);

    // 4. Open order in admin
    await orderViewPage.openByIncrementId(page, incrementId);

    // 5. Assert grand total matches expected
    const grandTotalStr = await orderViewPage.getGrandTotal(page);
    const grandTotal = parsePrice(grandTotalStr);

    expect(grandTotal).toBeCloseTo(testCase.expected.grandTotal, 1);

    // 6. Assert tax percentage in items table
    const taxPercentStr = await orderViewPage.getTaxPercent(page);
    const taxPercent = parseFloat(taxPercentStr.replace(/[^0-9.]/g, ''));

    expect(taxPercent).toBe(testCase.expected.taxPercent);
  });
}
