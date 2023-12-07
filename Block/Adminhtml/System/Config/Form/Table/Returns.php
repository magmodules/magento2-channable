<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Table;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;

/**
 * Returns Table Block for system config
 */
class Returns extends Template implements RendererInterface
{

    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Magmodules_Channable::system/config/fieldset/table/returns.phtml';

    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ConfigProvider $configProvider
     * @param LogRepository $logRepository
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        LogRepository $logRepository
    ) {
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $context->getUrlBuilder();
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element)
    {
        $this->setData('element', $element);
        return $this->toHtml();
    }

    /**
     * Returns configuration data array for all stores
     *
     * @return array
     */
    public function getStoreData(): array
    {
        $configData = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeId = (int)$store->getStoreId();
            try {
                $configData[$storeId] = [
                    'store_id' => $storeId,
                    'code' => $store->getCode(),
                    'name' => $store->getName(),
                    'is_active' => $store->getIsActive(),
                    'status' => $this->configProvider->isReturnsEnabled($storeId),
                    'webhook_url' => $this->configProvider->getReturnsWebhookUrl($storeId),
                ];
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('LocalizedException', $e->getMessage());
            }
        }
        return $configData;
    }

    /**
     * Url builder for returns simulator
     *
     * @param int $storeId
     * @return string
     */
    public function getTestReturnsUrl(int $storeId): string
    {
        return $this->urlBuilder->getUrl(
            'channable/returns/simulate',
            [
                'store_id' => $storeId
            ]
        );
    }

    /**
     * Url builder for returns simulator with order import
     *
     * @param int $storeId
     * @return string
     */
    public function getTestReturnsWithOrderUrl(int $storeId): string
    {
        return $this->urlBuilder->getUrl(
            'channable/returns/simulate',
            [
                'store_id' => $storeId,
                'import_order' => true
            ]
        );
    }
}
