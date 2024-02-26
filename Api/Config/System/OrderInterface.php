<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Config\System;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Order group interface
 */
interface OrderInterface extends ReturnsInterface
{

    /** General Group */
    const XML_PATH_ORDER_ENABLE = 'magmodules_channable_marketplace/general/enable';

    /** Order Group */
    const XML_PATH_SHIPPING_METHOD = 'magmodules_channable_marketplace/order/shipping_method';
    const XML_PATH_SHIPPING_CUSTOM = 'magmodules_channable_marketplace/order/shipping_method_custom';
    const XML_PATH_SHIPPING_METHOD_FALLBACK = 'magmodules_channable_marketplace/order/shipping_method_fallback';
    const XML_PATH_RETURN_LABEL = 'magmodules_channable_marketplace/order/return_label';
    const XML_PATH_RETURN_LABEL_REGEXP = 'magmodules_channable_marketplace/order/return_label_regexp';
    const XML_PATH_IMPORT_CUSTOMER = 'magmodules_channable_marketplace/order/import_customer';
    const XML_PATH_CUSTOMER_GROUP_ID = 'magmodules_channable_marketplace/order/customers_group';
    const XML_PATH_SEPERATE_HOUSENUMBER = 'magmodules_channable_marketplace/order/seperate_housenumber';
    const XML_PATH_SEND_ORDER_EMAIL = 'magmodules_channable_marketplace/order/order_email';
    const XML_PATH_INVOICE_ORDER = 'magmodules_channable_marketplace/order/invoice_order';
    const XML_PATH_SEND_INVOICE = 'magmodules_channable_marketplace/order/invoice_order_email';
    const XML_PATH_USE_CUSTOM_STATUS = 'magmodules_channable_marketplace/order/use_custom_status';
    const XML_PATH_CUSTOM_STATUS = 'magmodules_channable_marketplace/order/custom_status';
    const XML_PATH_USE_CHANNEL_ORDERID = 'magmodules_channable_marketplace/order/channel_orderid';
    const XML_PATH_ORDERID_PREFIX = 'magmodules_channable_marketplace/order/orderid_prefix';
    const XML_PATH_ORDERID_ALPHANUMERIC = 'magmodules_channable_marketplace/order/orderid_alphanumeric';
    const XML_PATH_IMPORT_COMPANY_NAME = 'magmodules_channable_marketplace/order/import_company_name';
    const XML_PATH_IS_COMPANY_REQUIRED = 'customer/address/company_show';
    const XML_PATH_ENABLE_BACKORDERS = 'magmodules_channable_marketplace/order/backorders';
    const XML_PATH_LVB_ENABLED = 'magmodules_channable_marketplace/order/lvb';
    const XML_PATH_LVB_SKIP_STOCK = 'magmodules_channable_marketplace/order/lvb_stock';
    const XML_PATH_LVB_AUTO_SHIP = 'magmodules_channable_marketplace/order/lvb_ship';
    const XML_PATH_DEDUCT_FPT = 'magmodules_channable_marketplace/order/deduct_fpt';
    const XML_PATH_BUSINESS_ORDER = 'magmodules_channable_marketplace/order/business_order';
    const XML_PATH_TRANSACTION_FEE = 'magmodules_channable_marketplace/order/transaction_fee';
    const XML_PATH_LOG = 'magmodules_channable_marketplace/order/log';
    const XML_PATH_CARRIER_TITLE = 'carriers/channable/title';
    const XML_PATH_CARRIER_OVERWRITE_TITLE = 'carriers/channable/overwrite_title';
    const XML_PATH_CARRIER_NAME = 'carriers/channable/name';
    const XML_PATH_CARRIER_OVERWRITE_NAME = 'carriers/channable/overwrite_name';
    const XML_PATH_ENABLE_GROUPED_PRODUCTS = 'magmodules_channable_marketplace/order/import_grouped_products';
    const XML_PATH_ENABLE_BUNDLE_PRODUCTS = 'magmodules_channable_marketplace/order/import_bundle_products';

    /**
     * Enabled flag for Order Import.
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function isOrderEnabled(int $storeId = null): bool;

    /**
     * Returns shipping method code that will be forces to use on order import.
     *
     * @param null|int $storeId
     *
     * @return null|string
     */
    public function getDefaultShippingMethod(int $storeId = null): ?string;

    /**
     * Returns shipping method array used for order import.
     * Array is looped though and first available method is used.
     *
     * @param null|int $storeId
     *
     * @return array
     */
    public function getCustomShippingMethodLogic(int $storeId = null): array;

    /**
     * Returns shipping method that should be used in case of no matched methods
     * are available. If not set 'flatrate_flatrate' is returned
     *
     * @param null|int $storeId
     *
     * @return string
     */
    public function getFallbackShippingMethod(int $storeId = null): ?string;

    /**
     * Create customer on order import
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function createCustomerOnImport(int $storeId = null): bool;

    /**
     * Group customers should be added on order import
     *
     * @param null|int $storeId
     *
     * @return string
     */
    public function customerGroupForOrderImport(int $storeId = null): ?string;

    /**
     * Seperate housenumber into 'streets'. Option is used when second steet
     * is used as housenumber field.
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function seperateHousenumber(int $storeId = null): bool;

    /**
     * The number of lines in a street address is configurable via 'customer/address/street_lines'.
     * To avoid a mismatch we'll concatenate additional lines so that they fit within the configured path.
     *
     * @param int $storeId
     * @return int
     */
    public function getCustomerStreetLines(int $storeId): int;

    /**
     * Check whether tax needs to be calculated
     *
     * @param string $type
     * @param int|null $storeId
     *
     * @return bool
     */
    public function getNeedsTaxCalulcation(string $type, int $storeId = null): bool;

    /**
     * Tax Class ID used for shipping
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getTaxClassShipping(int $storeId = null): string;

    /**
     * Invoice the order after import
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function autoInvoiceOrderOnImport(int $storeId = null): bool;

    /**
     * Send invoice email to customer after order import
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function sendInvoiceEmailOnImport(int $storeId = null): bool;

    /**
     * Send invoice email to customer after order import
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function sendOrderEmailOnImport(int $storeId = null): bool;

    /**
     * Update order status after order import
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function updateOrderStatusAfterImport(int $storeId = null): bool;

    /**
     * Returns status to be set after order import
     *
     * @param null|int $storeId
     *
     * @return string
     */
    public function getOrderProcessingStatus(int $storeId = null): ?string;

    /**
     * Use Channable order as Order Increment ID instead of auto generated Increment ID
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function useChannelOrderAsOrderIncrementId(int $storeId = null): bool;

    /**
     * Add a prefix to Order Increment ID to overcome duplicate Increment IDs
     * when Use Channable Order ID as Increment ID
     *
     * @param int|null $storeId
     *
     * @return null|string
     */
    public function getOrderIdPrefix(int $storeId = null): ?string;

    /**
     * Strip out non-alphanumeric characters from channel Order ID
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function stripChannelId(int $storeId = null): bool;

    /**
     * Import Company Name on Order
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function importCompanyName(int $storeId = null): bool;

    /**
     * Check if company is a required field
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function isCompanyRequired(int $storeId = null): bool;

    /**
     * Disable stock check on order import. Default Magento logic will check if products
     * have enough stock to import order.
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function disableStockCheckOnImport(int $storeId = null): bool;

    /**
     * Accept LVB/FBB orders. These are orders that are warehoused at the channel (e.g. Bol.com).
     * For e.g. products that are shipped directly by Bol.com
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function acceptLvbOrder(int $storeId = null): bool;

    /**
     * Disable stock movement for LVB/FBB orders. These are orders that are warehoused at the channel (e.g. Bol.com).
     * For e.g. products that are shipped directly by Bol.com
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function disableStockMovementForLvbOrders(int $storeId = null): bool;

    /**
     * Create shipment for LVB/FBV orders on order import.
     * As these orders are shipped directly by the channel no handeling should be done on Magento side.
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function autoShipLvbOrders(int $storeId = null): bool;

    /**
     * Deduct fees/fixed product taxes (FPT) before import.
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function deductFptTax(int $storeId = null): bool;

    /**
     * Check if business orders enabled.
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function isBusinessOrderEnabled(int $storeId = null): bool;

    /**
     * Check if need to add transaction fee
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function isTransactionFeeEnabled(int $storeId = null): bool;

    /**
     * Log order import
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function logOrderImport(int $storeId = null): bool;

    /**
     * @param int $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getWebhookUrl(int $storeId): string;

    /**
     * @param int $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStatusUrl(int $storeId): string;

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getCarrierTitle(int $storeId = null): string;

    /**
     * Returns labels option
     * Options: \Magmodules\Channable\Model\Config\Source\ReturnLabel
     *
     * @param null|int $storeId
     * @return null|string
     */
    public function useReturnLabel(int $storeId = null): ?string;

    /**
     * Returns array of carrier_code and regex to determine is label is return label
     *
     * @param null|int $storeId
     * @return array
     */
    public function getReturnLabelRegexp(int $storeId = null): array;

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getCarrierName(int $storeId = null): string;

    /**
     * @param null|int $storeId
     * @return int
     */
    public function getEnableBackorders(int $storeId = null): int;

    /**
     * @param null|int $storeId
     * @return bool
     */
    public function getDefaultManageStock($storeId = null): bool;

    /**
     * Allow import of grouped products in orders
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function importGroupedProducts(int $storeId = null): bool;

    /**
     * Allow import of bundle products in orders
     *
     * @param null|int $storeId
     *
     * @return bool
     */
    public function importBundleProducts(int $storeId = null): bool;
}
