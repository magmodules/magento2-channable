<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\Item;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magmodules\Channable\Model\ItemFactory;
use Magmodules\Channable\Model\ResourceModel\Item as ItemResource;

class InlineEdit extends Action
{
    protected JsonFactory $jsonFactory;
    protected ItemFactory $itemFactory;
    protected ItemResource $itemResource;

    public const ADMIN_RESOURCE = 'Magmodules_Channable::general_item';

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ItemFactory $itemFactory,
        ItemResource $itemResource
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->itemFactory = $itemFactory;
        $this->itemResource = $itemResource;
    }

    public function execute(): ResultInterface
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        foreach (array_keys($postItems) as $itemId) {
            $item = $this->itemFactory->create();
            try {
                $this->itemResource->load($item, $itemId);
                if (!$item->getId()) {
                    throw new NoSuchEntityException(__('Item with id "%1" does not exist.', $itemId));
                }

                $allowedFields = ['exclude_for_update', 'needs_update', 'is_in_stock'];
                $filteredData = array_intersect_key($postItems[$itemId], array_flip($allowedFields));
                $item->setData(array_merge($item->getData(), $filteredData));
                $this->itemResource->save($item);
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithItemId(
                    $item,
                    __($e->getMessage())
                );
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    protected function getErrorWithItemId($item, $errorText): string
    {
        return '[Item ID: ' . $item->getId() . '] ' . $errorText;
    }
}
