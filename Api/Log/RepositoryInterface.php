<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Log;

/**
 * Log repository interface
 */
interface RepositoryInterface
{

    /**
     * Add record to error log
     *
     * @param string $type
     * @param mixed $data
     *
     */
    public function addErrorLog(string $type, $data);

    /**
     * Add record to debug log
     *
     * @param string $type
     * @param mixed $data
     *
     */
    public function addDebugLog(string $type, $data);

}
