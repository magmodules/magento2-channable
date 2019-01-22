<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\System;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magmodules\Channable\Helper\Config as ConfigHelper;

/**
 * Class Config
 *
 * @package Magmodules\Channable\Observer\System
 */
class Config implements ObserverInterface
{

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * Tasks constructor.
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $section = $observer->getRequest()->getParam('section');
        if ($section == 'magmodules_channable') {
            $this->configHelper->run();
        }
    }
}
