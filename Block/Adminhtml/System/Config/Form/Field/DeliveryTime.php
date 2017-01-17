<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\DataObject;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class DeliveryTime extends AbstractFieldArray
{

    protected $columns = [];
    protected $countryRenderer;

    /**
     * Render block
     */
    protected function _prepareToRender()
    {
        $this->addColumn('code', [
            'label' => __('Country'),
            'renderer' => $this->getCountryRenderer()
        ]);
        $this->addColumn('in_stock', [
            'label' => __('In Stock'),
        ]);
        $this->addColumn('out_of_stock', [
            'label' => __('Out of Stock'),
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Returns render of countries
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    protected function getCountryRenderer()
    {
        if (!$this->countryRenderer) {
            $this->countryRenderer = $this->getLayout()->createBlock(
                '\Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer\Countries',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->countryRenderer;
    }

    /**
     * Prepare existing row data object
     * @param DataObject $row
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $attribute = $row->getCode();
        $options = [];
        if ($attribute) {
            $options['option_' . $this->getCountryRenderer()->calcOptionHash($attribute)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
