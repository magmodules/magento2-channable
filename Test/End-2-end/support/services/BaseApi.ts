/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import { execSync } from 'child_process';
import { MagentoDb } from '../helpers/MagentoDb';

/**
 * Shared base class for module-specific API services.
 * Provides config management, cache flushing, and DB helpers.
 *
 * Extend this class in your module and add module-specific methods.
 */
export default class BaseApi {
  protected db: MagentoDb | null;
  protected container: string | undefined;

  constructor() {
    this.container = process.env.MAGENTO_CONTAINER;
    this.db = this.container ? new MagentoDb(this.container) : null;
  }

  /**
   * Set Magento configuration values and flush config cache.
   * Uses docker exec if MAGENTO_CONTAINER is set, otherwise config-setter.php.
   */
  async setMagentoConfig(baseURL: string, configs: Record<string, string | number>): Promise<void> {
    if (this.container && this.db) {
      for (const [configPath, value] of Object.entries(configs)) {
        this.db.setConfig(configPath, String(value));
        console.log(`Config set: ${configPath} = ${value}`);
      }
      execSync(`docker exec ${this.container} bin/magento cache:flush config`, { stdio: 'pipe' });
    } else {
      await this.setConfigViaHelper(baseURL, configs);
    }
  }

  /**
   * Delete all config rows matching a LIKE pattern, reverting to config.xml defaults.
   */
  resetConfig(pathPattern: string): void {
    if (!this.db) return;
    this.db.resetConfig(pathPattern);
    console.log(`Config reset: ${pathPattern}`);
  }

  /**
   * Flush full page cache and related cache types.
   */
  flushPageCache(): void {
    if (!this.container) return;
    execSync(`docker exec ${this.container} bin/magento cache:flush full_page block_html layout config`, {
      stdio: 'pipe',
      timeout: 30000,
    });
    console.log('Page cache flushed.');
  }

  /**
   * Flush all caches.
   */
  flushAllCaches(): void {
    if (!this.container) return;
    execSync(`docker exec ${this.container} bin/magento cache:flush`, {
      stdio: 'pipe',
      timeout: 30000,
    });
    console.log('All caches flushed.');
  }

  /**
   * Set a product EAV attribute value by SKU.
   */
  setProductAttribute(sku: string, attributeCode: string, value: string): void {
    if (!this.db) {
      throw new Error('MAGENTO_CONTAINER env var is required');
    }
    this.db.setProductAttribute(sku, attributeCode, value);
    console.log(`Product attribute set: ${sku}.${attributeCode} = ${value}`);
    this.flushAllCaches();
  }

  /**
   * Get a category entity_id by its name.
   */
  getCategoryIdByName(name: string): string | null {
    if (!this.db) return null;
    return this.db.getCategoryIdByName(name);
  }

  /**
   * Run a PHP snippet inside the container (for custom queries).
   */
  protected execInContainer(phpCode: string): string {
    if (!this.db) {
      throw new Error('MAGENTO_CONTAINER env var is required');
    }
    return this.db.query(phpCode);
  }

  private async setConfigViaHelper(baseURL: string, configs: Record<string, string | number>): Promise<void> {
    const token = process.env.admin_token;
    const configArray = Object.entries(configs).map(([path, value]) => ({
      path,
      value: String(value),
    }));

    const response = await fetch(`${baseURL}opt/config-setter.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token, configs: configArray }),
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`Failed to set config: ${response.status} - ${text}`);
    }

    const result = await response.json() as any;
    console.log('Config set:', result.configs_set);
  }
}
