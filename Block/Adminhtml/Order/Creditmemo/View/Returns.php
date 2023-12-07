<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\Order\Creditmemo\View;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Creditmemo;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigRepository;
use Magmodules\Channable\Service\Returns\GetByOrder;

class Returns extends Template
{
    /**
     * @var GetByOrder
     */
    private $getByOrder;
    /**
     * @var Registry
     */
    private $coreRegistry;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @param Template\Context $context
     * @param ConfigRepository $configRepository
     * @param GetByOrder $getByOrder
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ConfigRepository $configRepository,
        GetByOrder $getByOrder,
        Registry $registry,
        array $data = []
    ) {
        $this->getByOrder = $getByOrder;
        $this->configRepository = $configRepository;
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function showOnCreditmemoCreation(): bool
    {
        return $this->configRepository->showOnCreditmemoCreation($this->getStoreId());
    }

    /**
     * @return bool
     */
    public function autoUpdateReturnsOnCreditmemo(): bool
    {
        return $this->configRepository->autoUpdateReturnsOnCreditmemo($this->getStoreId());
    }

    /**
     * Retrieve creditmemo model instance
     *
     * @return Creditmemo
     */
    private function getCreditmemo(): Creditmemo
    {
        return $this->coreRegistry->registry('current_creditmemo');
    }

    /**
     * @return int
     */
    private function getStoreId(): int
    {
        return (int)$this->getCreditmemo()->getStoreId();
    }

    /**
     * @return array|null
     */
    public function checkForReturns(): ?array
    {
        $order = $this->getCreditmemo()->getOrder();
        return $this->getByOrder->execute($order);
    }
}