<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\Returns;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magmodules\Channable\Service\Returns\ImportSimulator;

/**
 * Adminhtml Simulate controller to simulate Test Returns
 */
class Simulate extends Action
{

    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Magmodules_Channable::returns_simulate';

    /**
     * @var ImportSimulator
     */
    private $importSimulator;
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * Simulate constructor.
     *
     * @param Action\Context $context
     * @param ImportSimulator $importSimulator
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Action\Context $context,
        ImportSimulator $importSimulator,
        RedirectInterface $redirect
    ) {
        $this->importSimulator = $importSimulator;
        $this->redirect = $redirect;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $returns = $this->importSimulator->execute(
                (int)$this->getRequest()->getParam('store_id'),
                $this->getExtraParams()
            );
            if ($returns['validated']) {
                $this->messageManager->addSuccessMessage(__('Test return #%1 created', $returns['return_id']));
                $resultRedirect->setPath('channable/returns/index');
            } else {
                $this->messageManager->addErrorMessage(__($returns['errors']));
                $resultRedirect->setUrl($this->redirect->getRefererUrl());
            }
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $resultRedirect->setUrl($this->redirect->getRefererUrl());
        }

        return $resultRedirect;
    }

    /**
     * @return array
     */
    private function getExtraParams(): array
    {
        $extraParams = [];
        foreach (ImportSimulator::PARAMS as $param) {
            $extraParams[$param] = $this->getRequest()->getParam($param, null);
        }

        return $extraParams;
    }
}
