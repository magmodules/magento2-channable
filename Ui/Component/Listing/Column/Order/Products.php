<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Order;

use Exception;

/**
 * Products Column class for Order Grid
 */
class Products extends AbstractOrder
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
                    $item[$this->getData('name')] = $this->getFormattedProductData($item);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get formatted product data with hoover table
     *
     * @param array $item
     * @return string
     */
    private function getFormattedProductData(array $item): string
    {
        try {
            $productData = $this->json->unserialize($item['products']);
        } catch (Exception $e) {
            return '--';
        }

        $products = [];
        foreach ($productData as $product) {
            $products[] = sprintf(
                '%s <a href="#" class="grid-more-info">(i)<span>%s</span></a>',
                $this->getFormattedProduct($product),
                $this->getFormattedTable($product)
            );
        }

        return implode('<br/>', $products);
    }
}
