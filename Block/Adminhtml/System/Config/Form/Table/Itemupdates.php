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
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigProvider;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Model\ResourceModel\Item\CollectionFactory as ItemCollectionFactory;

/**
 * Itemupdates Table Block for system config
 */
class Itemupdates extends Template implements RendererInterface
{

    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Magmodules_Channable::system/config/fieldset/table/itemupdates.phtml';

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
     * @var ItemCollectionFactory
     */
    private $itemCollectionFactory;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        LogRepository $logRepository,
        ItemCollectionFactory $itemCollectionFactory
    ) {
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->storeManager = $storeManager;
        $this->itemCollectionFactory = $itemCollectionFactory;
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
                    'name' => $store->getName(),
                    'is_active' => $store->getIsActive(),
                    'enabled' => $this->configProvider->isItemUpdateEnabled($storeId)
                        ? __('Enabled')->render()
                        : __('Disabled')->render(),
                    'webhook' => $this->configProvider->getItemUpdateWebhookUrl($storeId)
                        ? __('Set')->render()
                        : __('Not Set')->render(),
                    'qty' => $this->getQtyByStoreId($storeId)
                ];
            } catch (\Exception $e) {
                $this->logRepository->addErrorLog('LocalizedException', $e->getMessage());
                continue;
            }
        }
        return $configData;
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element)
    {
        $this->setData('element', $element);
        return $this->toHtml();
    }

    private function getQtyByStoreId(int $storeId): int
    {
        $items = $this->itemCollectionFactory->create()
            ->addFieldToFilter('store_id', $storeId);
        return (int)$items->getSize();
    }
}
