<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Api;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magmodules\Channable\Api\Returns\SearchInterface;
use Magmodules\Channable\Api\Returns\SearchResultInterface;
use Magmodules\Channable\Model\Returns\CollectionFactory;

class ReturnsSearch implements SearchInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $items = [];
        foreach ($collection as $model) {
            $items[] = new ReturnItem($model->getData());
        }

        $result = new SearchResult();
        $result->setItems($items);
        $result->setTotalCount($collection->getSize());
        $result->setSearchCriteria($searchCriteria);

        return $result;
    }
}
