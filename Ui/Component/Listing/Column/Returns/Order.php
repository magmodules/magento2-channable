<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Returns;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Order Column class for Returns Grid
 */
class Order extends Column
{

    const ORDER_URL = '<a href="%s">#%s</a>';

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
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
                        self::ORDER_URL,
                        $orderUrl,
                        $incrementId
                    );
                }
            }
        }
        return $dataSource;
    }
}
