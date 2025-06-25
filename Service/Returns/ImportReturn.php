<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\ArrayManager;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ReturnData;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

class ImportReturn
{

    public const AUTO_PROCESS = ['complete', 'accepted'];

    private CreateCreditmemo $createCreditmemo;
    private ReturnsRepository $returnsRepository;
    private ResourceConnection $resource;
    private ArrayManager $arrayManager;
    private ConfigProvider $configProvider;
    private LogRepository $logRepository;

    public function __construct(
        CreateCreditmemo $createCreditmemo,
        ResourceConnection $resource,
        ReturnsRepository $returnsRepository,
        ArrayManager $arrayManager,
        ConfigProvider $configProvider,
        LogRepository $logRepository
    ) {
        $this->createCreditmemo = $createCreditmemo;
        $this->returnsRepository = $returnsRepository;
        $this->resource = $resource;
        $this->arrayManager = $arrayManager;
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
    }

    /**
     * Import return data
     *
     * @param array $returnData
     * @param int $storeId
     *
     * @return array
     */
    public function execute(array $returnData, int $storeId): array
    {
        $response = [];
        $item = $returnData['item'] ?? [];
        $customer = $returnData['customer'] ?? [];
        $address = $returnData['address'] ?? [];
        $orderIncrementId = $item['order_id'] ?? null;

        $return = $this->returnsRepository->create();
        $return->setStoreId($storeId)
            ->setOrderId((int)$orderIncrementId)
            ->setChannableId((int)$returnData['channable_id'])
            ->setChannelName($returnData['channel_name'])
            ->setChannelId($returnData['channel_id'])
            ->setCustomerName(trim($customer['first_name'] . ' ' . $customer['last_name']))
            ->setItem($item)
            ->setCustomer($customer)
            ->setAddress($address)
            ->setStatus($returnData['status'])
            ->setReason($item['reason'])
            ->setComment($item['comment'])
            ->setMagentoIncrementId((string)$orderIncrementId);

        if ($orderIncrementId && $entityId = $this->getOrderEntityIdByIncrementId((string)$orderIncrementId)) {
            $return->setMagentoOrderId($entityId);
        }

        if ($channelReturnId = $this->arrayManager->get('meta/channel_return_id', $returnData)) {
            $return->setChannelReturnId($channelReturnId);
        }

        if ($channelOrderId = $this->arrayManager->get('meta/channel_order_id', $returnData)) {
            $return->setChannelOrderId($channelOrderId);
        }

        if ($channelOrderIdInternal = $this->arrayManager->get('meta/channel_order_id_internal', $returnData)) {
            $return->setChannelOrderIdInternal($channelOrderIdInternal);
        }

        if ($platformOrderId = $this->arrayManager->get('meta/platform_order_id', $returnData)) {
            $return->setPlatformOrderId($platformOrderId);
        }

        try {
            $return = $this->returnsRepository->save($return);
            $response['validated'] = 'true';
            $response['return_id'] = $return->getEntityId();
        } catch (Exception $e) {
            $this->logRepository->addErrorLog('ImportReturn', $e->getMessage());
            $response['validated'] = 'false';
            $response['errors'] = $e->getMessage();
        }

        if ($this->autoProcessReturn($return, $storeId)) {
            try {
                $this->createCreditmemo->execute($return, $return->getStatus());
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('Creditmemo on ImportReturn', $e->getMessage());
            }
        }

        return $response;
    }

    private function autoProcessReturn(ReturnData $return, int $storeId): bool
    {
        return in_array($return->getStatus(), self::AUTO_PROCESS)
            && $this->configProvider->autoProcessCompeteReturns($storeId);
    }

    private function getOrderEntityIdByIncrementId(string $incrementId): ?int
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('sales_order'), ['entity_id'])
            ->where('increment_id = :increment_id');
        $bind = [':increment_id' => $incrementId];
        $result = $connection->fetchOne($select, $bind);
        return $result !== false ? (int)$result : null;
    }
}
