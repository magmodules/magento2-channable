<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Order;

use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Abstract Column class for Order Grid
 */
class AbstractOrder extends Column
{

    /**
     * @var SerializerJson
     */
    public $json;

    /**
     * Customer constructor.
     * @param SerializerJson $json
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        SerializerJson $json,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->json = $json;
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    /**
     * Returns imploded customer name
     *
     * @param array $data
     * @return string
     */
    public function getCustomerName(array $data): string
    {
        return implode(
            ' ',
            [
                $data['first_name'] ?? '-',
                $data['middle_name'] ?? '',
                $data['last_name'] ?? '-',
            ]
        );
    }

    /**
     * Reformat array data to table
     *
     * @param array $data
     * @return string
     */
    public function getFormattedTable($data): string
    {
        $formatted = '<table>';
        foreach ($data as $key => $value) {
            if (is_array($value) || empty($value)) {
                continue;
            }
            $formatted .= sprintf(
                '<tr><td>%s</td><td>%s</td></tr>',
                str_replace('_', ' ', ucfirst((string)$key)),
                $value
            );
        }

        $formatted .= '</table>';
        return $formatted;
    }

    /**
     * Get formatted price with currency code
     *
     * @param $data
     * @return mixed
     */
    public function getFormattedPrice($data): string
    {
        if (!isset($data['currency']) || !isset($data['total'])) {
            return '--';
        }

        $price = number_format((float)$data['total'], 2, ',', '.');
        return sprintf('%s %s', $data['currency'], $price);
    }

    /**
     * Get formatted quantity and product title
     *
     * @param array $product
     * @return string
     */
    public function getFormattedProduct($product): string
    {
        if (!isset($product['quantity']) || !isset($product['title'])) {
            return '--';
        }

        return sprintf('%sx %s', $product['quantity'], $product['title']);
    }
}
