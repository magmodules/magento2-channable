<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Class ImageSource
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class ImageSource implements ArrayInterface
{

    /**
     * @var array
     */
    public $options;
    /**
     * @var Repository
     */
    private $attributeRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Attributes constructor.
     *
     * @param Repository            $attributeRepository
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
        if (!$this->options) {
            $options[] = $this->getMediaImageArray();
            $options[] = $this->getMultipleImages();
            $this->options = $options;
        }

        return $this->options;
    }

    /**
     * @return array
     */
    public function getMediaImageArray()
    {
        $imageSource = [];

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('frontend_input', 'media_image')->create();
        /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
        foreach ($this->attributeRepository->getList($searchCriteria)->getItems() as $attribute) {
            if ($attribute->getIsVisible()) {
                $imageSource[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $this->getLabel($attribute)
                ];
            }
        }

        return ['label' => __('Single Source'), 'value' => $imageSource, 'optgroup-name' => __('single-source')];
    }

    /**
     * @param $attribute
     *
     * @return mixed
     */
    public function getLabel($attribute)
    {
        return str_replace("'", '', $attribute->getFrontendLabel());
    }

    /**
     * @return array
     */
    public function getMultipleImages()
    {
        $imageSource[] = ['value' => 'all', 'label' => __('All Images')];
        return ['label' => __('Other Options'), 'value' => $imageSource, 'optgroup-name' => __('other-options')];
    }
}
