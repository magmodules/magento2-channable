<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\BlockInterface;

/**
 * Represents a table for properties in the admin configuration
 */
class ReturnLabel extends AbstractFieldArray
{

    /**
     * @var Renderer\Carriers
     */
    private $carriersRenderer;

    /**
     * Render block
     */
    public function _prepareToRender()
    {
        $this->addColumn('carrier_code', [
            'label' => __('Carrier Code'),
            'renderer' => $this->getCarrierRenderer()
        ]);
        $this->addColumn('title_regexp', [
            'label' => __('Title should contain'),
            'class' => 'input-select required-entry'
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Returns render of stores
     *
     * @return BlockInterface
     */
    public function getCarrierRenderer()
    {
        if (!$this->carriersRenderer) {
            try {
                $this->carriersRenderer = $this->getLayout()->createBlock(
                    Renderer\Carriers::class,
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            } catch (\Exception $e) {
                $this->carriersRenderer = [];
            }
        }

        return $this->carriersRenderer;
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     */
    public function _prepareArrayRow(DataObject $row)
    {
        $attribute = $row->getData('carrier_code');
        $options = [];
        if ($attribute) {
            $options['option_' . $this->getCarrierRenderer()->calcOptionHash($attribute)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
