<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Ui\Component\Listing\Column\Item;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Actions
 *
 * @package Magmodules\Channable\Ui\Component\Listing\Column\Item
 */
class Actions extends Column
{

    const ROUTE = 'channable/item/update';

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
                $name = $this->getData('name');
                $item[$name]['update'] = [
                    'href'    => $this->context->getUrl(self::ROUTE, ['item_id' => $item['item_id']]),
                    'label'   => __('Run Update')
                ];

            }
        }

        return $dataSource;
    }
}
