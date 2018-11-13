<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magmodules\Channable\Model\ReturnsFactory;
use Magmodules\Channable\Helper\Returns as ReturnsHelper;

class Returns extends AbstractModel
{

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var ReturnsFactory
     */
    private $returnsFactory;
    /**
     * @var ReturnsHelper
     */
    private $returnsHelper;

    /**
     * Returns constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ReturnsFactory           $returnsFactory
     * @param ReturnsHelper            $returnsHelper
     * @param Context                  $context
     * @param Registry                 $registry
     * @param AbstractResource|null    $resource
     * @param AbstractDb|null          $resourceCollection
     * @param array                    $data
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderFactory $orderFactory,
        ReturnsFactory $returnsFactory,
        ReturnsHelper $returnsHelper,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->returnsFactory = $returnsFactory;
        $this->returnsHelper = $returnsHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @param $returnData
     * @param $storeId
     *
     * @return array
     */
    public function importReturn($returnData, $storeId)
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
            'item'          => json_encode($item),
            'customer'      => json_encode($customer),
            'address'       => json_encode($address),
            'status'        => $returnData['status'],
            'reason'        => $item['reason'],
            'comment'       => $item['comment']
        ];

        $order = $this->orderFactory->create()->load($item['order_id'], 'channable_id');
        if ($order->getId() > 0) {
            $data['magento_order_id'] = $order->getId();
            $data['magento_increment_id'] = $order->getIncrementId();
        }

        $returns = $this->returnsFactory->create()->addData($data);

        try {
            $returns = $returns->save();
            $response['validated'] = 'true';
            $response['return_id'] = $returns->getId();
        } catch (\Exception $e) {
            $response['validated'] = 'false';
            $response['errors'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function processReturn($params)
    {
        $result = [];

        if (empty($params['id'])) {
            $result['status'] = 'error';
            $result['msg'] = __('Id missing');
            return $result;
        }

        if (empty($params['type'])) {
            $result['status'] = 'error';
            $result['msg'] = __('Type missing');
            return $result;
        }

        if ($params['type'] == 'delete') {
            return $this->deleteReturn($params);
        }

        $return = $this->returnsFactory->create()->load($params['id']);

        if ($return->getId() < 1) {
            $result['status'] = 'error';
            $result['msg'] = __('Return with id %1 not found', $params['id']);
            return $result;
        }

        try {
            $return->setStatus($params['type'])->save();
            $result['status'] = 'success';
            $result['msg'] = __('Return processed, new status: %1', $params['type']);
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function deleteReturn($params)
    {
        $result = [];

        if (empty($params['id'])) {
            $result['status'] = 'error';
            $result['msg'] = __('Id missing');
            return $result;
        }

        $return = $this->returnsFactory->create()->load($params['id']);
        if ($return->getId() < 1) {
            $result['status'] = 'error';
            $result['msg'] = __('Return with id %1 not found', $params['id']);
            return $result;
        }

        try {
            $return->delete();
            $result['status'] = 'success';
            $result['msg'] = __('Return deleted');
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getReturnStatus($id)
    {
        $result = [];
        $return = $this->returnsFactory->create()->load($id);

        if ($return->getId() < 1) {
            $result['validated'] = 'false';
            $result['errors'] = __('Return with id %1 not found', $id);
            return $result;
        }

        $result['validated'] = 'true';
        $result['return_id'] = $return->getId();
        $result['status'] = $return->getStatus();
        return $result;
    }

    /**
     *
     */
    public function _construct()
    {
        $this->_init('Magmodules\Channable\Model\ResourceModel\Returns');
    }
}
