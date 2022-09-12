<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Log;

use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepositoryInterface;
use Magmodules\Channable\Logger\DebugLogger;
use Magmodules\Channable\Logger\ErrorLogger;

/**
 * Logs repository class
 */
class Repository implements LogRepositoryInterface
{

    /**
     * @var DebugLogger
     */
    private $debugLogger;
    /**
     * @var ErrorLogger
     */
    private $errorLogger;

    /**
     * Repository constructor.
     *
     * @param DebugLogger $debugLogger
     * @param ErrorLogger $errorLogger
     */
    public function __construct(
        DebugLogger $debugLogger,
        ErrorLogger $errorLogger
    ) {
        $this->debugLogger = $debugLogger;
        $this->errorLogger = $errorLogger;
    }

    /**
     * @inheritDoc
     */
    public function addErrorLog(string $type, $data): void
    {
        $this->errorLogger->addLog($type, $data);
    }

    /**
     * @inheritDoc
     */
    public function addDebugLog(string $type, $data): void
    {
        $this->debugLogger->addLog($type, $data);
    }
}
