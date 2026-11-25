/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';

const api = new ChannableApi();

const CONFIG_BUNDLE_IMPORT = 'magmodules_channable_marketplace/order/import_bundle_products';
const BUNDLE_PRICE = 100.00;

const SKUS = {
  childA: 'e2e-split-child-a',
  childB: 'e2e-split-child-b',
  bundle: 'e2e-split-bundle',
};

let bundleId: number;

type OrderItemRow = { sku: string; product_type: string; parent_item_id: string | null; qty_ordered: string };

/**
 * Helper: build simple product payload.
 */
function simpleProduct(sku: string) {
  return {
    sku,
    name: sku,
    attribute_set_id: 4,
    price: 10,
    type_id: 'simple',
    status: 1,
    visibility: 1,
    weight: 1,
    extension_attributes: {
      stock_item: { qty: 1000, is_in_stock: true },
      category_links: [],
    },
    custom_attributes: [
      { attribute_code: 'tax_class_id', value: '2' },
    ],
  };
}

/**
 * Helper: one required bundle option holding a single default child with a fixed quantity of 1.
 */
function bundleOption(title: string, sku: string, position: number) {
  return {
    title,
    required: true,
    type: 'select',
    position,
    product_links: [
      { sku, qty: 1, position: 0, is_default: true, price_type: 0, price: 0, can_change_quantity: 0 },
    ],
  };
}

/**
 * Helper: read the order items of an imported order straight from sales_order_item.
 */
function getOrderItems(incrementId: string): OrderItemRow[] {
  const output = api['db']!.query(`
    $stmt = $pdo->prepare("SELECT i.sku, i.product_type, i.parent_item_id, i.qty_ordered
      FROM sales_order_item i JOIN sales_order o ON o.entity_id = i.order_id
      WHERE o.increment_id = ? ORDER BY i.item_id");
    $stmt->execute(['${incrementId}']);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  `);

  return JSON.parse(output.trim());
}

/**
 * A marketplace can split one product over several order lines: two lines with quantity 1 instead
 * of one line with quantity 2. Magento merges those lines into a single quote item. For a bundle
 * the quantity of the children has to follow the bundle, whatever the number of lines it came in on.
 */
const testCases = [
  { title: 'one line with quantity 2', lines: [2] },
  { title: 'two lines with quantity 1', lines: [1, 1] },
  { title: 'four lines with quantity 2 + 1 + 1 + 1', lines: [2, 1, 1, 1] },
];

test.describe('Order import: bundle on several order lines', () => {

  test.beforeAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    for (const sku of [SKUS.bundle, SKUS.childA, SKUS.childB]) {
      try { await api.deleteProduct(baseURL, sku); } catch { /* ignore */ }
    }

    await api.createProduct(baseURL, simpleProduct(SKUS.childA));
    await api.createProduct(baseURL, simpleProduct(SKUS.childB));
    await api.createProduct(baseURL, {
      sku: SKUS.bundle,
      name: 'E2E Split Lines Bundle',
      attribute_set_id: 4,
      type_id: 'bundle',
      status: 1,
      visibility: 4,
      price: BUNDLE_PRICE,
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
          bundleOption('Option A', SKUS.childA, 0),
          bundleOption('Option B', SKUS.childB, 1),
        ],
      },
    });

    bundleId = await api.getProductId(baseURL, SKUS.bundle);

    await api.setMagentoConfig(baseURL, { [CONFIG_BUNDLE_IMPORT]: '1' });
    api.reindexAll();
  });

  test.afterAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    for (const sku of [SKUS.bundle, SKUS.childA, SKUS.childB]) {
      try { await api.deleteProduct(baseURL, sku); } catch { /* ignore */ }
    }

    await api.setMagentoConfig(baseURL, { [CONFIG_BUNDLE_IMPORT]: '0' });
  });

  for (const testCase of testCases) {
    test(`Bundle on ${testCase.title}: children follow the bundle quantity`, async ({ baseURL }) => {
      const [firstLine, ...otherLines] = testCase.lines;
      const totalQty = testCase.lines.reduce((sum, qty) => sum + qty, 0);

      const orderData = api.buildOrderData({
        productId: bundleId,
        price: BUNDLE_PRICE,
        quantity: firstLine,
        extraProducts: otherLines.map(quantity => ({ id: bundleId, price: BUNDLE_PRICE, quantity })),
      });
      expect(orderData.products).toHaveLength(testCase.lines.length);

      const response = await api.postOrder(baseURL!, orderData);
      expect(response.order_id, `Order import failed: ${JSON.stringify(response)}`).toBeTruthy();

      const items = getOrderItems(String(response.order_id));
      const parents = items.filter(item => item.parent_item_id === null);
      const children = items.filter(item => item.parent_item_id !== null);

      expect(parents).toHaveLength(1);
      expect(parents[0].product_type).toBe('bundle');
      expect(parseFloat(parents[0].qty_ordered)).toBe(totalQty);

      expect(children.map(item => item.sku).sort()).toEqual([SKUS.childA, SKUS.childB]);
      for (const child of children) {
        expect(parseFloat(child.qty_ordered), `qty_ordered of ${child.sku}`).toBe(totalQty);
      }
    });
  }
});
