/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {Page} from "@playwright/test";
import BackendLogin from "Pages/backend/BackendLogin";

const backendLogin = new BackendLogin();

export default class CustomerViewPage {
  async searchByEmail(page: Page, email: string): Promise<void> {
    const url = `/admin/customer/index/`;
    await page.goto(url);
    await this.checkIfLoggedIn(page, url);

    // Clear any existing filters first
    const clearButton = page.locator('button', { hasText: 'Clear all' });
    if (await clearButton.isVisible({ timeout: 3000 }).catch(() => false)) {
      await clearButton.click();
      await page.waitForLoadState('networkidle');
    }

    // Use the search/filter input for email
    const searchInput = page.locator('.data-grid-search-control, #fulltext');
    await searchInput.fill(email);
    await page.keyboard.press('Enter');
    await page.waitForLoadState('networkidle');
  }

  async customerExists(page: Page, email: string): Promise<boolean> {
    await this.searchByEmail(page, email);
    const row = page.locator('.data-grid-tbody tr', { hasText: email });
    return (await row.count()) > 0;
  }

  async getCustomerGroup(page: Page): Promise<string> {
    // Assumes we're on the customer grid and a matching row is visible
    const row = page.locator('.data-grid-tbody tr').first();
    const cells = row.locator('td');
    const count = await cells.count();

    // Find the "Group" column by header
    const headers = page.locator('.data-grid-thead th, thead th');
    const headerCount = await headers.count();
    let groupIndex = -1;

    for (let i = 0; i < headerCount; i++) {
      const text = (await headers.nth(i).textContent()).trim();
      if (text === 'Group') {
        groupIndex = i;
        break;
      }
    }

    if (groupIndex === -1) {
      throw new Error('Group column not found in customer grid');
    }

    return (await cells.nth(groupIndex).textContent()).trim();
  }

  private async checkIfLoggedIn(page: Page, urlToNavigateAfterLogin: string) {
    const passwordElement = Array.from(await page.getByText('Forgot your password?').all()).length;

    if (passwordElement === 1) {
      await backendLogin.login(page);
      await page.goto(urlToNavigateAfterLogin);
    }
  }
}
