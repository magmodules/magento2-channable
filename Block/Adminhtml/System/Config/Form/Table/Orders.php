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
use Magmodules\Channable\Api\Order\RepositoryInterface as OrderRepository;

/**
 * Order Table Block for system config
 */
class Orders extends Template implements RendererInterface
{

    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Magmodules_Channable::system/config/fieldset/table/orders.phtml';

    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
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
     * OrderStores constructor.
     *
     * @param Context $context
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        LogRepository $logRepository
    ) {
        $this->orderRepository = $orderRepository;
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
     * Returns order configuration data array for all stores
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
                    'status' => $this->configProvider->isOrderEnabled($storeId),
                    'webhook_url' => $this->configProvider->getWebhookUrl($storeId),
                    'status_url' => $this->configProvider->getStatusUrl($storeId)
                ];
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('LocalizedException', $e->getMessage());
                continue;
            }
        }
        return $configData;
    }

    /**
     * Url builder for order simulator
     *
     * @param int $storeId
     * @return string
     */
    public function getTestOrderUrl(int $storeId): string
    {
        return $this->urlBuilder->getUrl('channable/order/simulate', ['store_id' => $storeId]);
    }
}
