<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;

/**
 * Class ImportReturn
 */
class ImportReturn
{

    /**
     * @var ReturnsRepository
     */
    private $returnsRepository;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param Json               $json
     * @param ResourceConnection $resource
     * @param ReturnsRepository  $returnsRepository
     */
    public function __construct(
        Json $json,
        ResourceConnection $resource,
        ReturnsRepository $returnsRepository
    ) {
        $this->returnsRepository = $returnsRepository;
        $this->json = $json;
        $this->resource = $resource;
    }

    /**
     * @param array $returnData
     * @param int   $storeId
     *
     * @return array
     */
    public function execute(array $returnData, int $storeId): array
    {
        $response = [];
        $item = $returnData['item'];
        $customer = $returnData['customer'];
        $address = $returnData['address'];

        $data = [
            'store_id'      => $storeId,
            'order_id'      => $item['order_id'],
            'channel_name'  => $returnData['channel_name'],
            'channel_id'    => $returnData['channel_id'],
            'channable_id'  => $returnData['channable_id'],
            'customer_name' => trim($customer['first_name'] . ' ' . $customer['last_name']),
            'item'          => $this->json->serialize($item),
            'customer'      => $this->json->serialize($customer),
            'address'       => $this->json->serialize($address),
            'status'        => $returnData['status'],
            'reason'        => $item['reason'],
            'comment'       => $item['comment']
        ];

        if ($salesOrderGridData = $this->getMagentoOrder((int)$returnData['channel_id'])) {
            $data['magento_order_id'] = $salesOrderGridData['entity_id'];
            $data['magento_increment_id'] = $salesOrderGridData['increment_id'];
        }

        $returns = $this->returnsRepository->create()->addData($data);

        try {
            $returns = $this->returnsRepository->save($returns);
            $response['validated'] = 'true';
            $response['return_id'] = $returns->getId();
        } catch (Exception $e) {
            $response['validated'] = 'false';
            $response['errors'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * @param int $channableId
     *
     * @return mixed
     */
    private function getMagentoOrder(int $channableId)
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('sales_order_grid'))
            ->where('channable_id = :channable_id');
        $bind = [':channable_id' => $channableId];
        return $connection->fetchRow($select, $bind);
    }
}
