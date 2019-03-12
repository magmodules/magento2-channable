<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Feed as FeedHelper;
use Magmodules\Channable\Helper\Order as OrderHelper;
use Magmodules\Channable\Helper\Item as ItemHelper;
use Magmodules\Channable\Helper\Source as SourceHelper;
use Magmodules\Channable\Model\Generate as GenerateModel;
use Magento\Cron\Model\Schedule;

/**
 * Class Selftest
 *
 * @package Magmodules\Channable\Helper
 */
class Selftest extends AbstractHelper
{

    const SUPPORT_URL = 'https://www.magmodules.eu/help/magento2-channable/channable-magento2-selftest-results';

    /**
     * @var General
     */
    private $generalHelper;
    /**
     * @var Feed
     */
    private $feedHelper;
    /**
     * @var Item
     */
    private $itemHelper;
    /**
     * @var Order
     */
    private $orderHelper;
    /**
     * @var GenerateModel
     */
    private $generateModel;
    /**
     * @var Schedule
     */
    private $schedule;

    /**
     * Selftest constructor.
     *
     * @param Context       $context
     * @param General       $generalHelper
     * @param Feed          $feedHelper
     * @param Order         $orderHelper
     * @param Item          $itemHelper
     * @param GenerateModel $generateModel
     * @param Schedule      $schedule
     */
    public function __construct(
        Context $context,
        GeneralHelper $generalHelper,
        FeedHelper $feedHelper,
        OrderHelper $orderHelper,
        ItemHelper $itemHelper,
        GenerateModel $generateModel,
        Schedule $schedule
    ) {
        $this->generalHelper = $generalHelper;
        $this->feedHelper = $feedHelper;
        $this->orderHelper = $orderHelper;
        $this->itemHelper = $itemHelper;
        $this->generateModel = $generateModel;
        $this->schedule = $schedule;
        parent::__construct($context);
    }

    /**
     *
     */
    public function runFeedTests()
    {
        $stores = [];
        $names = [];

        $configData = $this->feedHelper->getConfigData();
        foreach ($configData as $storeId => $feedData) {
            if ($feedData['status']) {
                $stores[] = [
                    'id'           => $storeId,
                    'name'         => $feedData['name'],
                    'qty'          => $this->generateModel->getSize($storeId),
                    'last_fetched' => $feedData['last_fetched'],
                ];
                $names[] = $feedData['name'];
            }
        }

        if (!empty($stores)) {
            $msg = __('Enabled Storeviews(s): %1', implode(', ', $names));
            $results[] = $this->getPass($msg);

            foreach ($stores as $store) {
                if ($store['qty'] == -1) {
                    $msg = __('Storeview "%1": could not calculate number of product, please check the Channable logfile.',
                        $store['name']);
                    $results[] = $this->getFail($msg);
                } elseif ($store['qty'] == 0) {
                    $msg = __('Storeview "%1": no products found, please check the filter settings', $store['name']);
                    $results[] = $this->getFail($msg);
                } else {
                    $msg = __('Storeview "%1": found ~%2 items with current settings/filters. <small><i>Note:</i> This quantity is an indication, actual item quantity can vary due to product ralations.</small>', $store['name'], $store['qty']);
                    $results[] = $this->getPass($msg);
                }
                if (!empty($store['last_fetched'])) {
                    $msg = __('Storeview "%1": last fetched %2', $store['name'], $store['last_fetched']);
                    $results[] = $this->getPass($msg);
                } else {
                    $msg = __('Storeview "%1": not yet fetched, please check the Connection with your Channable account', $store['name']);
                    $results[] = $this->getNotice($msg);
                }

                $priceAttributes = $this->checkPriceAttributes($store['id']);
                if (!empty($priceAttributes)) {
                    $msg = __('Storeview "%1": found price attributes (%2) in extra fields. This is not recommended as prices are created dynamically based on multiple factors like VAT, Price Rules, etc.', $store['name'], implode(',', $priceAttributes));
                    $results[] = $this->getNotice($msg);
                }
            }
        } else {
            $msg = __('No Storeviews(s) Enabled');
            $results[] = $this->getFail($msg, '#feed-enable');
        }

        return $results;
    }

    /**
     * @param        $msg
     * @param string $link
     *
     * @return string
     */
    public function getPass($msg, $link = null)
    {
        return $this->getHtmlResult($msg, 'pass', $link);
    }

    /**
     * @param        $msg
     * @param        $type
     * @param string $link
     *
     * @return string
     */
    public function getHtmlResult($msg, $type, $link)
    {
        $format = null;

        if ($type == 'pass') {
            $format = '<span class="channable-success">%s</span>';
        }
        if ($type == 'fail') {
            $format = '<span class="channable-error">%s</span>';
        }
        if ($type == 'notice') {
            $format = '<span class="channable-notice">%s</span>';
        }

        if ($format) {
            if ($link) {
                $format = str_replace(
                    '</span>',
                    ' <span class="more"><a href="%s">More Info</a></span></span>',
                    $format
                );
                return sprintf($format, $msg, self::SUPPORT_URL . $link);
            } else {
                return sprintf($format, $msg);
            }
        }
    }

    /**
     * @param        $msg
     * @param string $link
     *
     * @return string
     */
    public function getFail($msg, $link = null)
    {
        return $this->getHtmlResult($msg, 'fail', $link);
    }

    /**
     * @param        $msg
     * @param string $link
     *
     * @return string
     */
    public function getNotice($msg, $link = null)
    {
        return $this->getHtmlResult($msg, 'notice', $link);
    }

    /**
     * @param $storeId
     *
     * @return array
     */
    public function checkPriceAttributes($storeId)
    {
        $priceAttributes = ['price', 'special_price'];
        $foundAttributes = [];
        if ($extraFields = $this->generalHelper->getStoreValueArray(SourceHelper::XPATH_EXTRA_FIELDS, $storeId)) {
            foreach ($extraFields as $attribute) {
                if (!empty($attribute['attribute']) && in_array($attribute['attribute'], $priceAttributes)) {
                    $foundAttributes[] = $attribute['attribute'];
                }
            }
        }

        return $foundAttributes;
    }

    /**
     *
     */
    public function runApiTests()
    {

        $enabled = $this->orderHelper->getEnabled();
        if ($enabled) {
            $msg = __('Order Import: Enabled');
            $results[] = $this->getPass($msg);
        } else {
            $msg = __('Order Import: Not Enabled');
            $results[] = $this->getFail($msg, '#order-import');
        }

        $stores = [];
        $names = [];

        $configData = $this->itemHelper->getConfigData();
        foreach ($configData as $storeId => $itemData) {
            if ($itemData['enable']) {
                $stores[] = [
                    'name'    => $itemData['name'],
                    'webhook' => $itemData['webhook']
                ];
                $names[] = $itemData['name'];
            }
        }

        if (!empty($stores)) {
            $msg = __('Item Update: Enabled Storeviews(s): %1', implode(', ', $names));
            $results[] = $this->getPass($msg);
            foreach ($stores as $store) {
                if (empty($store['webhook'])) {
                    $msg = __('Item Update: There is no webhook set for Storeview "%1"', $store['name']);
                    $results[] = $this->getFail($msg, '#item-update');
                }
            }
        } else {
            $msg = __('Item Update: No Storeviews(s) Enabled');
            $results[] = $this->getFail($msg, '#item-update-enable');
        }

        $cron = $this->itemHelper->isCronEnabled();
        if ($cron) {
            $msg = __('Item Update: The Cron is Enabled');
            $results[] = $this->getPass($msg);
        } else {
            $msg = __('Item Update: The Cron is Not Enabled');
            $results[] = $this->getFail($msg, '#item-update-cronjob');
        }

        if ($lastRun = $this->checkMagentoCron()) {
            if ((time() - strtotime($lastRun)) > 3600) {
                $msg = __('Magento cron not seen in last hour (last: %s)', $lastRun);
                $result[] = $this->getFail($msg, '#cronjob');
            } else {
                $msg = __('Magento cron seems to be running (last: %s)', $lastRun);
                $result[] = $this->getPass($msg);
            }
        } else {
            $msg = __('Magento cron not setup');
            $result[] = $this->getFail($msg, '#cronjob');
        }

        return $results;
    }

    /**
     * @return mixed
     */
    public function checkMagentoCron()
    {
        $scheduleCollection = $this->schedule->getCollection()
            ->addFieldToSelect('finished_at')
            ->addFieldToFilter('status', 'success');

        $scheduleCollection->getSelect()
            ->limit(1)
            ->order('finished_at DESC');

        return $scheduleCollection->getFirstItem()->getFinishedAt();
    }
}
