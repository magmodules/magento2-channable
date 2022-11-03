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
 * Class Attributes
 *
 * @package Magmodules\Channable\Model\System\Config\Source
 */
class Attributes implements ArrayInterface
{

    /**
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
            $options[] = ['value' => '', 'label' => __('None / Do not use')];
            $options[] = $this->getAttributesArray();
            $relations = $this->getRelations();
            if (!empty($relations)) {
                $options[] = $relations;
            }
            $this->options = $options;
        }

        return $this->options;
    }

    /**
     * @return array
     */
    public function getAttributesArray()
    {
        $attributes = [];
        $attributes[] = ['value' => 'attribute_set_id', 'label' => __('Attribute Set ID')];
        $attributes[] = ['value' => 'attribute_set_name', 'label' => __('Attribute Set Name')];
        $attributes[] = ['value' => 'type_id', 'label' => __('Product Type')];
        $attributes[] = ['value' => 'entity_id', 'label' => __('Product Id')];

        $exclude = $this->getNonAvailableAttributes();
        $searchCriteria = $this->searchCriteriaBuilder->create();
        /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
        foreach ($this->attributeRepository->getList($searchCriteria)->getItems() as $attribute) {
            if ($attribute->getIsVisible() && !in_array($attribute->getAttributeCode(), $exclude)) {
                $attributes[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $this->getLabel($attribute)
                ];
            }
        }

        usort($attributes, function ($a, $b) {
            return strcmp($a["label"], $b["label"]);
        });

        return ['label' => __('Attributes'), 'value' => $attributes, 'optgroup-name' => __('Attributes')];
    }

    /**
     * @return array
     */
    public function getNonAvailableAttributes()
    {
        return ['categories', 'gallery'];
    }

    /**
     * @param $attribute
     *
     * @return mixed
     */
    public function getLabel($attribute)
    {
        return str_replace("'", '', $attribute->getFrontendLabel() ?? '');
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        $optionArray = [];
        $optionArray[] = ['label' => __('Related Skus'), 'value' => 'related_skus'];
        $optionArray[] = ['label' => __('Upsell Skus'), 'value' => 'upsell_skus'];
        $optionArray[] = ['label' => __('Crosssell Skus'), 'value' => 'crosssell_skus'];

        return [
            'label'         => __('Product Relations'),
            'value'         => $optionArray,
            'optgroup-name' => __('Product Relations')
        ];
    }
}
