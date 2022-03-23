<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Service\Returns;

/**
 * Class JsonResponse
 */
class JsonResponse
{

    /**
     * @param null $errors
     * @param null $returnsId
     * @return array
     */
    public function execute($errors = null, $returnsId = null): array
    {
        $response = [];
        if (!empty($returnsId)) {
            $response['validated'] = 'true';
            $response['order_id'] = $returnsId;
        } else {
            $response['validated'] = 'false';
            $response['errors'] = $errors;
        }
        return $response;
    }

}
