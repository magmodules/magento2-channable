/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { test, expect } from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';

const api = new ChannableApi();

const PRODUCT_ID = parseInt(process.env.PRODUCT_ID || '1', 10);
const CONFIG_BASE = 'magmodules_channable_marketplace/item';

/**
 * Regression test for item_id / id field mismatch in channable_items.
 *
 * When "Product Updates" modus is set to Observer, saving MSI stock in admin
 * triggers an item save on channable_items. The table has both `item_id` (PK)
 * and `id` (Magento product ID) columns. Without the getId() override, Magento
 * uses the wrong column value in the WHERE clause, causing constraint violations.
 */
test.describe('Item Updates — save regression', () => {
  const STORE_ID = 1;
  const ITEM_ID = `${STORE_ID}${String(PRODUCT_ID).padStart(8, '0')}`;

  test.beforeAll(async ({}, testInfo) => {
    const baseURL = testInfo.project.use.baseURL!;

    // Enable item updates with observer modus
    await api.setMagentoConfig(baseURL, {
      [`${CONFIG_BASE}/enable`]: '1',
      [`${CONFIG_BASE}/invalidation_modus`]: 'observer',
    });

    // Seed a channable_items row for our product (simulates initial feed sync)
    api['db']!.query(`
      $pdo->prepare("DELETE FROM channable_items WHERE item_id = ?")->execute(['${ITEM_ID}']);
      $pdo->prepare("INSERT INTO channable_items (item_id, store_id, id, title, price, qty, is_in_stock, needs_update, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute(['${ITEM_ID}', ${STORE_ID}, ${PRODUCT_ID}, 'E2E Test Product', 19.99, 10, 1, 0, 'Success']);
      echo 'seeded';
    `);
  });

  test.afterAll(() => {
    try {
      api['db']!.query(`
        $pdo->prepare("DELETE FROM channable_items WHERE item_id = ?")->execute(['${ITEM_ID}']);
        echo 'cleaned';
      `);
    } catch {
      // ignore
    }
  });

  test('saving a product in admin should not cause constraint violation on channable_items', async ({ page }) => {
    // 1. Verify item exists before we start
    const before = api['db']!.query(`
      $stmt = $pdo->prepare("SELECT needs_update, qty FROM channable_items WHERE item_id = ?");
      $stmt->execute(['${ITEM_ID}']);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      echo json_encode($row ?: 'not_found');
    `);
    const beforeData = JSON.parse(before.trim());
    expect(beforeData).not.toBe('not_found');
    expect(String(beforeData.needs_update)).toBe('0');

    // 2. Open the product edit page in admin
    await page.goto(`/admin/catalog/product/edit/id/${PRODUCT_ID}`);
    await page.waitForLoadState('networkidle');

    // 3. Change the stock quantity
    const qtyField = page.locator('input[name="quantity_and_stock_status[qty]"]');
    if (await qtyField.isVisible()) {
      const currentQty = await qtyField.inputValue();
      const newQty = String(parseInt(currentQty || '100', 10) + 1);
      await qtyField.fill(newQty);
    }

    // 4. Save the product
    await page.click('#save-button');
    await page.waitForLoadState('networkidle');

    // 5. Verify save succeeded — should see success message, not an error
    const successMessage = page.locator('.message-success');
    const errorMessage = page.locator('.message-error');

    await expect(successMessage).toBeVisible({ timeout: 30000 });

    // Ensure no constraint violation error appeared
    const hasError = await errorMessage.count();
    if (hasError > 0) {
      const errorText = await errorMessage.first().textContent();
      expect(errorText).not.toContain('constraint');
      expect(errorText).not.toContain('item_id');
    }

    // 6. Verify the channable_items row was flagged for update (needs_update = 1)
    //    and NOT corrupted by the id mismatch
    const after = api['db']!.query(`
      $stmt = $pdo->prepare("SELECT needs_update, item_id, id FROM channable_items WHERE item_id = ?");
      $stmt->execute(['${ITEM_ID}']);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      echo json_encode($row ?: 'not_found');
    `);
    const afterData = JSON.parse(after.trim());
    expect(afterData).not.toBe('not_found');
    expect(String(afterData.item_id)).toBe(ITEM_ID);
    expect(String(afterData.id)).toBe(String(PRODUCT_ID));
    expect(String(afterData.needs_update)).toBe('1');
  });
});
