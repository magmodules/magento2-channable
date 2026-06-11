/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import * as fs from 'fs';
import * as path from 'path';
import { execSync } from 'child_process';
import BaseApi from './BaseApi';

export default class ChannableApi extends BaseApi {
  private orderTemplate: any;

  constructor() {
    super();
    this.orderTemplate = JSON.parse(
      fs.readFileSync(path.join(__dirname, '../fixtures/order-template.json'), 'utf-8')
    );
  }

  /**
   * POST an order to the Channable webhook endpoint.
   */
  async postOrder(baseURL: string, orderData: any, storeId: number = 1): Promise<any> {
    const token = process.env.CHANNABLE_TOKEN;
    const url = `${baseURL}channable/order/hook?store=${storeId}&code=${token}`;

    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(orderData),
    });

    return response.json();
  }

  /**
   * GET order status from the Channable webhook endpoint.
   */
  async getOrderStatus(baseURL: string, incrementId: string): Promise<any> {
    const token = process.env.CHANNABLE_TOKEN;
    const url = `${baseURL}channable/order/status?code=${token}&id=${incrementId}`;

    const response = await fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });

    return response.json();
  }

  /**
   * GET recent shipments from the Channable webhook endpoint.
   */
  async getShipments(baseURL: string, timespan: number = 1): Promise<any> {
    const token = process.env.CHANNABLE_TOKEN;
    const url = `${baseURL}channable/order/shipments?code=${token}&timespan=${timespan}`;

    const response = await fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });

    return response.json();
  }

  /**
   * GET a customer by email via the Magento REST API.
   */
  async getCustomerByEmail(baseURL: string, email: string): Promise<any> {
    const token = process.env.admin_token;
    const searchUrl = `${baseURL}rest/V1/customers/search?` +
      `searchCriteria[filterGroups][0][filters][0][field]=email&` +
      `searchCriteria[filterGroups][0][filters][0][value]=${encodeURIComponent(email)}`;

    const response = await fetch(searchUrl, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    const result = await response.json();
    if (result.items && result.items.length > 0) {
      return result.items[0];
    }

    throw new Error(`Customer not found for email: ${email}`);
  }

  /**
   * Build order data by merging overrides into the base template.
   */
  buildOrderData(overrides: {
    country?: string;
    state?: string;
    stateCode?: string;
    price?: number;
    priceTax?: number;
    productId?: number;
    quantity?: number;
    currency?: string;
    channableId?: string;
    channelId?: string;
    orderStatus?: string;
    businessOrder?: boolean;
    shipping?: number;
    discount?: number;
    companyName?: string;
    channelName?: string;
    shipmentMethod?: string;
    email?: string;
  } = {}): any {
    const channableId = overrides.channableId || String(Math.floor(Math.random() * 900000) + 100000);
    const country = overrides.country || 'NL';
    const price = overrides.price ?? this.orderTemplate.products[0].price;
    const priceTax = overrides.priceTax;
    const productId = overrides.productId;
    const quantity = overrides.quantity ?? 1;
    const currency = overrides.currency ?? 'EUR';

    const data = JSON.parse(JSON.stringify(this.orderTemplate));

    data.channable_id = channableId;
    data.channel_id = overrides.channelId ?? ('E2E-' + channableId);

    data.billing.country_code = country;
    data.shipping.country_code = country;

    const zipCodes: Record<string, string> = {
      'NL': '1000 AA',
      'DE': '10115',
      'AT': '1010',
      'BE': '1000',
      'FR': '75001',
      'LV': 'LV-3101',
      'PL': '00-001',
    };
    if (zipCodes[country]) {
      data.billing.zip_code = zipCodes[country];
      data.shipping.zip_code = zipCodes[country];
    }

    if (overrides.state !== undefined) {
      data.billing.state = overrides.state;
      data.shipping.state = overrides.state;
    }
    if (overrides.stateCode !== undefined) {
      data.billing.state_code = overrides.stateCode;
      data.shipping.state_code = overrides.stateCode;
    }

    if (productId) {
      data.products[0].id = productId;
    }
    data.products[0].quantity = quantity;
    data.products[0].price = price;

    if (priceTax !== undefined) {
      data.products[0].price_tax = priceTax;
    }

    if (overrides.orderStatus) {
      data.order_status = overrides.orderStatus;
    }

    if (overrides.businessOrder !== undefined) {
      data.customer.business_order = overrides.businessOrder;
    }

    if (overrides.companyName) {
      data.customer.company = overrides.companyName;
      data.billing.company = overrides.companyName;
      data.shipping.company = overrides.companyName;
    }

    if (overrides.email) {
      data.customer.email = overrides.email;
      data.billing.email = overrides.email;
      data.shipping.email = overrides.email;
    }

    if (overrides.channelName) {
      data.channel_name = overrides.channelName;
    }

    if (overrides.shipmentMethod) {
      data.shipment_method = overrides.shipmentMethod;
    }

    const shipping = overrides.shipping ?? 0;
    const discount = overrides.discount ?? 0;
    data.price.currency = currency;
    data.price.shipping = shipping;
    data.price.discount = discount;
    data.price.subtotal = price * quantity;
    data.price.total = (price * quantity) + shipping - discount;
    data.price.commission = Math.round(price * quantity * 0.10 * 100) / 100;
    data.products[0].commission = data.price.commission;

    return data;
  }

  /**
   * Ensure a second store view exists for multi-store tests.
   * Uses Magento bootstrap to create the store properly (triggers all observers/indexers).
   * Returns the store ID, or null if no container is available.
   */
  ensureSecondStoreView(storeCode: string): number | null {
    if (!this.container) return null;

    const phpScript = [
      '<?php',
      'error_reporting(0);',
      "require 'app/bootstrap.php';",
      '$bootstrap = \\Magento\\Framework\\App\\Bootstrap::create(BP, $_SERVER);',
      '$om = $bootstrap->getObjectManager();',
      '$repo = $om->get(\\Magento\\Store\\Api\\StoreRepositoryInterface::class);',
      'try {',
      `    $store = $repo->get('${storeCode}');`,
      '} catch (\\Magento\\Framework\\Exception\\NoSuchEntityException $e) {',
      '    $store = $om->get(\\Magento\\Store\\Model\\StoreFactory::class)->create();',
      `    $store->setCode('${storeCode}');`,
      "    $store->setName('Second Store');",
      '    $store->setWebsiteId(1);',
      '    $store->setGroupId(1);',
      '    $store->setIsActive(1);',
      '    $store->setSortOrder(10);',
      '    $store->save();',
      '}',
      'echo $store->getId();',
    ].join('\n');

    const tmpFile = '/tmp/e2e-create-store.php';
    execSync(`docker exec -i ${this.container} tee ${tmpFile}`, {
      input: phpScript,
      stdio: ['pipe', 'pipe', 'pipe'],
    });

    const result = execSync(
      `docker exec ${this.container} php ${tmpFile}`,
      { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'], timeout: 120000 }
    ).trim();

    const storeId = parseInt(result, 10);

    execSync(`docker exec ${this.container} bin/magento config:set --scope=stores --scope-code=${storeCode} magmodules_channable/general/enable 1`, { stdio: 'pipe' });
    execSync(`docker exec ${this.container} bin/magento config:set --scope=stores --scope-code=${storeCode} magmodules_channable_marketplace/general/enable 1`, { stdio: 'pipe' });
    execSync(`docker exec ${this.container} bin/magento indexer:reindex`, { stdio: 'pipe', timeout: 120000 });
    this.flushAllCaches();
    console.log(`Second store view '${storeCode}' ensured (ID: ${storeId}).`);

    return storeId;
  }

  /**
   * Ensure a currency rate exists in Magento (needed for multi-currency orders).
   */
  async setupCurrencyRate(from: string, to: string, rate: number): Promise<void> {
    if (!this.db) {
      console.log('No MAGENTO_CONTAINER set, skipping currency rate setup');
      return;
    }

    try {
      this.db.query(`
        $stmt = $pdo->prepare("INSERT INTO directory_currency_rate (currency_from, currency_to, rate) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rate = ?");
        $stmt->execute(['${from}', '${to}', ${rate}, ${rate}]);
        echo "OK";
      `);
      console.log(`Currency rate set: ${from} → ${to} = ${rate}`);
    } catch {
      console.log(`Warning: Could not set currency rate ${from} → ${to}`);
    }

    this.flushAllCaches();
  }

  /**
   * Create a product via the Magento REST API.
   */
  async createProduct(baseURL: string, payload: any): Promise<any> {
    const token = process.env.admin_token;
    const url = `${baseURL}rest/all/V1/products`;

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({ product: payload }),
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`Failed to create product ${payload.sku}: ${response.status} - ${text}`);
    }

    return response.json();
  }

  /**
   * Delete a product by SKU via the Magento REST API.
   */
  async deleteProduct(baseURL: string, sku: string): Promise<void> {
    const token = process.env.admin_token;
    const url = `${baseURL}rest/all/V1/products/${encodeURIComponent(sku)}`;

    const response = await fetch(url, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${token}` },
    });

    if (!response.ok && response.status !== 404) {
      const text = await response.text();
      throw new Error(`Failed to delete product ${sku}: ${response.status} - ${text}`);
    }
  }

  /**
   * Set stock status and quantity for a product by SKU.
   */
  async setStockStatus(baseURL: string, sku: string, qty: number, inStock: boolean): Promise<void> {
    const token = process.env.admin_token;
    const url = `${baseURL}rest/all/V1/products/${encodeURIComponent(sku)}`;

    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        product: {
          sku,
          extension_attributes: {
            stock_item: {
              qty,
              is_in_stock: inStock,
            },
          },
        },
      }),
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`Failed to set stock for ${sku}: ${response.status} - ${text}`);
    }
  }

  /**
   * Reindex catalog prices via docker exec.
   */
  reindexPrices(): void {
    if (!this.container) {
      throw new Error('MAGENTO_CONTAINER env var is required for reindexing');
    }

    execSync(
      `docker exec ${this.container} bin/magento indexer:reindex catalog_product_price cataloginventory_stock`,
      { stdio: 'pipe', timeout: 120000 }
    );
    console.log('Price index rebuilt.');
  }

  /**
   * Full reindex of all indexers + cache flush via docker exec.
   */
  reindexAll(): void {
    if (!this.container) {
      throw new Error('MAGENTO_CONTAINER env var is required for reindexing');
    }

    execSync(
      `docker exec ${this.container} bin/magento indexer:reindex`,
      { stdio: 'pipe', timeout: 120000 }
    );
    this.flushAllCaches();
    console.log('Full reindex + cache flush done.');
  }

  /**
   * Fetch a single product from the Channable feed by product ID.
   */
  async getFeedProduct(baseURL: string, pid: number, storeId: number = 1): Promise<any> {
    const token = process.env.CHANNABLE_TOKEN || 'e2e-test-token';
    const url = `${baseURL}channable/feed/json?id=${storeId}&token=${token}&pid=${pid}`;

    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' },
    });

    if (!response.ok) {
      throw new Error(`Feed request failed: ${response.status}`);
    }

    const body = await response.json() as any;

    // ?pid= returns {"products": {"product": {...}, "feed": {...}}}
    // ?page= returns {"products": [...]}
    const productsNode = body.products;

    if (!productsNode) {
      throw new Error(`Product ${pid} not found in feed (no products key)`);
    }

    // Single product via ?pid= — return the processed feed object
    if (productsNode.feed) {
      return productsNode.feed;
    }

    // Array from ?page=
    if (Array.isArray(productsNode) && productsNode.length > 0) {
      return productsNode[0];
    }

    throw new Error(`Product ${pid} not found in feed`);
  }

  /**
   * Get the Magento entity ID for a product by SKU (via REST API).
   */
  async getProductId(baseURL: string, sku: string): Promise<number> {
    const token = process.env.admin_token;
    const url = `${baseURL}rest/all/V1/products/${encodeURIComponent(sku)}`;

    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Product ${sku} not found: ${response.status}`);
    }

    const data = await response.json() as any;
    return data.id;
  }
}
