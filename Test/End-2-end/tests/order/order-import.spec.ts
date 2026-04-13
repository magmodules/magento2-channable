/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';
import OrderViewPage from 'Pages/backend/OrderViewPage';
import CustomerViewPage from 'Pages/backend/CustomerViewPage';

const channableApi = new ChannableApi();
const orderViewPage = new OrderViewPage();
const customerViewPage = new CustomerViewPage();

const PRODUCT_ID = parseInt(process.env.PRODUCT_ID || '1', 10);

const CONFIG_BASE = 'magmodules_channable_marketplace/order';

/**
 * Helper: extract numeric price from a formatted string like "€12.10" or "$12.10".
 */
function parsePrice(priceStr: string): number {
  return parseFloat(priceStr.replace(/[^0-9.,\-]/g, '').replace(',', '.'));
}

/**
 * Helper: extract order increment ID from the Channable webhook response.
 */
function getOrderIncrementId(response: any): string {
  if (response.order_id) {
    return String(response.order_id);
  }
  throw new Error(`Order creation failed: ${JSON.stringify(response)}`);
}

/**
 * Reset order import config to defaults before each test.
 */
async function resetOrderConfig(baseURL: string): Promise<void> {
  await channableApi.setMagentoConfig(baseURL, {
    [`${CONFIG_BASE}/import_customer`]: '0',
    [`${CONFIG_BASE}/customers_group`]: '1',
    [`${CONFIG_BASE}/invoice_order`]: '0',
    [`${CONFIG_BASE}/channel_orderid`]: '0',
    [`${CONFIG_BASE}/orderid_prefix`]: '',
    [`${CONFIG_BASE}/orderid_alphanumeric`]: '0',
    [`${CONFIG_BASE}/lvb`]: '0',
    [`${CONFIG_BASE}/lvb_ship`]: '0',
    [`${CONFIG_BASE}/business_order`]: '0',
  });
}

const testCases = [
  {
    title: 'Guest checkout (default)',
    config: {},
    orderOverrides: {},
    assert: async (page, incrementId) => {
      const isGuest = await orderViewPage.isGuestOrder(page);
      expect(isGuest).toBe(true);
    },
  },
  {
    title: 'Customer creation',
    config: {
      [`${CONFIG_BASE}/import_customer`]: '1',
      [`${CONFIG_BASE}/customers_group`]: '1',
    },
    orderOverrides: {},
    assert: async (page, incrementId) => {
      const isGuest = await orderViewPage.isGuestOrder(page);
      expect(isGuest).toBe(false);
    },
  },
  {
    title: 'Business order (VAT exempt)',
    config: {
      [`${CONFIG_BASE}/business_order`]: '1',
    },
    orderOverrides: { businessOrder: true, priceTax: 0 },
    assert: async (page, incrementId) => {
      const taxAmount = await orderViewPage.getTaxAmount(page);
      const tax = parsePrice(taxAmount);
      expect(tax).toBe(0);
    },
  },
  {
    title: 'LVB order (auto-shipped)',
    config: {
      [`${CONFIG_BASE}/lvb`]: '1',
      [`${CONFIG_BASE}/lvb_ship`]: '1',
    },
    orderOverrides: { orderStatus: 'shipped' },
    assert: async (page, incrementId) => {
      const hasShip = await orderViewPage.hasShipment(page);
      expect(hasShip).toBe(true);
    },
  },
  {
    title: 'Auto-invoice',
    config: {
      [`${CONFIG_BASE}/invoice_order`]: '1',
    },
    orderOverrides: {},
    assert: async (page, incrementId) => {
      const hasInv = await orderViewPage.hasInvoice(page);
      expect(hasInv).toBe(true);
    },
  },
  {
    title: 'Custom increment ID with prefix',
    config: {
      [`${CONFIG_BASE}/channel_orderid`]: '1',
      [`${CONFIG_BASE}/orderid_prefix`]: 'CHAN-',
    },
    orderOverrides: { channelId: `E2E-${Date.now()}` },
    assert: async (page, incrementId) => {
      const displayedId = await orderViewPage.getOrderIncrementId(page);
      // Increment ID should start with CHAN- prefix and contain the channel ID
      expect(displayedId).toMatch(/^CHAN-E2E-\d+/);
    },
  },
  {
    title: 'Custom increment ID (alphanumeric strip)',
    config: {
      [`${CONFIG_BASE}/channel_orderid`]: '1',
      [`${CONFIG_BASE}/orderid_prefix`]: 'TEST-',
      [`${CONFIG_BASE}/orderid_alphanumeric`]: '1',
    },
    orderOverrides: { channelId: `X${Date.now()}!Y` },
    assert: async (page, incrementId) => {
      const displayedId = await orderViewPage.getOrderIncrementId(page);
      // Alphanumeric strip removes special chars, prefix is prepended
      expect(displayedId).toMatch(/^TEST-X\d+Y$/);
    },
  },
  {
    title: 'Shipping cost in order',
    config: {},
    orderOverrides: { shipping: 5.00 },
    assert: async (page, incrementId) => {
      const shippingStr = await orderViewPage.getShippingAmount(page);
      const shipping = parsePrice(shippingStr);
      expect(shipping).toBeCloseTo(5.00, 1);
    },
  },
  {
    title: 'Discount in order',
    config: {},
    orderOverrides: { discount: 2.00 },
    assert: async (page, incrementId) => {
      const discountStr = await orderViewPage.getDiscountAmount(page);
      const discount = Math.abs(parsePrice(discountStr));
      expect(discount).toBeCloseTo(2.00, 1);
    },
  },
  {
    title: 'Multiple quantities',
    config: {},
    orderOverrides: { quantity: 3 },
    assert: async (page, incrementId) => {
      const grandTotalStr = await orderViewPage.getGrandTotal(page);
      const grandTotal = parsePrice(grandTotalStr);
      // Default price is 12.10, qty 3 = 36.30
      expect(grandTotal).toBeCloseTo(12.10 * 3, 1);
    },
  },
  {
    title: 'Multi-currency order (PLN)',
    config: {
      'currency/options/allow': 'EUR,PLN',
    },
    setup: async () => {
      // Ensure PLN currency rate exists (needed for clean environments)
      await channableApi.setupCurrencyRate('EUR', 'PLN', 4.35);
    },
    orderOverrides: { currency: 'PLN', price: 50.00, priceTax: 9.35 },
    assert: async (page, incrementId) => {
      // Magento displays: "€11.49[PLN 50.00]" — base EUR + order currency in brackets
      // getGrandTotal reads <strong> which only has EUR, so read the full cell
      const row = page.locator('tr', { hasText: 'Grand Total' }).last();
      const cellText = (await row.locator('td').last().textContent()).trim();

      // Full cell should contain PLN amount in brackets
      expect(cellText).toContain('PLN');
      expect(cellText).toContain('50.00');

      // Base currency (EUR) conversion should be > 0
      const baseCurrencyTotal = parsePrice(cellText);
      expect(baseCurrencyTotal).toBeGreaterThan(0);
    },
  },
];

for (const testCase of testCases) {
  test(`Order import: ${testCase.title}`, async ({ page, baseURL }) => {
    // 0. Run optional setup (e.g. currency rates)
    if (testCase.setup) {
      await testCase.setup();
    }

    // 1. Reset config to defaults then apply test-specific config
    await resetOrderConfig(baseURL);
    if (Object.keys(testCase.config).length > 0) {
      await channableApi.setMagentoConfig(baseURL, testCase.config);
    }

    // 2. Build and POST order
    const orderData = channableApi.buildOrderData({
      productId: PRODUCT_ID,
      ...testCase.orderOverrides,
    });

    const response = await channableApi.postOrder(baseURL, orderData);
    const incrementId = getOrderIncrementId(response);
    console.log(`Order created: ${incrementId} (${testCase.title})`);

    // 3. Open order in admin
    await orderViewPage.openByIncrementId(page, incrementId);

    // 4. Run test-specific assertions
    await testCase.assert(page, incrementId);
  });
}
