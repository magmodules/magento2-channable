<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use Magmodules\Channable\Model\System\Config\Source\Attributes as AttributesSource;

/**
 * Class Attributes
 *
 * @package Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer
 */
class Attributes extends Select
{

    /**
     * @var array
     */
    private $attribute = [];
    /**
     * @var AttributesSource
     */
    private $attributes;

    /**
     * Attributes constructor.
     *
     * @param Context          $context
     * @param AttributesSource $attributes
     * @param array            $data
     */
    public function __construct(
        Context $context,
        AttributesSource $attributes,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->attributes = $attributes;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->getAttributeSource() as $attribute) {
                $this->addOption($attribute['value'], $attribute['label']);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function getAttributeSource()
    {
        if (!$this->attribute) {
            $this->attribute = $this->attributes->toOptionArray();
        }

        return $this->attribute;
    }

    /**
     * Sets name for input element
     *
     * @param $value
     *
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
