<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Selftest;

/**
 * Selftest repository interface
 */
interface RepositoryInterface
{

    /**
     * Test everything
     *
     * @return array
     */
    public function test(): array;
}
