<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;

/**
 * Setup data patch class to create token
 */
class CreateToken implements DataPatchInterface
{

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->configProvider = $configProvider;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        if (!$this->configProvider->getToken()) {
           $this->configProvider->setToken(
               $this->getRandomString()
           );
        }
        $this->moduleDataSetup->getConnection()->endSetup();
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

        return (string)$token;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
