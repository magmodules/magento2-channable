<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\Returns;

use Magento\Backend\App\Action;
use Magmodules\Channable\Api\Returns\RepositoryInterface as ReturnsRepository;
use Magmodules\Channable\Service\Returns\ProcessReturn;

/**
 * Returns Process controller
 */
class Process extends Action
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
     * @var ProcessReturn
     */
    private $processReturn;

    /**
     * Process constructor.
     *
     * @param Action\Context $context
     * @param ReturnsRepository $returnsRepository
     * @param ProcessReturn $processReturn
     */
    public function __construct(
        Action\Context $context,
        ReturnsRepository $returnsRepository,
        ProcessReturn $processReturn
    ) {
        parent::__construct($context);
        $this->returnsRepository = $returnsRepository;
        $this->processReturn = $processReturn;
    }

    /**
     * Execute function for Returns Process
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $result = $this->processReturn->execute($data);

        if (!empty($result['status']) && $result['status'] == 'success') {
            if (!empty($result['msg'])) {
                $this->messageManager->addSuccessMessage($result['msg']);
            } else {
                $this->messageManager->addSuccessMessage(__('Return updated'));
            }
        }

        if (!empty($result['status']) && $result['status'] == 'error') {
            if (!empty($result['msg'])) {
                $this->messageManager->addErrorMessage($result['msg']);
            } else {
                $this->messageManager->addErrorMessage(__('Unkown Error'));
            }
        }

        $this->_redirect('channable/returns/index');
    }
}
