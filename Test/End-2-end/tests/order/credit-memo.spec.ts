/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';
import OrderViewPage from 'Pages/backend/OrderViewPage';

const channableApi = new ChannableApi();
const orderViewPage = new OrderViewPage();

const PRODUCT_ID = parseInt(process.env.PRODUCT_ID || '1', 10);
const CONFIG_BASE = 'magmodules_channable_marketplace/order';

function getOrderIncrementId(response: any): string {
  if (response.order_id) {
    return String(response.order_id);
  }
  throw new Error(`Order creation failed: ${JSON.stringify(response)}`);
}

/**
 * Create an auto-invoiced order and return the increment ID.
 */
async function createInvoicedOrder(baseURL: string): Promise<string> {
  await channableApi.setMagentoConfig(baseURL, {
    [`${CONFIG_BASE}/invoice_order`]: '1',
  });

  const orderData = channableApi.buildOrderData({ productId: PRODUCT_ID });
  const response = await channableApi.postOrder(baseURL, orderData);
  return getOrderIncrementId(response);
}

test.describe('Credit Memo', () => {
  test.beforeEach(async ({ baseURL }) => {
    await channableApi.setMagentoConfig(baseURL, {
      [`${CONFIG_BASE}/import_customer`]: '0',
      [`${CONFIG_BASE}/invoice_order`]: '0',
      [`${CONFIG_BASE}/lvb`]: '0',
      [`${CONFIG_BASE}/lvb_ship`]: '0',
      [`${CONFIG_BASE}/business_order`]: '0',
    });
  });

  test('Credit memo via admin UI', async ({ page, baseURL }) => {
    const incrementId = await createInvoicedOrder(baseURL);
    console.log(`Order created: ${incrementId}`);

    // Open order in admin and verify invoice exists
    await orderViewPage.openByIncrementId(page, incrementId);
    const hasInvoice = await orderViewPage.hasInvoice(page);
    expect(hasInvoice).toBe(true);

    // Navigate to Invoices tab in the order view sidebar
    await page.getByRole('tab', { name: 'Invoices' }).click();
    await page.waitForLoadState('networkidle');

    // Click "View" on the first invoice row
    const invoiceRow = page.locator('.data-row').first();
    await invoiceRow.locator('a', { hasText: 'View' }).click();
    await page.waitForLoadState('networkidle');

    // Click "Credit Memo" button in the top button bar
    await page.locator('button', { hasText: 'Credit Memo' }).click();
    await page.waitForLoadState('networkidle');

    // Submit the credit memo (click "Refund Offline" button)
    await page.locator('button', { hasText: 'Refund Offline' }).click();
    await page.waitForLoadState('networkidle');

    // Verify credit memo was created — check success message
    const successMessage = page.locator('.message-success');
    await expect(successMessage).toBeVisible({ timeout: 15000 });

    const hasCreditMemo = await channableApi.hasCreditMemo(baseURL, incrementId);
    expect(hasCreditMemo).toBe(true);
  });

  test('Credit memo via REST API (V1/invoice/:id/refund)', async ({ page, baseURL }) => {
    const incrementId = await createInvoicedOrder(baseURL);
    console.log(`Order created: ${incrementId}`);

    // Get invoice and order item IDs via REST API
    const invoiceId = await channableApi.getInvoiceId(baseURL, incrementId);
    expect(invoiceId).toBeTruthy();

    const itemInfo = await channableApi.getOrderItemInfo(baseURL, incrementId);
    expect(itemInfo.orderItemId).toBeTruthy();

    // Refund the invoice via REST API
    const result = await channableApi.refundInvoiceViaApi(
      baseURL,
      invoiceId,
      itemInfo.orderItemId,
      itemInfo.qty,
    );

    console.log(`API refund response: ${result.status}`, result.body);

    // Expect 200 OK — the response body is the credit memo ID (returned as string)
    expect(result.status).toBe(200);
    const creditMemoId = parseInt(String(result.body), 10);
    expect(creditMemoId).toBeGreaterThan(0);

    // Verify credit memo is visible in admin order view
    await orderViewPage.openByIncrementId(page, incrementId);

    // Check order status changed to "Closed" (fully refunded)
    const status = await orderViewPage.getOrderStatus(page);
    expect(status.toLowerCase()).toContain('closed');

    // Navigate to Credit Memos tab and verify a credit memo row exists
    await page.getByRole('tab', { name: 'Credit Memos' }).click();
    await page.waitForLoadState('networkidle');

    // The tab content panel is AJAX-loaded; force-open it via DOM
    await page.evaluate(() => {
      const panel = document.querySelector('#sales_order_view_tabs_order_creditmemos_content');
      if (panel) {
        (panel as HTMLElement).style.display = 'block';
      }
    });

    const creditMemoRow = page.locator('#sales_order_view_tabs_order_creditmemos_content .data-row').first();
    await expect(creditMemoRow).toBeVisible({ timeout: 10000 });
  });
});
