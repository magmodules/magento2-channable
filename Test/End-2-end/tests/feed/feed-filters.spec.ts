/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';

const api = new ChannableApi();

const FEED_TOKEN = process.env.CHANNABLE_TOKEN || 'e2e-test-token';
const STORE_ID = 1;

/** No product can have an entity_id this high, so a working filter empties the feed. */
const UNREACHABLE_ID = 999999999;

const feedUrl = () => `/channable/feed/json?id=${STORE_ID}&token=${FEED_TOKEN}&page=1`;

const advancedFilter = (attribute: string, condition: string, value: string) =>
  JSON.stringify([{ attribute, condition, value, product_type: 'all' }]);

/**
 * Filters on entity_id, type_id and attribute_set_id used to be dropped without a word: the feed
 * asked the EAV config for their frontend input, got nothing back because they are static columns,
 * and skipped the filter. The feed then returned everything, which looks exactly like a filter that
 * was never saved.
 *
 * The assertions deliberately avoid matching ids from the feed against entity_id: the feed's own id
 * field is configurable and need not be the entity id. Instead each filter is set to a bound that
 * can only produce one outcome, so an ignored filter and an applied one cannot look alike.
 */
test.describe('Feed filters', () => {
  const setFilter = async (baseURL: string, value: string) =>
    api.setMagentoConfig(baseURL, { 'magmodules_channable/filter/filters_data': value });

  const productCount = async (request: any): Promise<number> => {
    const body = await (await request.get(feedUrl())).json();
    return (body.products ?? []).length;
  };

  test.beforeAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;
    await api.setMagentoConfig(baseURL, {
      'magmodules_channable/general/enable': '1',
      'magmodules_channable/filter/filters': '1',
      'magmodules_channable/filter/filters_data': '',
    });
  });

  test.afterAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;
    await api.setMagentoConfig(baseURL, {
      'magmodules_channable/filter/filters': '0',
      'magmodules_channable/filter/filters_data': '',
    });
  });

  test('a greater-than filter on entity_id is applied', async ({ request }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    await setFilter(baseURL, '');
    const unfiltered = await productCount(request);
    expect(unfiltered, 'the feed needs products to filter').toBeGreaterThan(0);

    await setFilter(baseURL, advancedFilter('entity_id', 'gt', String(UNREACHABLE_ID)));
    expect(await productCount(request)).toBe(0);

    await setFilter(baseURL, advancedFilter('entity_id', 'gt', '0'));
    expect(await productCount(request)).toBe(unfiltered);
  });

  test('a less-than-or-equal filter on entity_id is applied', async ({ request }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    await setFilter(baseURL, '');
    const unfiltered = await productCount(request);
    expect(unfiltered).toBeGreaterThan(0);

    // 'lteq' was spelled 'lteg' in the operator map, so this condition never reached the collection.
    await setFilter(baseURL, advancedFilter('entity_id', 'lteq', '0'));
    expect(await productCount(request)).toBe(0);

    await setFilter(baseURL, advancedFilter('entity_id', 'lteq', String(UNREACHABLE_ID)));
    expect(await productCount(request)).toBe(unfiltered);
  });

  test('a filter on attribute_set_id is applied', async ({ request }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    await setFilter(baseURL, '');
    const unfiltered = await productCount(request);
    expect(unfiltered).toBeGreaterThan(0);

    await setFilter(baseURL, advancedFilter('attribute_set_id', 'gt', String(UNREACHABLE_ID)));
    expect(await productCount(request)).toBe(0);
  });
});
