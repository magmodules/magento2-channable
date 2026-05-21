/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';

const api = new ChannableApi();

const PRODUCT_ID = parseInt(process.env.PRODUCT_ID || '1', 10);

/**
 * Helper: extract order increment ID from the Channable webhook response.
 */
function getOrderIncrementId(response: any): string {
  if (response.order_id) {
    return String(response.order_id);
  }
  throw new Error(`Order creation failed: ${JSON.stringify(response)}`);
}

test.describe('REST API — /V1/channable/orders', () => {
  let channelId: string;
  let incrementId: string;

  test.beforeAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    // Import an order via webhook so we have data to query
    channelId = 'REST-API-E2E-' + Date.now();
    const orderData = api.buildOrderData({
      productId: PRODUCT_ID,
      channelId,
    });
    const response = await api.postOrder(baseURL, orderData);
    incrementId = getOrderIncrementId(response);
    console.log(`Order imported for REST API test: ${incrementId} (channel_id: ${channelId})`);
  });

  test('returns orders filtered by channel_id', async ({ baseURL }) => {
    const filter = encodeURIComponent(channelId);
    const { status, body } = await api.restGet(
      baseURL!,
      `/channable/orders?searchCriteria[filterGroups][0][filters][0][field]=channel_id&searchCriteria[filterGroups][0][filters][0][value]=${filter}`
    );

    expect(status).toBe(200);
    expect(body.items.length).toBeGreaterThanOrEqual(1);

    const order = body.items.find((o: any) => o.channel_id === channelId);
    expect(order).toBeDefined();
    expect(order.magento_increment_id).toBe(incrementId);
    expect(order.store_id).toBeTruthy();
    expect(order.channable_id).toBeTruthy();
    expect(order.created_at).toBeTruthy();
  });

  test('returns all orders without filter', async ({ baseURL }) => {
    const { status, body } = await api.restGet(
      baseURL!,
      '/channable/orders?searchCriteria[pageSize]=5'
    );

    expect(status).toBe(200);
    expect(body).toHaveProperty('items');
    expect(body).toHaveProperty('total_count');
    expect(body.items.length).toBeGreaterThanOrEqual(1);
    expect(body.items.length).toBeLessThanOrEqual(5);

    // Verify DTO shape — all expected fields present
    const item = body.items[0];
    expect(item).toHaveProperty('entity_id');
    expect(item).toHaveProperty('channable_id');
    expect(item).toHaveProperty('channel_id');
    expect(item).toHaveProperty('store_id');
    expect(item).toHaveProperty('status');
    expect(item).toHaveProperty('created_at');
  });

  test('rejects unauthenticated requests', async ({ baseURL }) => {
    const url = `${baseURL}rest/V1/channable/orders?searchCriteria[pageSize]=1`;
    const response = await fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });

    expect(response.status).toBe(401);
  });
});

test.describe('REST API — /V1/channable/returns', () => {
  let channelId: string;

  test.beforeAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    // Enable returns
    await api.setMagentoConfig(baseURL, {
      'magmodules_channable_marketplace/returns/enable': '1',
    });

    // Import a return via webhook so we have data to query
    channelId = 'REST-RETURN-E2E-' + Date.now();
    const returnData = api.buildReturnData({
      productId: PRODUCT_ID,
      channelId,
    });
    const response = await api.postReturn(baseURL, returnData);
    console.log(`Return imported for REST API test: channel_id=${channelId}`, response);
  });

  test('returns filtered by channel_id', async ({ baseURL }) => {
    const filter = encodeURIComponent(channelId);
    const { status, body } = await api.restGet(
      baseURL!,
      `/channable/returns?searchCriteria[filterGroups][0][filters][0][field]=channel_id&searchCriteria[filterGroups][0][filters][0][value]=${filter}`
    );

    expect(status).toBe(200);
    expect(body.items.length).toBeGreaterThanOrEqual(1);

    const ret = body.items.find((r: any) => r.channel_id === channelId);
    expect(ret).toBeDefined();
    expect(ret.channel_name).toBe('Channable');
    expect(ret.status).toBeTruthy();
    expect(ret.store_id).toBeTruthy();
    expect(ret.created_at).toBeTruthy();
  });

  test('returns all without filter', async ({ baseURL }) => {
    const { status, body } = await api.restGet(
      baseURL!,
      '/channable/returns?searchCriteria[pageSize]=5'
    );

    expect(status).toBe(200);
    expect(body).toHaveProperty('items');
    expect(body).toHaveProperty('total_count');
    expect(body.items.length).toBeGreaterThanOrEqual(1);

    // Verify DTO shape
    const item = body.items[0];
    expect(item).toHaveProperty('entity_id');
    expect(item).toHaveProperty('channable_id');
    expect(item).toHaveProperty('channel_id');
    expect(item).toHaveProperty('channel_name');
    expect(item).toHaveProperty('status');
    expect(item).toHaveProperty('store_id');
    expect(item).toHaveProperty('created_at');
  });

  test('rejects unauthenticated requests', async ({ baseURL }) => {
    const url = `${baseURL}rest/V1/channable/returns?searchCriteria[pageSize]=1`;
    const response = await fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });

    expect(response.status).toBe(401);
  });
});
