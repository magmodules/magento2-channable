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
 * Class MainImage
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class MainImage implements ArrayInterface
{

    /**
     * Options array
     *
     * @var array
     */
    public $options = null;
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
            $options[] = $this->getPositionSource();
            $options[] = $this->getMediaImageTypesSource();
            $this->options = $options;
        }

        return $this->options;
    }

    /**
     * @return array
     */
    public function getMediaImageTypesSource()
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
        usort($imageSource, function ($a, $b) {
            return strcmp($a["label"], $b["label"]);
        });

        return ['label' => __('Media Image Types'), 'value' => $imageSource, 'optgroup-name' => __('image-types')];
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
    public function getPositionSource()
    {
        $imageSource = [];
        $imageSource[] = ['value' => '', 'label' => __('First Image (default)')];
        $imageSource[] = ['value' => 'last', 'label' => __('Last Image')];
        return ['label' => __('By position'), 'value' => $imageSource, 'optgroup-name' => __('position')];
    }
}
