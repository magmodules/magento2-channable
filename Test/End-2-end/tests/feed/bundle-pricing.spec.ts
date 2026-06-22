/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';

const api = new ChannableApi();

const TAX_RATE_NL = 0.21;

const SKUS = {
  childA: 'e2e-bundle-child-a',
  childB: 'e2e-bundle-child-b',
  dynamic: 'e2e-bundle-dynamic',
  fixed: 'e2e-bundle-fixed',
  dynamicSingleOpt: 'e2e-bundle-dyn-single',
};

const CHILD_A_PRICE = 20.00;
const CHILD_B_PRICE = 30.00;
const CHILDREN_SUM = CHILD_A_PRICE + CHILD_B_PRICE; // 50.00
const FIXED_PRICE = 100.00;

let dynamicProductId: number;
let fixedProductId: number;
let dynamicSingleOptId: number;

const BASE_CONFIG: Record<string, string> = {
  'magmodules_channable/general/enable': '1',
  'magmodules_channable/filter/visbility_enabled': '0',
};

/**
 * Helper: parse feed price (e.g. "50.00 EUR") and round to 2 decimals.
 */
function price(val: string | number): number {
  const num = parseFloat(String(val).replace(/[^0-9.\-]/g, ''));
  return Math.round(num * 100) / 100;
}

/**
 * Helper: build simple product payload.
 */
function simpleProduct(sku: string, price: number) {
  return {
    sku,
    name: sku,
    attribute_set_id: 4,
    price,
    type_id: 'simple',
    status: 1,
    visibility: 1,
    weight: 1,
    extension_attributes: {
      stock_item: { qty: 100, is_in_stock: true },
      category_links: [],
    },
    custom_attributes: [
      { attribute_code: 'tax_class_id', value: '2' },
    ],
  };
}

test.describe('Bundle Product Pricing in Feed', () => {

  test.beforeAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    // Clean up any leftover products from previous runs
    for (const sku of Object.values(SKUS)) {
      try { await api.deleteProduct(baseURL, sku); } catch { /* ignore */ }
    }

    // Create simple children
    await api.createProduct(baseURL, simpleProduct(SKUS.childA, CHILD_A_PRICE));
    await api.createProduct(baseURL, simpleProduct(SKUS.childB, CHILD_B_PRICE));

    // Get child IDs for bundle options
    const childAId = await api.getProductId(baseURL, SKUS.childA);
    const childBId = await api.getProductId(baseURL, SKUS.childB);

    // Create dynamic bundle (price_type=0)
    await api.createProduct(baseURL, {
      sku: SKUS.dynamic,
      name: 'E2E Dynamic Bundle',
      attribute_set_id: 4,
      type_id: 'bundle',
      status: 1,
      visibility: 4,
      custom_attributes: [
        { attribute_code: 'tax_class_id', value: '2' },
        { attribute_code: 'price_type', value: '0' },
        { attribute_code: 'sku_type', value: '0' },
        { attribute_code: 'weight_type', value: '0' },
        { attribute_code: 'price_view', value: '0' },
        { attribute_code: 'shipment_type', value: '0' },
      ],
      extension_attributes: {
        stock_item: { qty: 0, is_in_stock: true },
        bundle_product_options: [
          {
            title: 'Option A',
            required: true,
            type: 'select',
            position: 0,
            product_links: [
              {
                sku: SKUS.childA,
                qty: 1,
                position: 0,
                is_default: true,
                can_change_quantity: 0,
              },
            ],
          },
          {
            title: 'Option B',
            required: true,
            type: 'select',
            position: 1,
            product_links: [
              {
                sku: SKUS.childB,
                qty: 1,
                position: 0,
                is_default: true,
                can_change_quantity: 0,
              },
            ],
          },
        ],
      },
    });

    // Create fixed bundle (price_type=1)
    await api.createProduct(baseURL, {
      sku: SKUS.fixed,
      name: 'E2E Fixed Bundle',
      attribute_set_id: 4,
      type_id: 'bundle',
      status: 1,
      visibility: 4,
      price: FIXED_PRICE,
      custom_attributes: [
        { attribute_code: 'tax_class_id', value: '2' },
        { attribute_code: 'price_type', value: '1' },
        { attribute_code: 'sku_type', value: '1' },
        { attribute_code: 'weight_type', value: '1' },
        { attribute_code: 'price_view', value: '0' },
        { attribute_code: 'shipment_type', value: '0' },
      ],
      extension_attributes: {
        stock_item: { qty: 0, is_in_stock: true },
        bundle_product_options: [
          {
            title: 'Bundle Items',
            required: true,
            type: 'select',
            position: 0,
            product_links: [
              {
                sku: SKUS.childA,
                qty: 1,
                position: 0,
                is_default: true,
                price_type: 0,
                price: 0,
                can_change_quantity: 0,
              },
            ],
          },
        ],
      },
    });

    // Create dynamic bundle with single option (both children in one select) for OOS test
    await api.createProduct(baseURL, {
      sku: SKUS.dynamicSingleOpt,
      name: 'E2E Dynamic Bundle Single Option',
      attribute_set_id: 4,
      type_id: 'bundle',
      status: 1,
      visibility: 4,
      custom_attributes: [
        { attribute_code: 'tax_class_id', value: '2' },
        { attribute_code: 'price_type', value: '0' },
        { attribute_code: 'sku_type', value: '0' },
        { attribute_code: 'weight_type', value: '0' },
        { attribute_code: 'price_view', value: '0' },
        { attribute_code: 'shipment_type', value: '0' },
      ],
      extension_attributes: {
        stock_item: { qty: 0, is_in_stock: true },
        bundle_product_options: [
          {
            title: 'Pick one',
            required: true,
            type: 'select',
            position: 0,
            product_links: [
              {
                sku: SKUS.childA,
                qty: 1,
                position: 0,
                is_default: true,
                can_change_quantity: 0,
              },
              {
                sku: SKUS.childB,
                qty: 1,
                position: 1,
                is_default: false,
                can_change_quantity: 0,
              },
            ],
          },
        ],
      },
    });

    dynamicProductId = await api.getProductId(baseURL, SKUS.dynamic);
    fixedProductId = await api.getProductId(baseURL, SKUS.fixed);
    dynamicSingleOptId = await api.getProductId(baseURL, SKUS.dynamicSingleOpt);

    // Ensure module is enabled and set initial tax config (prices include tax)
    // Default tax destination MUST match a tax rule (NL) so getTaxPrice() resolves
    // the correct rate in feed context where no customer session exists.
    await api.setMagentoConfig(baseURL, {
      ...BASE_CONFIG,
      'tax/calculation/price_includes_tax': '1',
      'tax/defaults/country': 'NL',
      'tax/defaults/region': '0',
      'tax/defaults/postcode': '1000 AA',
    });

    api.reindexAll();
  });

  test.afterAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    // Delete test products
    for (const sku of Object.values(SKUS)) {
      try { await api.deleteProduct(baseURL, sku); } catch { /* ignore */ }
    }

    // Reset config values we changed during tests
    await api.setMagentoConfig(baseURL, {
      'tax/calculation/price_includes_tax': '1',
      'magmodules_channable/advanced/tax': '0',
      'magmodules_channable/advanced/tax_include_both': '0',
    });
  });

  // ── Group A: Catalog prices INCLUDING tax ──────────────────────────────
  // Stored €50 = incl tax.

  test.describe('Group A: Catalog prices including tax', () => {

    test('A1: dynamic bundle, add tax on → price = stored value (already incl)', async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;

      await api.setMagentoConfig(baseURL, {
        ...BASE_CONFIG,
        'tax/calculation/price_includes_tax': '1',
        'magmodules_channable/advanced/tax': '1',
        'magmodules_channable/advanced/tax_include_both': '0',
      });

      const product = await api.getFeedProduct(baseURL, dynamicProductId);
      expect(price(product.price)).toBe(CHILDREN_SUM);
      expect(price(product.min_price)).toBe(CHILDREN_SUM);
    });

    test('A2: dynamic bundle, add tax off → price = stored value (incl)', async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;

      await api.setMagentoConfig(baseURL, {
        ...BASE_CONFIG,
        'tax/calculation/price_includes_tax': '1',
        'magmodules_channable/advanced/tax': '0',
        'magmodules_channable/advanced/tax_include_both': '0',
      });

      const product = await api.getFeedProduct(baseURL, dynamicProductId);
      expect(price(product.price)).toBe(CHILDREN_SUM);
      expect(price(product.min_price)).toBe(CHILDREN_SUM);
    });

    test('A3: dynamic bundle, include both → price_incl and price_excl', async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;

      await api.setMagentoConfig(baseURL, {
        ...BASE_CONFIG,
        'tax/calculation/price_includes_tax': '1',
        'magmodules_channable/advanced/tax': '1',
        'magmodules_channable/advanced/tax_include_both': '1',
      });

      const product = await api.getFeedProduct(baseURL, dynamicProductId);

      const expectedIncl = CHILDREN_SUM;
      const expectedExcl = price(CHILDREN_SUM / (1 + TAX_RATE_NL));

      expect(price(product.price_incl)).toBe(expectedIncl);
      expect(price(product.price_excl)).toBe(expectedExcl);
    });

    test('A4: fixed bundle, add tax on → price = fixed price', async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;

      await api.setMagentoConfig(baseURL, {
        ...BASE_CONFIG,
        'tax/calculation/price_includes_tax': '1',
        'magmodules_channable/advanced/tax': '1',
        'magmodules_channable/advanced/tax_include_both': '0',
      });

      const product = await api.getFeedProduct(baseURL, fixedProductId);
      expect(price(product.price)).toBe(FIXED_PRICE);
    });
  });

  // ── Group B: Catalog prices EXCLUDING tax ──────────────────────────────
  // Stored €50 = excl tax. Incl = €60.50.

  test.describe('Group B: Catalog prices excluding tax', () => {

    test.beforeAll(async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;
      await api.setMagentoConfig(baseURL, {
        'tax/calculation/price_includes_tax': '0',
      });
      api.reindexAll();
    });

    test.afterAll(async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;
      await api.setMagentoConfig(baseURL, {
        'tax/calculation/price_includes_tax': '1',
      });
      api.reindexAll();
    });

    test('B5: dynamic bundle, add tax on → price with tax added', async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;

      await api.setMagentoConfig(baseURL, {
        ...BASE_CONFIG,
        'magmodules_channable/advanced/tax': '1',
        'magmodules_channable/advanced/tax_include_both': '0',
      });

      const product = await api.getFeedProduct(baseURL, dynamicProductId);
      const expectedIncl = price(CHILDREN_SUM * (1 + TAX_RATE_NL));
      expect(price(product.price)).toBe(expectedIncl);
      expect(price(product.min_price)).toBe(expectedIncl);
    });

    test('B6: dynamic bundle, add tax off → raw excl price', async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;

      await api.setMagentoConfig(baseURL, {
        ...BASE_CONFIG,
        'magmodules_channable/advanced/tax': '0',
        'magmodules_channable/advanced/tax_include_both': '0',
      });

      const product = await api.getFeedProduct(baseURL, dynamicProductId);
      expect(price(product.price)).toBe(CHILDREN_SUM);
      expect(price(product.min_price)).toBe(CHILDREN_SUM);
    });

    test('B7: dynamic bundle, include both → price_incl and price_excl', async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;

      await api.setMagentoConfig(baseURL, {
        ...BASE_CONFIG,
        'magmodules_channable/advanced/tax': '1',
        'magmodules_channable/advanced/tax_include_both': '1',
      });

      const product = await api.getFeedProduct(baseURL, dynamicProductId);
      const expectedIncl = price(CHILDREN_SUM * (1 + TAX_RATE_NL));

      expect(price(product.price_incl)).toBe(expectedIncl);
      expect(price(product.price_excl)).toBe(CHILDREN_SUM);
    });

    test('B8: fixed bundle, add tax on → fixed price + tax', async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;

      await api.setMagentoConfig(baseURL, {
        ...BASE_CONFIG,
        'magmodules_channable/advanced/tax': '1',
        'magmodules_channable/advanced/tax_include_both': '0',
      });

      const product = await api.getFeedProduct(baseURL, fixedProductId);
      const expectedIncl = price(FIXED_PRICE * (1 + TAX_RATE_NL));
      expect(price(product.price)).toBe(expectedIncl);
    });
  });

  // ── Group C: Stock scenarios ───────────────────────────────────────────

  test.describe('Group C: OOS child affects bundle price', () => {

    test.beforeAll(async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;
      // Ensure prices include tax for this group
      await api.setMagentoConfig(baseURL, {
        'tax/calculation/price_includes_tax': '1',
      });
      api.reindexAll();
    });

    test('C9: dynamic bundle, OOS child → min_price reflects only in-stock children', async ({}, testInfo) => {
      const baseURL = testInfo.project.use.baseURL!;

      // Set child B out of stock
      await api.setStockStatus(baseURL, SKUS.childB, 0, false);
      api.reindexAll();

      await api.setMagentoConfig(baseURL, {
        ...BASE_CONFIG,
        'magmodules_channable/advanced/tax': '1',
        'magmodules_channable/advanced/tax_include_both': '0',
      });

      // Single-option bundle (select between A and B): OOS child B excluded,
      // min_price = cheapest in-stock child = child A (€20)
      const product = await api.getFeedProduct(baseURL, dynamicSingleOptId);
      expect(price(product.min_price)).toBe(CHILD_A_PRICE);

      // Restore child B stock
      await api.setStockStatus(baseURL, SKUS.childB, 100, true);
      api.reindexAll();
    });
  });
});
