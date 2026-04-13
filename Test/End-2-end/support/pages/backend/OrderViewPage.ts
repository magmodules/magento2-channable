/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, Page} from "@playwright/test";
import BackendLogin from "Pages/backend/BackendLogin";

const backendLogin = new BackendLogin();

export default class OrderViewPage {
  async openOrderById(page: Page, orderId: number) {
    const url = `/admin/sales/order/view/order_id/${orderId}`;
    await page.goto(url);
    await this.checkIfLoggedIn(page, url);
  }

  async openByIncrementId(page: Page, incrementId: string) {
    const url = `/admin/sales/order/`;
    await page.goto(url);
    await this.checkIfLoggedIn(page, url);

    const row = await page.locator('.data-row', { hasText: incrementId })
      .locator('a.action-menu-item', { hasText: 'View' });

    const href = await row.getAttribute('href');
    await page.goto(href);

    await page.getByText('Submit Comment').isVisible();
  }

  async getGrandTotal(page: Page): Promise<string> {
    const row = page.locator('tr', { hasText: 'Grand Total' }).last();
    return (await row.locator('td strong').last().textContent()).trim();
  }

  async getSubtotal(page: Page): Promise<string> {
    const row = page.locator('.order-subtotal-table tr', { hasText: 'Subtotal' }).first();
    return (await row.locator('td').last().textContent()).trim();
  }

  async getTaxAmount(page: Page): Promise<string> {
    const row = page.locator('.order-subtotal-table tr', { hasText: 'Total Tax' }).first();
    if (await row.count() === 0) {
      return '€0.00';
    }
    return (await row.locator('td').last().textContent()).trim();
  }

  /**
   * Get the tax percent from the order items table.
   * Finds the cell containing a percentage value (e.g. "21%") in the first data row.
   */
  async getTaxPercent(page: Page): Promise<string> {
    const table = page.locator('.edit-order-table, .order-tables .data-table').first();
    const firstRow = table.locator('tbody tr').first();

    // Find the cell that contains a percentage pattern like "21%" or "19%"
    const cells = firstRow.locator('td');
    const count = await cells.count();

    for (let i = 0; i < count; i++) {
      const text = (await cells.nth(i).textContent()).trim();
      if (/^\d+(\.\d+)?%$/.test(text)) {
        return text;
      }
    }

    throw new Error('Tax Percent cell not found in items table');
  }

  async getItemPrice(page: Page): Promise<string> {
    return this.getItemColumnValue(page, 'Price');
  }

  async getOriginalPrice(page: Page): Promise<string> {
    return this.getItemColumnValue(page, 'Original Price');
  }

  private async getItemColumnValue(page: Page, columnName: string): Promise<string> {
    const table = page.locator('.edit-order-table, .order-tables .data-table').first();
    const headers = table.locator('thead th');
    const headerCount = await headers.count();
    let colIndex = -1;

    for (let i = 0; i < headerCount; i++) {
      const text = (await headers.nth(i).textContent()).trim();
      if (text === columnName) {
        colIndex = i;
        break;
      }
    }

    if (colIndex === -1) {
      throw new Error(`${columnName} column not found in items table`);
    }

    const firstRow = table.locator('tbody tr').first();
    const cell = firstRow.locator('td').nth(colIndex);
    return (await cell.textContent()).trim();
  }

  async getOrderStatus(page: Page): Promise<string> {
    return (await page.locator('#order_status').textContent()).trim();
  }

  async getShippingAmount(page: Page): Promise<string> {
    const row = page.locator('.order-subtotal-table tr', { hasText: 'Shipping & Handling' }).first();
    return (await row.locator('td').last().textContent()).trim();
  }

  async getDiscountAmount(page: Page): Promise<string> {
    const row = page.locator('.order-subtotal-table tr', { hasText: 'Discount' }).first();
    return (await row.locator('td').last().textContent()).trim();
  }

  async getPaymentMethod(page: Page): Promise<string> {
    const section = page.locator('.order-payment-method');
    return (await section.locator('dd').first().textContent()).trim();
  }

  async hasInvoice(page: Page): Promise<boolean> {
    // Check if any item in the order items table has "Invoiced" status
    const invoicedCell = page.locator('td', { hasText: 'Invoiced' }).first();
    return (await invoicedCell.count()) > 0;
  }

  async hasShipment(page: Page): Promise<boolean> {
    // Check if any item in the order items table has "Shipped" status
    const shippedCell = page.locator('td', { hasText: 'Shipped' }).first();
    return (await shippedCell.count()) > 0;
  }

  async getShippingMethod(page: Page): Promise<string> {
    const section = page.locator('.order-shipping-method');
    return (await section.textContent()).trim();
  }

  async getCustomerGroup(page: Page): Promise<string> {
    const row = page.locator('tr', { hasText: 'Customer Group' }).first();
    return (await row.locator('td').first().textContent()).trim();
  }

  async getCustomerName(page: Page): Promise<string> {
    const row = page.locator('tr', { hasText: 'Customer Name' }).first();
    return (await row.locator('td').first().textContent()).trim();
  }

  async isGuestOrder(page: Page): Promise<boolean> {
    // Check the Customer Group cell in the Account Information table
    const groupCell = page.locator('tr', { hasText: 'Customer Group' }).locator('td').first();
    const groupText = (await groupCell.textContent()).trim();
    return groupText === 'NOT LOGGED IN';
  }

  async getRowTotal(page: Page): Promise<string> {
    return this.getItemColumnValue(page, 'Row Total');
  }

  async getOrderIncrementId(page: Page): Promise<string> {
    // The heading is h1 with format "#000000123" or "# 000000123"
    const heading = page.locator('h1').first();
    const text = (await heading.textContent()).trim();
    const match = text.match(/#\s*(.+)/);
    return match ? match[1].trim() : text;
  }

  private async checkIfLoggedIn(page: Page, urlToNavigateAfterLogin: string) {
    const passwordElement = Array.from(await page.getByText('Forgot your password?').all()).length;

    if (passwordElement === 1) {
      await backendLogin.login(page);
      await page.goto(urlToNavigateAfterLogin);
    }
  }
}
