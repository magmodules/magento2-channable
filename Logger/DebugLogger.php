<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Logger;

use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;

/**
 * DebugLogger logger class
 */
class DebugLogger extends Logger
{

    /**
     * @var Json
     */
    private $json;

    /**
     * DebugLogger constructor.
     *
     * @param Json   $json
     * @param string $name
     * @param array  $handlers
     * @param array  $processors
     */
    public function __construct(
        Json $json,
        string $name,
        array $handlers = [],
        array $processors = []
    ) {
        $this->json = $json;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Add debug data to Channable log
     *
     * @param string $type
     * @param mixed  $data
     */
    public function addLog(string $type, $data): void
    {
        if (is_array($data) || is_object($data)) {
            $this->addRecord(static::INFO, $type . ': ' . $this->json->serialize($data));
        } else {
            $this->addRecord(static::INFO, $type . ': ' . $data);
        }
    }
}
