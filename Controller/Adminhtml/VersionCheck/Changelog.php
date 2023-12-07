<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\VersionCheck;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Class Changelog
 *
 * AJAX controller to check latest extension version changelog
 */
class Changelog extends Action
{

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var JsonSerializer
     */
    private $json;
    /**
     * @var File
     */
    private $file;

    /**
     * Check constructor.
     *
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param JsonSerializer $json
     * @param File $file
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        JsonSerializer $json,
        File $file
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->file = $file;
        parent::__construct($context);
    }

    /**
     * @return Json
     * @throws FileSystemException
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = $this->getVersions();
        $data = $this->json->unserialize($result);
        return $resultJson->setData($data);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    private function getVersions(): string
    {
        return $this->file->fileGetContents(
            sprintf('https://version.magmodules.eu/%s.json', ConfigRepository::EXTENSION_CODE)
        );
    }
}
