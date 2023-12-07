<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Order;

use Exception;

/**
 * Customer Column class for Order Grid
 */
class Customer extends AbstractOrder
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
                    $item[$this->getData('name')] = $this->getFormattedCustomerData($item);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get formatted customer data with hoover table
     *
     * @param array $item
     * @return string
     */
    private function getFormattedCustomerData(array $item): string
    {
        try {
            $customerData = $this->json->unserialize($item['customer']);
        } catch (Exception $e) {
            return '--';
        }

        return sprintf(
            '<i class="grid-more-info">%s<div><header>%s</header><span>%s</span></div></i>',
            $this->getCustomerName($customerData),
            $this->getCustomerName($customerData),
            $this->getFormattedTable($customerData)
        );
    }
}
