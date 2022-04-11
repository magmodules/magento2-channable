<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Plugin;

use Magento\Framework\App\RequestInterface;

/**
 * Bypass Amasty Shop By SEO url parser.
 */
class AroundIsAllowedRequest
{

    /**
     * @param                  $subject
     * @param \Closure         $proceed
     * @param RequestInterface $request
     * @param bool             $allowEmptyModuleName
     **
     * @return bool
     */
    public function aroundIsAllowedRequest(
        $subject,
        \Closure $proceed,
        RequestInterface $request,
        $allowEmptyModuleName = false
    ) {
        $identifier = ltrim($request->getOriginalPathInfo(), '/');
        if (strpos($identifier, 'channable/') !== FALSE) {
            return false;
        }

        return $proceed($request, $allowEmptyModuleName);
    }
}
