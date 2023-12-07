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
class Creditmemo extends Column
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
                if ($item['magento_creditmemo_id']) {
                    $orderUrl = $this->context->getUrl(
                        'sales/creditmemo/view/',
                        ['creditmemo_id' => $item['magento_creditmemo_id']]
                    );
                    $item['magento_creditmemo_increment_id'] = sprintf(
                        self::ORDER_URL,
                        $orderUrl,
                        $item['magento_creditmemo_increment_id']
                    );
                }
            }
        }
        return $dataSource;
    }
}
