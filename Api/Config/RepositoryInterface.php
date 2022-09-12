<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Api\Config;

/**
 * Config repository interface
 */
interface RepositoryInterface extends System\OrderInterface
{

    /** Extension code */
    const EXTENSION_CODE = 'Magmodules_Channable';

    /** General Group */
    const XML_PATH_EXTENSION_VERSION = 'magmodules_channable/general/version';
    const XML_PATH_ENABLE = 'magmodules_channable/general/enable';

    /**
     * Returns current version of module
     *
     * @return string
     */
    public function getExtensionVersion(): string;

    /**
     * Get Magento Product version
     *
     * @return string
     */
    public function getMagentoVersion(): string;

    /**
     * Get Magento Product edition
     *
     * @return string
     */
    public function getMagentoEdition(): string;

    /**
     * Module enable flag
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Returns saved module token
     *
     * @return ?string
     */
    public function getToken(): ?string;

    /**
     * Set module token
     *
     * @param string|null $token
     *
     * @return mixed
     */
    public function setToken(?string $token);

    /**
     * Retrieve application store currenct code
     *
     * @return string
     */
    public function getStoreCurrencyCode(): string;
}
