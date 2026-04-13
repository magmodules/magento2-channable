/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Build an absolute admin URL from a relative admin path.
 * This ensures the store code path (e.g. /default/) does not prefix admin routes.
 *
 * @example adminUrl('/admin/richsnippet/localbusiness/index')
 */
export function adminUrl(path: string): string {
  const baseURL = process.env.BASE_URL || 'https://magento.test/';
  return new URL(path, baseURL).toString();
}
