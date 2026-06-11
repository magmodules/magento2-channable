/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';

const api = new ChannableApi();

const FEED_TOKEN = process.env.CHANNABLE_TOKEN || 'e2e-test-token';
const STORE_ID = 1;

const CONFIG = {
  'magmodules_channable/general/enable': '1',
};

test.describe('Feed Generation', () => {
  test.beforeAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;
    await api.setMagentoConfig(baseURL, CONFIG);
  });

  test('feed endpoint returns valid JSON without errors', async ({ request }) => {
    const response = await request.get(`/channable/feed/json?id=${STORE_ID}&token=${FEED_TOKEN}&page=1`);

    expect(response.status()).toBe(200);

    const body = await response.json();
    expect(body).not.toHaveProperty('error');
  });

  test('feed endpoint returns products array', async ({ request }) => {
    const response = await request.get(`/channable/feed/json?id=${STORE_ID}&token=${FEED_TOKEN}&page=1`);

    const body = await response.json();
    expect(body).toHaveProperty('products');
    expect(Array.isArray(body.products)).toBe(true);
  });

  test('feed products contain required id and title fields', async ({ request }) => {
    const response = await request.get(`/channable/feed/json?id=${STORE_ID}&token=${FEED_TOKEN}&page=1`);

    const body = await response.json();
    const products = body.products ?? [];

    // Skip if no products in feed (empty catalog)
    if (products.length === 0) return;

    for (const product of products) {
      // Every product row must have an id and title
      expect(product).toHaveProperty('id');
      expect(product).toHaveProperty('title');
      expect(product.id).toBeTruthy();
    }
  });

  test('feed does not crash with visibility filter enabled', async ({ request }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    // Enable visibility filter and include "Not Visible Individually" (value 1)
    await api.setMagentoConfig(baseURL, {
      ...CONFIG,
      'magmodules_channable/filter/visbility_enabled': '1',
      'magmodules_channable/filter/visbility': '1',
    });

    const response = await request.get(`/channable/feed/json?id=${STORE_ID}&token=${FEED_TOKEN}&page=1`);

    expect(response.status()).toBe(200);

    const body = await response.json();
    expect(body).not.toHaveProperty('error');

    // Reset visibility filter
    await api.setMagentoConfig(baseURL, {
      ...CONFIG,
      'magmodules_channable/filter/visbility_enabled': '0',
    });
  });

  test('feed returns empty for invalid token', async ({ request }) => {
    const response = await request.get(`/channable/feed/json?id=${STORE_ID}&token=wrong-token&page=1`);

    expect(response.status()).toBe(200);

    const body = await response.json();
    // Should return empty array, not an error page
    expect(Array.isArray(body) || (typeof body === 'object' && Object.keys(body).length === 0)).toBe(true);
  });

  test('feed returns empty when module is disabled', async ({ request }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    await api.setMagentoConfig(baseURL, {
      'magmodules_channable/general/enable': '0',
      'magmodules_channable/general/token': FEED_TOKEN,
    });

    const response = await request.get(`/channable/feed/json?id=${STORE_ID}&token=${FEED_TOKEN}&page=1`);

    expect(response.status()).toBe(200);

    const body = await response.json();
    expect(Array.isArray(body) || (typeof body === 'object' && Object.keys(body).length === 0)).toBe(true);

    // Re-enable
    await api.setMagentoConfig(baseURL, CONFIG);
  });

  test('single product feed via pid parameter', async ({ request }) => {
    const response = await request.get(`/channable/feed/json?id=${STORE_ID}&token=${FEED_TOKEN}&pid=1`);

    expect(response.status()).toBe(200);

    const body = await response.json();
    expect(body).not.toHaveProperty('error');
  });
});
