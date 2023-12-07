<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Order;

use Exception;

/**
 * Shipping Column class for Order Grid
 */
class Shipping extends AbstractOrder
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
                if ($item[$this->getData('name')]) {
                    $item[$this->getData('name')] = $this->getFormattedShippingData($item);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get formatted shipping data with hoover table
     *
     * @param array $item
     * @return string
     */
    private function getFormattedShippingData(array $item): string
    {
        try {
            $shippingData = $this->json->unserialize($item['shipping']);
        } catch (Exception $e) {
            return '--';
        }

        return sprintf(
            '<i class="grid-more-info">%s<div><header>%s</header><span>%s</span></div></i>',
            $this->getCustomerName($shippingData),
            $this->getCustomerName($shippingData),
            $this->getFormattedTable($shippingData)
        );
    }
}
