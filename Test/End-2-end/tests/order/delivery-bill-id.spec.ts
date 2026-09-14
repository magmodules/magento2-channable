/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {expect, test} from '@playwright/test';
import ChannableApi from 'Services/ChannableApi';

const channableApi = new ChannableApi();

const PRODUCT_ID = parseInt(process.env.PRODUCT_ID || '1', 10);

/**
 * Helper: extract order increment ID from the Channable webhook response.
 */
function getOrderIncrementId(response: any): string {
  if (response.order_id) {
    return String(response.order_id);
  }
  throw new Error(`Order creation failed: ${JSON.stringify(response)}`);
}

/**
 * Helper: create a Channable order and return its increment ID.
 */
async function createOrder(baseURL: string, quantity: number = 1): Promise<string> {
  const orderData = channableApi.buildOrderData({ productId: PRODUCT_ID, quantity });
  const response = await channableApi.postOrder(baseURL, orderData);

  return getOrderIncrementId(response);
}

/**
 * Helper: return all shipment entries for an order from the shipments webhook.
 */
async function getShipmentEntries(baseURL: string, orderIncrementId: string): Promise<any[]> {
  const shipments = await channableApi.getShipments(baseURL, 1);
  expect(Array.isArray(shipments)).toBe(true);

  return shipments.filter(
    (entry: any) => entry.type === 'shipment' && String(entry.id) === orderIncrementId
  );
}

/**
 * Helper: unique delivery bill ID in the format an ERP would generate.
 */
function erpDeliveryBillId(): string {
  return `DSN-${Math.floor(Math.random() * 900000) + 100000}`;
}

test.describe('Delivery Bill ID: default (Magento shipment increment ID)', () => {
  test('Shipments webhook — delivery_bill_id defaults to the shipment increment ID', async ({ baseURL }) => {
    const incrementId = await createOrder(baseURL!);
    const { status, shipmentId } = await channableApi.shipOrder(baseURL!, incrementId, {
      trackNumber: '3SDEFAULT001',
      carrierCode: 'custom',
      title: 'DHL',
    });
    expect(status).toBe(200);

    const shipment = await channableApi.getShipment(baseURL!, shipmentId);
    const entries = await getShipmentEntries(baseURL!, incrementId);

    expect(entries).toHaveLength(1);
    expect(entries[0].fulfillment.delivery_bill_id).toBe(shipment.increment_id);
  });

  test('Shipments webhook — entry exposes shipment_id next to the order id', async ({ baseURL }) => {
    const incrementId = await createOrder(baseURL!);
    const { shipmentId } = await channableApi.shipOrder(baseURL!, incrementId, {
      trackNumber: '3SDEFAULT002',
    });

    const shipment = await channableApi.getShipment(baseURL!, shipmentId);
    const entries = await getShipmentEntries(baseURL!, incrementId);

    expect(entries).toHaveLength(1);
    expect(entries[0].id).toBe(incrementId);
    expect(entries[0].shipment_id).toBe(shipment.increment_id);
    // The delivery bill ID and the shipment increment ID are the same value by default
    expect(entries[0].fulfillment.delivery_bill_id).toBe(entries[0].shipment_id);
  });

  test('Delivery bill ID does not replace the tracking data', async ({ baseURL }) => {
    const incrementId = await createOrder(baseURL!);
    await channableApi.shipOrder(baseURL!, incrementId, {
      trackNumber: '3SDEFAULT003',
      carrierCode: 'custom',
      title: 'DHL',
    });

    const entries = await getShipmentEntries(baseURL!, incrementId);
    const fulfillment = entries[0].fulfillment;

    expect(fulfillment.tracking_code).toContain('3SDEFAULT003');
    expect(Array.isArray(fulfillment.carrier_code)).toBe(true);
    expect(typeof fulfillment.delivery_bill_id).toBe('string');
  });

  test('Shipment without tracking still reports a delivery bill ID', async ({ baseURL }) => {
    const incrementId = await createOrder(baseURL!);
    const { shipmentId } = await channableApi.shipOrder(baseURL!, incrementId);

    const shipment = await channableApi.getShipment(baseURL!, shipmentId);
    const entries = await getShipmentEntries(baseURL!, incrementId);

    expect(entries).toHaveLength(1);
    expect(entries[0].fulfillment.delivery_bill_id).toBe(shipment.increment_id);
  });
});

test.describe('Delivery Bill ID: ERP supplied value', () => {
  test('ERP value supplied on shipment creation is used', async ({ baseURL }) => {
    const deliveryBillId = erpDeliveryBillId();
    const incrementId = await createOrder(baseURL!);

    const { status } = await channableApi.shipOrder(baseURL!, incrementId, {
      deliveryBillId,
      trackNumber: '3SERP001',
    });
    expect(status).toBe(200);

    const entries = await getShipmentEntries(baseURL!, incrementId);

    expect(entries).toHaveLength(1);
    expect(entries[0].fulfillment.delivery_bill_id).toBe(deliveryBillId);
    // The ERP value overrides the increment ID, the shipment_id is unaffected
    expect(entries[0].shipment_id).not.toBe(deliveryBillId);
  });

  test('ERP value is exposed as extension attribute on the shipment', async ({ baseURL }) => {
    const deliveryBillId = erpDeliveryBillId();
    const incrementId = await createOrder(baseURL!);

    const { shipmentId } = await channableApi.shipOrder(baseURL!, incrementId, { deliveryBillId });
    const shipment = await channableApi.getShipment(baseURL!, shipmentId);

    expect(shipment.extension_attributes?.channable_delivery_bill_id).toBe(deliveryBillId);
  });

  test('ERP value can be set on an existing shipment', async ({ baseURL }) => {
    const deliveryBillId = erpDeliveryBillId();
    const incrementId = await createOrder(baseURL!);

    // Ship without a delivery bill ID first, as most shipping extensions do
    const { shipmentId } = await channableApi.shipOrder(baseURL!, incrementId, {
      trackNumber: '3SERP002',
    });
    const shipment = await channableApi.getShipment(baseURL!, shipmentId);
    expect(shipment.extension_attributes?.channable_delivery_bill_id).toBeFalsy();

    // The ERP pushes its delivery note number afterwards
    shipment.extension_attributes = {
      ...(shipment.extension_attributes || {}),
      channable_delivery_bill_id: deliveryBillId,
    };
    const { status } = await channableApi.saveShipment(baseURL!, shipment);
    expect(status).toBe(200);

    const updated = await channableApi.getShipment(baseURL!, shipmentId);
    expect(updated.extension_attributes?.channable_delivery_bill_id).toBe(deliveryBillId);

    const entries = await getShipmentEntries(baseURL!, incrementId);
    expect(entries[0].fulfillment.delivery_bill_id).toBe(deliveryBillId);
  });

  test('Empty ERP value falls back to the shipment increment ID', async ({ baseURL }) => {
    const incrementId = await createOrder(baseURL!);

    const { shipmentId } = await channableApi.shipOrder(baseURL!, incrementId, {
      deliveryBillId: '',
      trackNumber: '3SERP003',
    });

    const shipment = await channableApi.getShipment(baseURL!, shipmentId);
    const entries = await getShipmentEntries(baseURL!, incrementId);

    expect(entries[0].fulfillment.delivery_bill_id).toBe(shipment.increment_id);
  });
});

test.describe('Delivery Bill ID: order status webhook', () => {
  test('Order status returns a delivery bill ID per shipment', async ({ baseURL }) => {
    const deliveryBillId = erpDeliveryBillId();
    const incrementId = await createOrder(baseURL!);

    const { shipmentId } = await channableApi.shipOrder(baseURL!, incrementId, {
      deliveryBillId,
      trackNumber: '3SSTATUS001',
    });
    const shipment = await channableApi.getShipment(baseURL!, shipmentId);

    const statusResponse = await channableApi.getOrderStatus(baseURL!, incrementId);

    expect(statusResponse.id).toBe(incrementId);
    expect(statusResponse.fulfillment.delivery_bill_id).toBe(deliveryBillId);
    expect(statusResponse.fulfillments).toHaveLength(1);
    expect(statusResponse.fulfillments[0].shipment_id).toBe(shipment.increment_id);
    expect(statusResponse.fulfillments[0].delivery_bill_id).toBe(deliveryBillId);
  });

  test('Order without shipments has no fulfillment data', async ({ baseURL }) => {
    const incrementId = await createOrder(baseURL!);

    const statusResponse = await channableApi.getOrderStatus(baseURL!, incrementId);

    expect(statusResponse).not.toHaveProperty('fulfillment');
    expect(statusResponse).not.toHaveProperty('fulfillments');
  });
});

test.describe('Delivery Bill ID: multiple shipments', () => {
  test('Each shipment keeps its own delivery bill ID and tracking data', async ({ baseURL }) => {
    const firstBillId = erpDeliveryBillId();
    const secondBillId = erpDeliveryBillId();
    const incrementId = await createOrder(baseURL!, 2);

    const { orderItemId } = await channableApi.getOrderItemInfo(baseURL!, incrementId);

    // Ship the order in two parts, each with its own delivery note
    const first = await channableApi.shipOrder(baseURL!, incrementId, {
      deliveryBillId: firstBillId,
      trackNumber: '3SMULTI001',
      items: [{ orderItemId, qty: 1 }],
    });
    expect(first.status).toBe(200);

    const second = await channableApi.shipOrder(baseURL!, incrementId, {
      deliveryBillId: secondBillId,
      trackNumber: '3SMULTI002',
      items: [{ orderItemId, qty: 1 }],
    });
    expect(second.status).toBe(200);

    const entries = await getShipmentEntries(baseURL!, incrementId);
    expect(entries).toHaveLength(2);

    const billIds = entries.map((entry: any) => entry.fulfillment.delivery_bill_id);
    expect(billIds).toContain(firstBillId);
    expect(billIds).toContain(secondBillId);

    // Tracking data is not merged across shipments
    const firstEntry = entries.find((entry: any) => entry.fulfillment.delivery_bill_id === firstBillId);
    const secondEntry = entries.find((entry: any) => entry.fulfillment.delivery_bill_id === secondBillId);
    expect(firstEntry.fulfillment.tracking_code).toEqual(['3SMULTI001']);
    expect(secondEntry.fulfillment.tracking_code).toEqual(['3SMULTI002']);
    expect(firstEntry.shipment_id).not.toBe(secondEntry.shipment_id);
  });

  test('Order status returns one fulfillment entry per shipment', async ({ baseURL }) => {
    const firstBillId = erpDeliveryBillId();
    const secondBillId = erpDeliveryBillId();
    const incrementId = await createOrder(baseURL!, 2);

    const { orderItemId } = await channableApi.getOrderItemInfo(baseURL!, incrementId);

    await channableApi.shipOrder(baseURL!, incrementId, {
      deliveryBillId: firstBillId,
      trackNumber: '3SMULTI003',
      items: [{ orderItemId, qty: 1 }],
    });
    const second = await channableApi.shipOrder(baseURL!, incrementId, {
      deliveryBillId: secondBillId,
      trackNumber: '3SMULTI004',
      items: [{ orderItemId, qty: 1 }],
    });

    const secondShipment = await channableApi.getShipment(baseURL!, second.shipmentId);
    const statusResponse = await channableApi.getOrderStatus(baseURL!, incrementId);

    expect(statusResponse.fulfillments).toHaveLength(2);
    expect(statusResponse.fulfillments.map((f: any) => f.delivery_bill_id))
      .toEqual([firstBillId, secondBillId]);

    // The legacy fulfillment key holds the most recent shipment
    expect(statusResponse.fulfillment.delivery_bill_id).toBe(secondBillId);
    expect(statusResponse.fulfillment.shipment_id).toBe(secondShipment.increment_id);
    expect(statusResponse.fulfillment.tracking_code).toEqual(['3SMULTI004']);
  });
});
