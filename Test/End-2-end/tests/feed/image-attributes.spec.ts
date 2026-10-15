/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { execSync } from 'child_process';
import { test, expect } from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';

const api = new ChannableApi();

const FEED_TOKEN = process.env.CHANNABLE_TOKEN || 'e2e-test-token';
const STORE_ID = 1;
const CONTAINER = process.env.MAGENTO_CONTAINER;

const ATTRIBUTE_CODE = 'e2e_extra_image';
const FEED_FIELD = 'extra_image';

const feedUrl = () => `/channable/feed/json?id=${STORE_ID}&token=${FEED_TOKEN}&page=1`;

/**
 * Magento stores an image attribute that was filled and then cleared as the literal string
 * 'no_selection'. The feed used to hand that to getMediaUrl(), producing a URL that resolves to
 * nothing. Marketplaces queue it, fetch it, and show a broken image on the listing.
 */
test.describe('Image attributes in the feed', () => {
  test.skip(!CONTAINER, 'Needs MAGENTO_CONTAINER to create the attribute and set its value');

  let productId: string;

  /**
   * The PHP is passed base64 encoded: a double quoted shell argument would let sh expand every
   * $variable in it before PHP ever sees the code.
   */
  const magento = (php: string): string => {
    const encoded = Buffer.from(php).toString('base64');
    return execSync(
      `docker exec ${CONTAINER} php -r 'eval(base64_decode("${encoded}"));'`,
      { stdio: 'pipe', timeout: 60000 }
    ).toString();
  };

  test.beforeAll(async ({ request }, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    await api.setMagentoConfig(baseURL, { 'magmodules_channable/general/enable': '1' });

    // Pick a product that is actually in the feed, so the assertion cannot pass on an empty result.
    const feed = await (await request.get(feedUrl())).json();
    // The feed's id field is the entity id, but guard against rows that carry something else.
    const products = (feed.products ?? []).filter((p: { id: string }) => /^\d+$/.test(String(p.id)));
    expect(products.length, 'the feed needs a product to attach the attribute to').toBeGreaterThan(0);
    productId = String(products[0].id);

    magento(`
      $root = null;
      foreach (['/var/www/html', '/data'] as $candidate) {
          if (file_exists($candidate . '/app/bootstrap.php')) { $root = $candidate; break; }
      }
      if ($root === null) { fwrite(STDERR, 'no Magento root found'); exit(1); }
      require $root . '/app/bootstrap.php';
      $app = \\Magento\\Framework\\App\\Bootstrap::create($root, $_SERVER);
      $om = $app->getObjectManager();
      $om->get(\\Magento\\Framework\\App\\State::class)->setAreaCode('adminhtml');

      $repository = $om->get(\\Magento\\Catalog\\Api\\ProductAttributeRepositoryInterface::class);
      try {
          $repository->get('${ATTRIBUTE_CODE}');
      } catch (\\Magento\\Framework\\Exception\\NoSuchEntityException $e) {
          $attribute = $om->get(\\Magento\\Catalog\\Api\\Data\\ProductAttributeInterfaceFactory::class)->create();
          $attribute->setAttributeCode('${ATTRIBUTE_CODE}')
              ->setEntityTypeId(4)
              ->setFrontendInput('media_image')
              ->setBackendType('varchar')
              ->setIsUserDefined(true)
              ->setIsRequired(false)
              ->setScope('store')
              ->setDefaultFrontendLabel('E2E extra image');
          $repository->save($attribute);

          $setId = $om->get(\\Magento\\Eav\\Model\\Config::class)
              ->getEntityType('catalog_product')->getDefaultAttributeSetId();
          $groupId = $om->get(\\Magento\\Eav\\Model\\ResourceModel\\Entity\\Attribute\\Group\\CollectionFactory::class)
              ->create()->setAttributeSetFilter($setId)->setPageSize(1)->getFirstItem()->getId();
          $om->get(\\Magento\\Catalog\\Api\\ProductAttributeManagementInterface::class)
              ->assign($setId, $groupId, '${ATTRIBUTE_CODE}', 100);
      }

      $product = $om->get(\\Magento\\Catalog\\Api\\ProductRepositoryInterface::class)->getById(${productId});
      $product->setData('${ATTRIBUTE_CODE}', 'no_selection');
      $om->get(\\Magento\\Catalog\\Api\\ProductRepositoryInterface::class)->save($product);
      echo 'ok';
    `);

    await api.setMagentoConfig(baseURL, {
      'magmodules_channable/advanced/extra_fields': JSON.stringify([
        { name: FEED_FIELD, attribute: ATTRIBUTE_CODE, actions: '' },
      ]),
    });
  });

  test.afterAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;
    await api.setMagentoConfig(baseURL, { 'magmodules_channable/advanced/extra_fields': '' });
  });

  test('a cleared image attribute leaves the feed field empty', async ({ request }) => {
    const feed = await (await request.get(feedUrl())).json();
    const product = (feed.products ?? []).find((p: { id: string }) => String(p.id) === productId);

    expect(product, `product ${productId} should still be in the feed`).toBeTruthy();

    const value = product[FEED_FIELD] ?? '';
    expect(value).not.toContain('no_selection');
    expect(value).toBeFalsy();
  });
});
