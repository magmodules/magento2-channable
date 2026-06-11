/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import * as fs from 'fs';
import * as path from 'path';
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
   * Get the invoice entity ID for an order by its increment ID (via REST API).
   */
  async getInvoiceId(baseURL: string, orderIncrementId: string): Promise<string> {
    const token = process.env.admin_token;
    const filter = encodeURIComponent(`searchCriteria[filterGroups][0][filters][0][field]=order_id&searchCriteria[filterGroups][0][filters][0][value]=${orderIncrementId}&searchCriteria[filterGroups][0][filters][0][conditionType]=eq&searchCriteria[pageSize]=1`);

    // First get order entity ID
    const orderUrl = `${baseURL}rest/all/V1/orders?searchCriteria[filterGroups][0][filters][0][field]=increment_id&searchCriteria[filterGroups][0][filters][0][value]=${orderIncrementId}&searchCriteria[pageSize]=1`;
    const orderRes = await fetch(orderUrl, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
    });
    const orderData = await orderRes.json() as any;
    const orderId = orderData.items?.[0]?.entity_id;
    if (!orderId) throw new Error(`Order not found: ${orderIncrementId}`);

    // Then get invoice for that order
    const invoiceUrl = `${baseURL}rest/all/V1/invoices?searchCriteria[filterGroups][0][filters][0][field]=order_id&searchCriteria[filterGroups][0][filters][0][value]=${orderId}&searchCriteria[pageSize]=1`;
    const invoiceRes = await fetch(invoiceUrl, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
    });
    const invoiceData = await invoiceRes.json() as any;
    return String(invoiceData.items?.[0]?.entity_id || '');
  }

  /**
   * Get order item info for an order by its increment ID (via REST API).
   */
  async getOrderItemInfo(baseURL: string, orderIncrementId: string): Promise<{ orderId: string; orderItemId: string; qty: number }> {
    const token = process.env.admin_token;
    const url = `${baseURL}rest/all/V1/orders?searchCriteria[filterGroups][0][filters][0][field]=increment_id&searchCriteria[filterGroups][0][filters][0][value]=${orderIncrementId}&searchCriteria[pageSize]=1`;

    const res = await fetch(url, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
    });
    const data = await res.json() as any;
    const order = data.items?.[0];
    if (!order) throw new Error(`Order not found: ${orderIncrementId}`);

    const item = order.items?.find((i: any) => i.product_type === 'simple') || order.items?.[0];
    return {
      orderId: String(order.entity_id),
      orderItemId: String(item.item_id),
      qty: item.qty_invoiced || item.qty_ordered,
    };
  }

  /**
   * Refund an invoice via the Magento REST API.
   */
  async refundInvoiceViaApi(baseURL: string, invoiceId: string, orderItemId: string, qty: number): Promise<{ status: number; body: any }> {
    const token = process.env.admin_token;
    const url = `${baseURL}rest/all/V1/invoice/${invoiceId}/refund`;

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        items: [{ order_item_id: parseInt(orderItemId, 10), qty }],
        notify: false,
        isOnline: false,
        arguments: { adjustment_positive: 0, adjustment_negative: 0, shipping_amount: 0 },
      }),
    });

    const body = await response.json();
    return { status: response.status, body };
  }

  /**
   * Check if a credit memo exists for an order by increment ID (via REST API).
   */
  async hasCreditMemo(baseURL: string, orderIncrementId: string): Promise<boolean> {
    const token = process.env.admin_token;

    // Get order entity ID first
    const orderUrl = `${baseURL}rest/all/V1/orders?searchCriteria[filterGroups][0][filters][0][field]=increment_id&searchCriteria[filterGroups][0][filters][0][value]=${orderIncrementId}&searchCriteria[pageSize]=1`;
    const orderRes = await fetch(orderUrl, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
    });
    const orderData = await orderRes.json() as any;
    const orderId = orderData.items?.[0]?.entity_id;
    if (!orderId) return false;

    // Search for credit memos on that order
    const cmUrl = `${baseURL}rest/all/V1/creditmemos?searchCriteria[filterGroups][0][filters][0][field]=order_id&searchCriteria[filterGroups][0][filters][0][value]=${orderId}&searchCriteria[pageSize]=1`;
    const cmRes = await fetch(cmUrl, {
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
    });
    const cmData = await cmRes.json() as any;
    return (cmData.total_count || 0) > 0;
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
}
