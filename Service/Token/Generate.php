<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Token;

use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

class Generate
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param bool $force
     * @return void
     */
    public function execute(bool $force = false): void
    {
        if ($force || !$this->configProvider->getToken()) {
            $this->configProvider->setToken(
                $this->getRandomString()
            );
        }
    }

    /**
     * @return string
     */
    private function getRandomString(): string
    {
        $token = '';
        $chars = str_split("abcdefghijklmnopqrstuvwxyz0123456789");
        for ($i = 0; $i < 64; $i++) {
            $token .= $chars[array_rand($chars)];
        }

        return $token;
    }
}