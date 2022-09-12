<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config;

use Magmodules\Channable\Api\Config\RepositoryInterface;

/**
 * Config provider class
 */
class Repository extends System\OrderRepository implements RepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function getExtensionVersion(): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_VERSION);
    }

    /**
     * @inheritDoc
     */
    public function getStoreCurrencyCode(): string
    {
        try {
            return $this->getStore()->getCurrentCurrency()->getCode();
        } catch (\Exception $e) {
            return 'EUR';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getMagentoVersion(): string
    {
        return $this->metadata->getVersion();
    }

    /**
     * {@inheritDoc}
     */
    public function getMagentoEdition(): string
    {
        return $this->metadata->getEdition();
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE);
    }
}
