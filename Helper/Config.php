<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigModel;
use Magmodules\Channable\Logger\ChannableLogger;
use Magmodules\Channable\Helper\Source as SourceHelper;

/**
 * Class Config
 *
 * @package Magmodules\Channable\Helper
 */
class Config extends AbstractHelper
{

    const XPATH_CONVERT_RUN = 'magmodules_channable/task/convert_run';
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;
    /**
     * @var ChannableLogger
     */
    private $logger;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitConfig;

    /**
     * Config constructor.
     *
     * @param Context                   $context
     * @param ObjectManagerInterface    $objectManager
     * @param ResourceConnection        $resource
     * @param ProductMetadataInterface  $productMetadata
     * @param ReinitableConfigInterface $reinitConfig
     * @param ConfigModel               $config
     * @param ChannableLogger           $logger
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        ResourceConnection $resource,
        ProductMetadataInterface $productMetadata,
        ReinitableConfigInterface $reinitConfig,
        ConfigModel $config,
        ChannableLogger $logger
    ) {
        $this->objectManager = $objectManager;
        $this->resource = $resource;
        $this->productMetadata = $productMetadata;
        $this->config = $config;
        $this->reinitConfig = $reinitConfig;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     *
     */
    public function run()
    {
        $convert = $this->scopeConfig->getValue(self::XPATH_CONVERT_RUN);
        $magentoVersion = $this->productMetadata->getVersion();
        if (empty($convert) && version_compare($magentoVersion, '2.2.0', '>=')) {
            try {
                $this->convertSerializedDataToJson();
                $this->config->saveConfig(self::XPATH_CONVERT_RUN, 1, 'default', 0);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * Convert Serialzed Data fields to Json for Magento 2.2
     * Using Object Manager for backwards compatability
     */
    public function convertSerializedDataToJson()
    {
        $magentoVersion = $this->productMetadata->getVersion();
        if (version_compare($magentoVersion, '2.2.0', '>=')) {

            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            $connection = $this->resource
                ->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);

            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            $fieldDataConverter = $this->objectManager
                ->create(\Magento\Framework\DB\FieldDataConverterFactory::class)
                ->create(\Magento\Framework\DB\DataConverter\SerializedToJson::class);

            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            $queryModifier = $this->objectManager
                ->create(\Magento\Framework\DB\Select\QueryModifierFactory::class)
                ->create(
                    'in',
                    [
                        'values' => [
                            'path' => [
                                SourceHelper::XPATH_EXTRA_FIELDS,
                                SourceHelper::XPATH_DELIVERY_TIME,
                                SourceHelper::XPATH_FILTERS_DATA
                            ]
                        ]
                    ]
                );

            $fieldDataConverter->convert(
                $connection,
                $this->resource->getTableName('core_config_data'),
                'config_id',
                'value',
                $queryModifier
            );

            $this->reinitConfig->reinit();
            return 'Fields updated!';
        } else {
            return 'Incompatible Magento Version';
        }
    }
}
