<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\DataObject;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class Filters
 *
 * @package Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field
 */
class Filters extends AbstractFieldArray
{

    /**
     * @var \Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer\Attributes
     */
    private $attributeRenderer;
    /**
     * @var \Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer\Conditions
     */
    private $conditionRenderer;
    /**
     * @var \Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer\ProductTypes
     */
    private $productTypeRenderer;

    /**
     * Render block.
     */
    public function _prepareToRender()
    {
        $this->addColumn('attribute', [
            'label'    => __('Attribute'),
            'renderer' => $this->getAttributeRenderer()
        ]);
        $this->addColumn('condition', [
            'label'    => __('Condition'),
            'renderer' => $this->getConditionRenderer()
        ]);
        $this->addColumn('value', [
            'label' => __('Value'),
        ]);
        $this->addColumn('product_type', [
            'label' => __('Apply To'),
            'renderer' => $this->getProductTypeRenderer()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Returns render of Attributes.
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    public function getAttributeRenderer()
    {
        if (!$this->attributeRenderer) {
            try {
                $this->attributeRenderer = $this->getLayout()->createBlock(
                    '\Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer\Attributes',
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            } catch (\Exception $e) {
                $this->attributeRenderer = [];
            }
        }

        return $this->attributeRenderer;
    }

    /**
     * Returns render of Attributes.
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    public function getConditionRenderer()
    {
        if (!$this->conditionRenderer) {
            try {
                $this->conditionRenderer = $this->getLayout()->createBlock(
                    '\Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer\Conditions',
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            } catch (\Exception $e) {
                $this->conditionRenderer = [];
            }
        }

        return $this->conditionRenderer;
    }

    /**
     * Returns render of Product Types.
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    public function getProductTypeRenderer()
    {
        if (!$this->productTypeRenderer) {
            try {
                $this->productTypeRenderer = $this->getLayout()->createBlock(
                    '\Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer\ProductTypes',
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            } catch (\Exception $e) {
                $this->productTypeRenderer = [];
            }
        }

        return $this->productTypeRenderer;
    }

    /**
     * Prepare existing row data object.
     *
     * @param DataObject $row
     */
    public function _prepareArrayRow(DataObject $row)
    {
        $options = [];
        $attribute = $row->getData('attribute');
        if ($attribute) {
            $options['option_' . $this->getAttributeRenderer()->calcOptionHash($attribute)] = 'selected="selected"';
        }
        $condition = $row->getData('condition');
        if ($condition) {
            $options['option_' . $this->getConditionRenderer()->calcOptionHash($condition)] = 'selected="selected"';
        }
        $productType = $row->getData('product_type');
        if ($condition) {
            $options['option_' . $this->getProductTypeRenderer()->calcOptionHash($productType)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
