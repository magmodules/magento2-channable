<?php
/**
 *  Copyright © 2018 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Ui\Component\Listing\Column\Returns;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Item
 *
 * @package Magmodules\Channable\Ui\Component\Listing\Column\Transactions
 */
class Item extends Column
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
                $itemData = !empty($item['item']) ? json_decode($item['item'], true) : null;
                if (!empty($itemData)) {
                    $item['item'] = __(
                        '%1x %2 %3',
                        $itemData['quantity'],
                        $itemData['title'],
                        '(GTIN: ' . $itemData['gtin'] . ')'
                    );
                }
            }
        }
        return $dataSource;
    }
}
