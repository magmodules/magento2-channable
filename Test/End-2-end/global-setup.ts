/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {type FullConfig} from '@playwright/test';

async function globalSetup(config: FullConfig) {
  process.env['NODE_TLS_REJECT_UNAUTHORIZED'] = '0';
  const { baseURL } = config.projects[0].use;

  await getAdminToken(baseURL);

  if (!process.env.CHANNABLE_TOKEN) {
    console.warn('CHANNABLE_TOKEN not set. Set it via environment variable or configure-channable.sh.');
  } else {
    console.log('Using CHANNABLE_TOKEN from environment.');
  }
}

const getAdminToken = async (baseURL: string) => {
  const username = process.env.MAGENTO_ADMIN_USER || 'exampleuser';
  const password = process.env.MAGENTO_ADMIN_PASS || 'examplepassword123';

  console.log('Requesting admin token from "' + baseURL + '"...');

  const response = await fetch(baseURL + 'rest/all/V1/integration/admin/token', {
    method: 'POST',
    headers: {
      'accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ username, password }),
  });

  process.env.admin_token = await response.json();
  console.log('Admin token acquired.');
}

export default globalSetup;
