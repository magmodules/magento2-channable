<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magmodules\Channable\Model\System\Config\Source\Attributes as AttributesSource;

/**
 * Class Attributes
 *
 * Renderer for attribute selection in system configuration.
 */
class Attributes extends Select
{
    /**
     * @var array Cached attribute options
     */
    private $attributeOptions = [];

    /**
     * @var AttributesSource Source model for attributes
     */
    private $attributesSource;

    /**
     * Attributes constructor.
     *
     * @param Context $context
     * @param AttributesSource $attributesSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        AttributesSource $attributesSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->attributesSource = $attributesSource;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->getOptions()) {
            foreach ($this->getAttributeOptions() as $attribute) {
                $this->addOption($attribute['value'], $attribute['label']);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Retrieve all attribute options including price and product options.
     *
     * @return array
     */
    private function getAttributeOptions(): array
    {
        if (empty($this->attributeOptions)) {
            $this->attributeOptions = $this->attributesSource->toOptionArray();
            $this->attributeOptions[] = $this->getPriceAttributeOptions();
            $this->attributeOptions[] = $this->getProductOptions();
        }

        return $this->attributeOptions;
    }

    /**
     * Retrieve price-related attribute options.
     *
     * @return array
     */
    private function getPriceAttributeOptions(): array
    {
        return [
            'label' => __('Price Attributes'),
            'value' => [
                [
                    'label' => __('Price with base currency'),
                    'value' => 'rendered_price__price'
                ],
                [
                    'label' => __('Min. price with base currency'),
                    'value' => 'rendered_price__min_price'
                ],
                [
                    'label' => __('Max. price with base currency'),
                    'value' => 'rendered_price__max_price'
                ],
            ],
            'optgroup-name' => __('Price Attributes')
        ];
    }

    /**
     * Retrieve product options.
     *
     * @return array
     */
    private function getProductOptions(): array
    {
        return [
            'label' => __('Product Options'),
            'value' => [
                ['label' => __('Custom Options'), 'value' => 'custom_options']
            ],
            'optgroup-name' => __('Product Options'),
        ];
    }

    /**
     * Set the input name for the select element.
     *
     * @param string $value Input name
     * @return $this
     */
    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }
}