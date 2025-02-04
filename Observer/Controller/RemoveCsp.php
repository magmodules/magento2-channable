<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Observer\Controller;

use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class RemoveCsp
 *
 * @package Magmodules\Channable\Observer\Controller
 */
class RemoveCsp implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var ResponseHttp $response */
        $response = $observer->getEvent()->getData('response');
        /** @var RequestHttp $request */
        $request = $observer->getEvent()->getData('request');
        if (!$response || !$request) {
            return;
        }
        /** @todo When php7 support is dropped replace 'str_starts_with($request->getFullActionName(), 'channable_')' */
        if (strpos($request->getFullActionName(), 'channable_') === 0) {
            $response->clearHeader('content-security-policy-report-only')
                ->clearHeader('Content-Security-Policy-Report-Only')
                ->clearHeader('Content-Security-Policy');
        }
    }
}
