<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Config as ShippingConfig;

class ShippingMethods implements ArrayInterface
{

    private $shipconfig;
    private $scopeConfig;

    /**
     * ShippingMethods constructor.
     *
     * @param ShippingConfig       $shipconfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ShippingConfig $shipconfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->shipconfig = $shipconfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];
        $activeCarriers = $this->shipconfig->getActiveCarriers();
        foreach ($activeCarriers as $carrierCode => $carrierModel) {
            $options = [];
            $carrierTitle = '';
            if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                foreach ($carrierMethods as $methodCode => $method) {
                    $code = $carrierCode . '_' . $methodCode;
                    $options[] = ['value' => $code, 'label' => $method];
                }
                $carrierTitle = $this->scopeConfig->getValue('carriers/' . $carrierCode . '/title');
            }
            $methods[] = ['value' => $options, 'label' => $carrierTitle];
        }
        return $methods;
    }
}
