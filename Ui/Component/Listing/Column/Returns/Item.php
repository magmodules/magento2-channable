<?php
/**
 *  Copyright © Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Returns;

use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Item Column class for Returns Grid
 */
class Item extends Column
{
    private $json;

    /**
     * Constructor
     *
     * @param Json $json
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        Json $json,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->json = $json;
        $this->uiComponentFactory = $uiComponentFactory;
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

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
                try {
                    $itemData = !empty($item['item']) ? $this->unserialize($item['item']) : null;
                } catch (InvalidArgumentException $exception) {
                    $itemData = [];
                }
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

    private function unserialize($item)
    {
        while (is_string($item)) {
            $item = $this->json->unserialize($item);
        }
        return $item;
    }
}
