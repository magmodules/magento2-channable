<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\DataObject;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class ExtraFields extends AbstractFieldArray
{

    protected $columns = [];
    protected $attributeRenderer;

    /**
     * Render block
     */
    protected function _prepareToRender()
    {
        $this->addColumn('name', [
            'label' => __('Fieldname'),
        ]);
        $this->addColumn('attribute', [
            'label' => __('Attribute'),
            'renderer' => $this->getAttributeRenderer()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Returns render of stores
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    protected function getAttributeRenderer()
    {
        if (!$this->attributeRenderer) {
            $this->attributeRenderer = $this->getLayout()->createBlock(
                '\Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer\Attributes',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->attributeRenderer;
    }

    /**
     * Prepare existing row data object
     * @param DataObject $row
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $attribute = $row->getAttribute();
        $options = [];
        if ($attribute) {
            $options['option_' . $this->getAttributeRenderer()->calcOptionHash($attribute)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
