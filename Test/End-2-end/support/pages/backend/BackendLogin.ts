/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect} from "@playwright/test";
import {adminUrl} from "../../helpers/AdminUrl";

export default class BackendLogin {
  async login(page) {
    const username = process.env.MAGENTO_ADMIN_USER || 'exampleuser';
    const password = process.env.MAGENTO_ADMIN_PASS || 'examplepassword123';

    await page.goto(adminUrl('/admin'));
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Sign in' }).click();

    await page.waitForURL('**/admin/dashboard/**');

    await expect(page.getByRole('link', { name: 'Most Viewed Products' })).toBeVisible();
  }
}
