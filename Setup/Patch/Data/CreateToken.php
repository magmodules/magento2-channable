<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magmodules\Channable\Service\Token\Generate;

/**
 * Setup data patch class to create token
 */
class CreateToken implements DataPatchInterface
{

    /**
     * @var Generate
     */
    private $generateToken;
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param Generate $generateToken
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        Generate $generateToken,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->generateToken = $generateToken;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->generateToken->execute();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
