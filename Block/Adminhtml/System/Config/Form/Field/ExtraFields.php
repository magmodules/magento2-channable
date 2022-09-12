<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ExtraFields
 * System config field renderer for Extra Fields (feed)
 */
class ExtraFields extends AbstractFieldArray
{

    /**
     * @var Renderer\Attributes
     */
    private $attributeRenderer;
    /**
     * @var Renderer\Actions
     */
    private $actionsRenderer;

    /**
     * Prepare to render method
     *
     * @throws LocalizedException
     */
    public function _prepareToRender()
    {
        $this->addColumn(
            'name',
            [
                'label' => __('Field name'),
            ]
        );
        $this->addColumn(
            'attribute',
            [
                'label' => __('Attribute'),
                'renderer' => $this->getAttributeRenderer()
            ]
        );
        $this->addColumn('actions',
            [
                'label' => __('Actions'),
                'renderer' => $this->getActionsRenderer()
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Retrieve attribute column renderer
     *
     * @return Renderer\Attributes
     * @throws LocalizedException
     */
    public function getAttributeRenderer(): Renderer\Attributes
    {
        if (!$this->attributeRenderer) {
            $this->attributeRenderer = $this->getLayout()->createBlock(
                Renderer\Attributes::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->attributeRenderer;
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    public function _prepareArrayRow(DataObject $row)
    {
        $options = [];
        if ($attribute = $row->getData('attribute')) {
            $options['option_' . $this->getAttributeRenderer()->calcOptionHash($attribute)] = 'selected="selected"';
        }
        if ($attribute = $row->getData('actions')) {
            $options['option_' . $this->getActionsRenderer()->calcOptionHash($attribute)] = 'selected="selected"';
        }

        $row->setData(
            'option_extra_attrs',
            $options
        );
    }

    /**
     * Retrieve actions column renderer
     *
     * @return Renderer\Actions
     * @throws LocalizedException
     */
    public function getActionsRenderer(): Renderer\Actions
    {
        if (!$this->actionsRenderer) {
            $this->actionsRenderer = $this->getLayout()->createBlock(
                Renderer\Actions::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->actionsRenderer;
    }
}
