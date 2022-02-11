<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Setup data patch class to change config path
 */
class ConfigPath implements DataPatchInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var ValueInterface
     */
    private $configReader;
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * OrderAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ValueInterface $configReader
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ValueInterface $configReader,
        WriterInterface $configWriter,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
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
        $this->changeConfigPaths();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Change config paths for fields due to changes in config options.
     */
    public function changeConfigPaths()
    {
        $collection = $this->configReader->getCollection()
            ->addFieldToFilter("path", "magmodules_channable/advanced/parent_atts");

        foreach ($collection as $config) {
            /** @var Value $config */
            $this->configWriter->save(
                "magmodules_channable/types/configurable_parent_atts",
                $config->getValue(),
                $config->getScope(),
                $config->getScopeId()
            );
            $this->configWriter->delete(
                "magmodules_channable/advanced/parent_atts",
                $config->getScope(),
                $config->getScopeId()
            );
        }

        $collection = $this->configReader->getCollection()
            ->addFieldToFilter("path", "magmodules_channable/advanced/relations");

        foreach ($collection as $config) {
            /** @var Value $config */
            if ($config->getValue() == 1) {
                $this->configWriter->save(
                    "magmodules_channable/types/configurable",
                    'simple',
                    $config->getScope(),
                    $config->getScopeId()
                );
            }
            $this->configWriter->delete(
                "magmodules_channable/advanced/relations",
                $config->getScope(),
                $config->getScopeId()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
