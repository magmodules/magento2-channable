<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class ChannableHandler
 *
 * @package Magmodules\Channable\Logger
 */
class ChannableHandler extends Base
{

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
    /**
     * @var string
     */
    protected $fileName = '/var/log/channable.log';
}
