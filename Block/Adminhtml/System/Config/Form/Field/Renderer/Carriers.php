<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use Magento\Shipping\Model\Config;

class Carriers extends Select
{

    /**
     * @var array
     */
    private $carriers = [
        ['value' => 'all' , 'label' => 'All']
    ];
    /**
     * @var Config
     */
    private $shippingConfig;

    /**
     * Countries constructor.
     *
     * @param Context       $context
     * @param Config $shippingConfig
     * @param array         $data
     */
    public function __construct(
        Context $context,
        Config $shippingConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->getCarriersSource() as $carriers) {
                $this->addOption($carriers['value'], $carriers['label']);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Get all countries
     *
     * @return array
     */
    private function getCarriersSource()
    {
        if (!$this->carriers) {
            foreach ($this->shippingConfig->getAllCarriers() as $carrier) {
                $this->carriers[] = [
                    'value' => $carrier->getCarrierCode(),
                    'label' => $carrier->getCarrierCode()
                ];
            }
        }

        return $this->carriers;
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
