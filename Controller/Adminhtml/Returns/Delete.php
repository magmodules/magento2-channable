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
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;

class Delete extends Action
{

    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Magmodules_Channable::returns_delete';

    /**
     * @var ReturnsRepository
     */
    private $returnsRepository;
    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @param Action\Context $context
     * @param ReturnsRepository $returnsRepository
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Action\Context $context,
        ReturnsRepository $returnsRepository,
        RedirectInterface $redirect
    ) {
        parent::__construct($context);
        $this->redirect = $redirect;
        $this->returnsRepository = $returnsRepository;
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        try {
            $this->returnsRepository->deleteById(
                (int)$this->getRequest()->getParam('id')
            );
            $this->messageManager->addSuccessMessage(__('Returns was deleted'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath(
            $this->redirect->getRefererUrl()
        );
    }
}
