<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Selftest;

use Magmodules\Channable\Api\Selftest\RepositoryInterface;

/**
 * Selftest repository class
 */
class Repository implements RepositoryInterface
{
    /**
     * @var array
     */
    private $testList;

    /**
     * Repository constructor.
     *
     * @param array $testList
     */
    public function __construct(
        array $testList
    ) {
        $this->testList = $testList;
    }

    /**
     * @inheritDoc
     */
    public function test($output = true): array
    {
        $result = [];
        foreach ($this->testList as $data) {
            $result[] = $data->execute();
        }
        return $result;
    }
}
