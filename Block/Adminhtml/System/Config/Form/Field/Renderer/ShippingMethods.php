<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magmodules\Channable\Model\Config\Source\ShippingMethods as ShippingMethodsSource;

class ShippingMethods extends Select
{

    private ShippingMethodsSource $shippingMethods;

    public function __construct(
        Context $context,
        ShippingMethodsSource $shippingMethods,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->shippingMethods = $shippingMethods;
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (empty($this->getOptions())) {
            foreach ($this->shippingMethods->toOptionArray() as $method) {
                $this->addOption($method['value'], $method['label']);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Sets the name for the input element.
     *
     * @param string $value
     * @return $this
     */
    public function setInputName(string $value): self
    {
        $this->setName($value);
        return $this;
    }
}
