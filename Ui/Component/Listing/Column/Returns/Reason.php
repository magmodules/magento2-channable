<?php
/**
 *  Copyright © 2018 Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Ui\Component\Listing\Column\Returns;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Reason
 *
 * @package Magmodules\Channable\Ui\Component\Listing\Column\Transactions
 */
class Reason extends Column
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
                $reason = !empty($item['reason']) ? $item['reason'] : null;
                $comment = !empty($item['comment']) ? $item['comment'] : null;
                if ($comment !== null) {
                    $item['reason'] = $reason . '<br/>' . $comment;
                }
            }
        }
        return $dataSource;
    }
}
