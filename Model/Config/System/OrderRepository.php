<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\System;

use Magento\CatalogInventory\Model\Configuration as CatalogInventoryConfiguration;
use Magmodules\Channable\Api\Config\System\OrderInterface;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Order provider class
 */
class OrderRepository extends BaseRepository implements OrderInterface
{

    /**
     * @inheritDoc
     */
    public function isOrderEnabled(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_ORDER_ENABLE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultShippingMethod(int $storeId = null): ?string
    {
        return $this->getStoreValue(self::XML_PATH_SHIPPING_METHOD, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomShippingMethodLogic(int $storeId = null): array
    {
        return explode(';', $this->getStoreValue(self::XML_PATH_SHIPPING_CUSTOM, $storeId));
    }

    /**
     * @inheritDoc
     */
    public function getFallbackShippingMethod(int $storeId = null): ?string
    {
        return $this->getStoreValue(self::XML_PATH_SHIPPING_METHOD_FALLBACK, $storeId) ?? 'flatrate_flatrate';
    }

    /**
     * @inheritDoc
     */
    public function createCustomerOnImport(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_IMPORT_CUSTOMER, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function customerGroupForOrderImport(int $storeId = null): ?string
    {
        return $this->getStoreValue(self::XML_PATH_CUSTOMER_GROUP_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function seperateHousenumber(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_SEPERATE_HOUSENUMBER, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomerStreetLines(int $storeId): int
    {
        return (int)$this->getStoreValue('customer/address/street_lines', (int)$storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getNeedsTaxCalulcation(string $type, int $storeId = null): bool
    {
        if ($type == 'shipping') {
            return $this->isSetFlag(TaxConfig::CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX, (int)$storeId);
        } else {
            return $this->isSetFlag(TaxConfig::CONFIG_XML_PATH_PRICE_INCLUDES_TAX, (int)$storeId);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTaxClassShipping(int $storeId = null): string
    {
        return (string)$this->getStoreValue(TaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, (int)$storeId);
    }

    /**
     * @inheritDoc
     */
    public function sendOrderEmailOnImport(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_SEND_ORDER_EMAIL, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function autoInvoiceOrderOnImport(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_INVOICE_ORDER, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function sendInvoiceEmailOnImport(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_SEND_INVOICE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function updateOrderStatusAfterImport(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_USE_CUSTOM_STATUS, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderProcessingStatus(int $storeId = null): ?string
    {
        return $this->getStoreValue(self::XML_PATH_CUSTOM_STATUS, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function useChannelOrderAsOrderIncrementId(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_USE_CHANNEL_ORDERID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderIdPrefix(int $storeId = null): ?string
    {
        return $this->isSetFlag(self::XML_PATH_ORDERID_PREFIX, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function stripChannelId(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_ORDERID_ALPHANUMERIC, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function importCompanyName(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_IMPORT_COMPANY_NAME, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function disableStockCheckOnImport(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_ENABLE_BACKORDERS, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function acceptLvbOrder(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_LVB_ENABLED, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function disableStockMovementForLvbOrders(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_LVB_SKIP_STOCK, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function autoShipLvbOrders(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_LVB_AUTO_SHIP, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function logOrderImport(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_LOG, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getWebhookUrl(int $storeId): string
    {
        return sprintf(
            '%schannable/order/hook/store/%s/code/%s',
            $this->storeManager->getStore((int)$storeId)->getBaseUrl(),
            $storeId,
            $this->getToken()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusUrl(int $storeId): string
    {
        return sprintf(
            '%schannable/order/status/code/%s',
            $this->storeManager->getStore((int)$storeId)->getBaseUrl(),
            $this->getToken()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCarrierTitle(int $storeId = null): string
    {
        if ($this->isSetFlag(self::XML_PATH_CARRIER_OVERWRITE_TITLE, $storeId)) {
            return '{{channable_channel_label}}';
        }

        return (string)$this->getStoreValue(self::XML_PATH_CARRIER_TITLE, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getCarrierName(int $storeId = null): string
    {
        if ($this->isSetFlag(self::XML_PATH_CARRIER_OVERWRITE_NAME, $storeId)) {
            return '{{shipment_method}}';
        }

        return (string)$this->getStoreValue(self::XML_PATH_CARRIER_NAME, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getEnableBackorders(int $storeId = null): int
    {
        return (int)$this->getStoreValue(self::XML_PATH_ENABLE_BACKORDERS, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultManageStock($storeId = null): bool
    {
        return $this->isSetFlag(CatalogInventoryConfiguration::XML_PATH_MANAGE_STOCK, $storeId);
    }
}
