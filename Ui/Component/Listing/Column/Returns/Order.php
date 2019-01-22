<?php
/**
 *  Copyright © 2019 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Ui\Component\Listing\Column\Returns;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Order
 *
 * @package Magmodules\Channable\Ui\Component\Listing\Column\Transactions
 */
class Order extends Column
{

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $incrementId = !empty($item['magento_increment_id']) ? $item['magento_increment_id'] : null;
                $orderId = !empty($item['magento_order_id']) ? $item['magento_order_id'] : null;
                if ($orderId > 0) {
                    $orderUrl = $this->context->getUrl(
                        'sales/order/view/',
                        ['order_id' => $orderId]
                    );
                    $item['magento_increment_id'] = sprintf(
                        '<a href="%s">%s</a>',
                        $orderUrl,
                        $incrementId
                    );
                }
            }
        }
        return $dataSource;
    }
}
