<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Order;

/**
 * Error Column class for Order Grid
 */
class Error extends AbstractOrder
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
                    $item[$this->getData('name')] = $this->getFormattedErrorData($item);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Get formatted error data with hoover table
     *
     * @param array $item
     * @return string
     */
    private function getFormattedErrorData(array $item): string
    {
        $message = explode(',', (string)$item['error_msg']);
        if (count($message) == 1) {
            if (strlen($item['error_msg']) < 30) {
                return $item['error_msg'];
            } else {
                return sprintf(
                    '<i class="grid-more-info">%s<div><header>%s</header><span>%s</span></div></i>',
                    substr($item['error_msg'], 0, 30),
                    substr($item['error_msg'], 0, 30),
                    substr($item['error_msg'], 30, strlen($item['error_msg']))
                );
            }
        }
        $shortMessage = array_shift($message);
        return sprintf(
            '<i class="grid-more-info">%s<div><header>%s</header><span>%s</span></div></i>',
            $shortMessage,
            $shortMessage,
            implode(',', $message)
        );
    }
}
