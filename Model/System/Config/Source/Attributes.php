<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Attributes implements ArrayInterface
{

    protected $attributeRepository;
    protected $searchCriteriaBuilder;

    /**
     * Attributes constructor.
     * @param Repository $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Repository $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = [];
        $attributes[] = ['value' => '', 'label' => __('None / Do not use')];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
        foreach ($this->attributeRepository->getList($searchCriteria)->getItems() as $attribute) {
            if ($attribute->getIsVisible()) {
                $attributes[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => str_replace("'", '', $attribute->getFrontendLabel())
                ];
            }
        }
        
        return $attributes;
    }
}
