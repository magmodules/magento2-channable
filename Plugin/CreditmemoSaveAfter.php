<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigRepository;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;
use Magmodules\Channable\Api\Returns\Data\DataInterface as ReturnData;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnRepository;
use Magmodules\Channable\Service\Returns\GetByOrder;

class CreditmemoSaveAfter
{

    /**
     * @var GetByOrder
     */
    private $getByOrder;
    /**
     * @var ReturnRepository
     */
    private $returnRepository;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * CreditmemoSaveAfter constructor.
     * @param GetByOrder $getByOrder
     * @param ReturnRepository $returnRepository
     * @param LogRepository $logRepository
     * @param ConfigRepository $configRepository
     * @param RequestInterface $request
     */
    public function __construct(
        GetByOrder $getByOrder,
        ReturnRepository $returnRepository,
        LogRepository $logRepository,
        ConfigRepository $configRepository,
        RequestInterface $request
    ) {
        $this->getByOrder = $getByOrder;
        $this->returnRepository = $returnRepository;
        $this->configRepository = $configRepository;
        $this->logRepository = $logRepository;
        $this->request = $request;
    }

    /**
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $creditmemo
     * @return CreditmemoInterface
     */
    public function afterSave(
        CreditmemoRepositoryInterface $subject,
        CreditmemoInterface $creditmemo
    ): CreditmemoInterface {
        $storeId = (int)$creditmemo->getStoreId();
        if (!$this->configRepository->isEnabled() || !$this->configRepository->isReturnsEnabled($storeId)) {
            return $creditmemo;
        }

        $automate = $this->configRepository->autoUpdateReturnsOnCreditmemo($storeId);
        $selectedReturns = $this->request->getParam('channable_return') ?? [];
        if (empty($selectedReturns) && !$automate) {
            return $creditmemo;
        }

        $order = $creditmemo->getOrder();
        if (!$returns = $this->getByOrder->execute($order)) {
            return $creditmemo;
        }

        foreach ($creditmemo->getItems() as $creditmemoItem) {
            if (!$creditmemoItem->getQty()) {
                continue;
            }
            if (!array_key_exists($creditmemoItem->getSku(), $selectedReturns) && !$automate) {
                continue;
            }
            if (!isset($returns[$creditmemoItem->getSku()])) {
                continue;
            }
            try {
                /** @var ReturnData $return */
                $return = $returns[$creditmemoItem->getSku()];
                $return->setMagentoCreditmemoIncrementId($creditmemo->getIncrementId())
                    ->setMagentoCreditmemoId((int)$creditmemo->getEntityId())
                    ->setStatus('accepted');
                $this->returnRepository->save($return);
            } catch (LocalizedException $exception) {
                $this->logRepository->addErrorLog('CreditmemoSaveAfter', $exception->getMessage());
            }
        }

        return $creditmemo;
    }
}
