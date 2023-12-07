<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\Returns;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;
use Magmodules\Channable\Model\Returns\Collection as ReturnsCollection;
use Magmodules\Channable\Model\Returns\CollectionFactory;
use Magmodules\Channable\Service\Returns\ImportReturn;
use Magmodules\Channable\Api\Returns\Data\DataInterface;

class MassProcess extends Action
{

    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Magmodules_Channable::returns_process';

    /**
     * @var ReturnsRepository
     */
    private $returnsRepository;
    /**
     * @var RedirectInterface
     */
    private $redirect;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var ImportReturn
     */
    private $importReturn;
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @param Action\Context $context
     * @param Filter $filter
     * @param ReturnsRepository $returnsRepository
     * @param CollectionFactory $collectionFactory
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Action\Context $context,
        Filter $filter,
        ReturnsRepository $returnsRepository,
        CollectionFactory $collectionFactory,
        ImportReturn $importReturn,
        RedirectInterface $redirect
    ) {
        parent::__construct($context);
        $this->redirect = $redirect;
        $this->filter = $filter;
        $this->importReturn = $importReturn;
        $this->returnsRepository = $returnsRepository;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        try {
            foreach ($this->getCollection() as $return) {
                $this->process($return);
            }
            $this->messageManager->addSuccessMessage(__('Returns re-processed!'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath(
            $this->redirect->getRefererUrl()
        );
    }

    /**
     * Get selected collection
     *
     * @return ReturnsCollection $collection
     * @throws LocalizedException
     */
    private function getCollection(): ReturnsCollection
    {
        if ($selected = $this->getRequest()->getParam('selected')) {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter(
                    'entity_id',
                    ['in' => $selected]
                );
        } else {
            /** @var ReturnsCollection $collection */
            $collection = $this->filter->getCollection(
                $this->collectionFactory->create()
            );
        }

        return $collection;
    }

    /**
     * @param DataInterface $return
     * @return void
     * @throws LocalizedException
     */
    private function process(DataInterface $return): void
    {
        $incrementId = $return->getMagentoIncrementId();
        if ($incrementId && !$return->getMagentoOrderId()) {
            $orderId = $this->importReturn->getMagentoOrder($incrementId);
            $return->setMagentoOrderId($orderId);
            $this->returnsRepository->save($return);
        }
    }
}
