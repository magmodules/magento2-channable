<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory;
use Magento\Sales\Model\RefundOrder;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ReturnsData;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;

/**
 * Class ProcessReturn
 */
class CreateCreditmemo
{

    /**
     * @var ReturnsRepository
     */
    private $returnsRepository;
    /**
     * @var RefundOrder
     */
    private $refundOrder;
    /**
     * @var ItemCreationFactory
     */
    private $itemCreationFactory;
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepositoryInterface;
    /**
     * @var GetSkuFromGtin
     */
    private $getSkuFromGtin;

    public function __construct(
        ReturnsRepository $returnsRepository,
        ResourceConnection $resource,
        RefundOrder $refundOrder,
        ItemCreationFactory $itemCreationFactory,
        CreditmemoRepositoryInterface $creditmemoRepositoryInterface,
        GetSkuFromGtin $getSkuFromGtin
    ) {
        $this->returnsRepository = $returnsRepository;
        $this->resource = $resource;
        $this->refundOrder = $refundOrder;
        $this->itemCreationFactory = $itemCreationFactory;
        $this->creditmemoRepositoryInterface = $creditmemoRepositoryInterface;
        $this->getSkuFromGtin = $getSkuFromGtin;
    }

    /**
     * @param ReturnsData $return
     * @param string|null $status
     * @return string
     * @throws InputException
     * @throws LocalizedException
     */
    public function execute(ReturnsData $return, ?string $status): string
    {
        $orderId = $return->getMagentoOrderId();
        if (!$orderId) {
            throw new InputException(__('Return not linked to an order.'));
        }

        $item = $return->getItem();
        $sku = $this->getSkuFromGtin->execute($item['gtin'] ?? null, (int)$return->getStoreId());
        if (!$sku) {
            throw new InputException(__('Unable to find SKU for GTIN.'));
        }

        $itemId = $this->findOrderItemId($sku, $orderId);
        if (!$itemId) {
            throw new InputException(__('Unable to locate the order Item-ID for imported return.'));
        }

        $creditmemoItem = $this->itemCreationFactory->create();
        $creditmemoItem->setQty($item['quantity'])->setOrderItemId($itemId);

        $itemIdsToRefund[] = $creditmemoItem;
        $creditmemoId = $this->refundOrder->execute($orderId, $itemIdsToRefund);

        $creditmemo = $this->creditmemoRepositoryInterface->get($creditmemoId);
        $this->updateReturn((int)$return->getEntityId(), $creditmemo, $status);

        return $creditmemo->getIncrementId();
    }

    /**
     * @param string $sku
     * @param int $orderId
     * @return ?int
     */
    private function findOrderItemId(string $sku, int $orderId): ?int
    {
        $connection = $this->resource->getConnection();

        $select = $connection->select('')
            ->from($this->resource->getTableName('sales_order_item'), 'item_id')
            ->where('sku = :sku')
            ->where('order_id = :order_id');

        $bind = [
            ':sku' => $sku,
            ':order_id' => $orderId
        ];

        $itemId = $connection->fetchOne($select, $bind);
        return $itemId ? (int)$itemId : null;
    }

    /**
     * @param int $returnId
     * @param CreditmemoInterface $creditmemo
     * @param string|null $status
     * @return void
     * @throws LocalizedException
     */
    private function updateReturn(int $returnId, CreditmemoInterface $creditmemo, ?string $status): void
    {
        /* Reload return as can be processed by plugin */
        $return = $this->returnsRepository->get($returnId);

        $return->setMagentoCreditmemoId((int)$creditmemo->getEntityId());
        $return->setMagentoCreditmemoIncrementId($creditmemo->getIncrementId());

        if ($status) {
            $return->setStatus($status);
        }

        $this->returnsRepository->save($return);
    }
}
