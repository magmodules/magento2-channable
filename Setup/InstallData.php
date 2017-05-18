<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Config\Model\ResourceModel\Config;

class InstallData implements InstallDataInterface
{

    protected $config;
    private $eavSetupFactory;

    /**
     * InstallData constructor.
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param Config          $config
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $config
    ) {
        $this->config = $config;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            'channable_cat_disable_export',
            [
                'type'         => 'int',
                'label'        => 'Disable Category from export',
                'input'        => 'select',
                'source'       => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global'       => 1,
                'visible'      => true,
                'required'     => false,
                'user_defined' => false,
                'sort_order'   => 100,
                'default'      => 0
            ]
        );

        $token = '';
        $chars = str_split("abcdefghijklmnopqrstuvwxyz0123456789");
        for ($i = 0; $i < 64; $i++) {
            $token .= $chars[array_rand($chars)];
        }
        $key = 'magmodules_channable/general/token';
        $this->config->saveConfig($key, $token, 'default', 0);
    }
}
