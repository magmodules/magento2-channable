<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Order;

use Exception;

/**
 * Billing Column class for Order Grid
 */
class Billing extends AbstractOrder
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
                    $item[$this->getData('name')] = $this->getFormattedBillingData($item);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get formatted billing data with hoover table
     *
     * @param array $item
     * @return string
     */
    private function getFormattedBillingData(array $item): string
    {
        try {
            $billingData = $this->json->unserialize($item['billing']);
        } catch (Exception $e) {
            return '--';
        }

        return sprintf(
            '%s <a href="#" class="grid-more-info">(i)<span>%s</span></a>',
            $this->getCustomerName($billingData),
            $this->getFormattedTable($billingData)
        );
    }
}
