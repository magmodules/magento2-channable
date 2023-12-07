<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Controller\Adminhtml\VersionCheck;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magmodules\Channable\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Class index
 *
 * AJAX controller to check latest extension version
 */
class Index extends Action
{

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
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
     * @param ConfigRepository $configRepository
     * @param JsonSerializer $json
     * @param File $file
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ConfigRepository $configRepository,
        JsonSerializer $json,
        File $file
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configRepository = $configRepository;
        $this->json = $json;
        $this->file = $file;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = $this->getVersions();
        $current = $latest = preg_replace('/^v/', '', $this->configRepository->getExtensionVersion());
        $changeLog = [];
        if ($result) {
            $data = $this->json->unserialize($result);
            $versions = array_keys($data);
            $latest = preg_replace('/^v/', '', reset($versions));
            foreach ($data as $version => $changes) {
                if (version_compare(preg_replace('/^v/', '', $version), $current) == 0) {
                    break;
                }
                $changeLog[] = [
                    $version => $changes['changelog']
                ];
            }
        }
        $data = [
            'current_version' => 'v' . $current,
            'last_version' => 'v' . $latest,
            'changelog' => $changeLog,
        ];
        return $resultJson->setData(['result' => $data]);
    }

    /**
     * @return string
     */
    private function getVersions(): string
    {
        try {
            return $this->file->fileGetContents(
                sprintf('https://version.magmodules.eu/%s.json', ConfigRepository::EXTENSION_CODE)
            );
        } catch (\Exception $exception) {
            return '';
        }
    }
}
