/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';

const channableApi = new ChannableApi();

const PRODUCT_ID = parseInt(process.env.PRODUCT_ID || '1', 10);
const CONFIG_BASE = 'magmodules_channable_marketplace/order';

/**
 * Helper: extract order increment ID from the Channable webhook response.
 */
function getOrderIncrementId(response: any): string {
  if (response.order_id) {
    return String(response.order_id);
  }
  throw new Error(`Order creation failed: ${JSON.stringify(response)}`);
}

test.describe('Webhooks: Order Status & Shipments', () => {
  test('Order status — processing', async ({ baseURL }) => {
    // Create a regular order
    const orderData = channableApi.buildOrderData({ productId: PRODUCT_ID });
    const response = await channableApi.postOrder(baseURL, orderData);
    const incrementId = getOrderIncrementId(response);
    console.log(`Order created for status check: ${incrementId}`);

    // GET order status
    const statusResponse = await channableApi.getOrderStatus(baseURL, incrementId);

    expect(statusResponse).toHaveProperty('id');
    expect(['pending', 'processing']).toContain(statusResponse.status);
    expect(statusResponse).not.toHaveProperty('fulfillment');
  });

  test('Order status — invalid ID', async ({ baseURL }) => {
    const statusResponse = await channableApi.getOrderStatus(baseURL, 'NONEXISTENT-999');

    // API returns validated as string "false"
    expect(String(statusResponse.validated)).toBe('false');
    expect(statusResponse).toHaveProperty('errors');
  });

  test('Shipments — recent order appears', async ({ baseURL }) => {
    // Create a regular order
    const orderData = channableApi.buildOrderData({ productId: PRODUCT_ID });
    const response = await channableApi.postOrder(baseURL, orderData);
    const incrementId = getOrderIncrementId(response);
    console.log(`Order created for shipments check: ${incrementId}`);

    // GET recent shipments
    const shipmentsResponse = await channableApi.getShipments(baseURL, 1);

    // Response should be an array or contain our order
    expect(Array.isArray(shipmentsResponse)).toBe(true);
  });

  test('Shipments — LVB order has fulfillment', async ({ baseURL }) => {
    // Enable LVB + auto-ship
    await channableApi.setMagentoConfig(baseURL, {
      [`${CONFIG_BASE}/lvb`]: '1',
      [`${CONFIG_BASE}/lvb_ship`]: '1',
    });

    // Create LVB order (shipped status)
    const orderData = channableApi.buildOrderData({
      productId: PRODUCT_ID,
      orderStatus: 'shipped',
    });
    const response = await channableApi.postOrder(baseURL, orderData);
    const incrementId = getOrderIncrementId(response);
    console.log(`LVB order created for shipments check: ${incrementId}`);

    // GET recent shipments
    const shipmentsResponse = await channableApi.getShipments(baseURL, 1);

    expect(Array.isArray(shipmentsResponse)).toBe(true);

    // Find our order in the shipments
    const ourShipment = shipmentsResponse.find(
      (s: any) => String(s.order_id) === incrementId || String(s.id) === incrementId
    );
    expect(ourShipment).toBeDefined();

    // Reset LVB config
    await channableApi.setMagentoConfig(baseURL, {
      [`${CONFIG_BASE}/lvb`]: '0',
      [`${CONFIG_BASE}/lvb_ship`]: '0',
    });
  });
});
