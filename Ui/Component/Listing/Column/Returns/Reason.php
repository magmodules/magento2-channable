<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Returns;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Reason Column class for Returns Grid
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
    public function prepareDataSource(array $dataSource): array
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
