<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magmodules\Channable\Helper\General as GeneralHelper;

class Feed extends AbstractHelper
{

    const XML_PATH_ENABLE = 'magmodules_channable/general/enable';
    const CHANNABLE_CONNECT_URL = 'https://app.channable.com/connect/magento.html?store_id=%s&url=%s&token=%s&version=v2';

    private $generalHelper;
    private $storeManager;
    private $directory;
    private $datetime;

    /**
     * Feed constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param Filesystem            $filesystem
     * @param DateTime              $datetime
     * @param General               $generalHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        DateTime $datetime,
        GeneralHelper $generalHelper
    ) {
        $this->generalHelper = $generalHelper;
        $this->storeManager = $storeManager;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->datetime = $datetime;
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getConfigData()
    {
        $feedData = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeId = $store->getStoreId();
            $feedData[$storeId] = [
                'store_id'    => $storeId,
                'code'        => $store->getCode(),
                'name'        => $store->getName(),
                'is_active'   => $store->getIsActive(),
                'status'      => $this->generalHelper->getStoreValue(self::XML_PATH_ENABLE, $storeId),
                'preview_url' => $this->getPreviewUrl($storeId),
                'json_url'    => $this->geJsonUrl($storeId),
                'connect_url' => $this->getConnectUrl($storeId),

            ];
        }
        return $feedData;
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getPreviewUrl($storeId)
    {
        $url = $this->storeManager->getStore($storeId)->getBaseUrl();
        $token = $this->generalHelper->getToken();
        return $url . sprintf('channable/feed/preview/id/%s/token/%s', $storeId, $token);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function geJsonUrl($storeId)
    {
        $url = $this->storeManager->getStore($storeId)->getBaseUrl();
        $token = $this->generalHelper->getToken();
        return $url . sprintf('channable/feed/json/id/%s/token/%s', $storeId, $token);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getConnectUrl($storeId)
    {
        $url = $this->storeManager->getStore($storeId)->getBaseUrl();
        $token = $this->generalHelper->getToken();
        return sprintf(self::CHANNABLE_CONNECT_URL, $storeId, $url, $token);
    }

    /**
     * @param     $timeStart
     * @param int $count
     * @param int $limit
     * @param int $productQty
     * @param int $page
     * @param int $pages
     *
     * @return array
     */
    public function getFeedSummary($timeStart, $count, $limit, $productQty, $page = 1, $pages = 1)
    {
        $summary = [];
        $summary['system'] = 'Magento 2';
        $summary['extension'] = 'Magmodules_Channable';
        $summary['version'] = $this->generalHelper->getExtensionVersion();
        $summary['magento_version'] = $this->generalHelper->getMagentoVersion();
        $summary['products_total'] = $count;
        $summary['products_limit'] = $limit;
        $summary['products_output'] = $productQty;
        $summary['products_pages'] = $pages;
        $summary['current_page'] = ($page) ? $page : 1;
        if ($pages > $summary['current_page']) {
            $summary['next_page'] = 'true';
        } else {
            $summary['next_page'] = 'false';
        }
        $summary['time'] = number_format((microtime(true) - $timeStart), 2) . ' sec';
        $summary['date'] = $this->datetime->gmtDate();
        return $summary;
    }
}
