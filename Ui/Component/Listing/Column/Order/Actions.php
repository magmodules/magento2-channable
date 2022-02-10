<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Order;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magmodules\Channable\Model\System\Config\Source\Status;

/**
 * Class Actions
 * grid actions
 */
class Actions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['delete'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'channable/order/delete',
                        ['id' => $item['entity_id']]
                    ),
                    'label' => __('Delete'),
                    'hidden' => false
                ];
                if ($item['magento_increment_id']) {
                    $item[$this->getData('name')]['delete']['confirm'] = [
                        'title' => __('Delete'),
                        'message' => __('Are you sure you want to delete?'),
                        '__disableTmpl' => true,
                    ];
                    $item[$this->getData('name')]['view_order'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'sales/order/view',
                            ['order_id' => $item['magento_order_id']]
                        ),
                        'label' => __('View Order'),
                        'hidden' => false,
                    ];
                }

                $status = [Status::NEW, Status::ERROR, Status::FAILED];
                if (in_array($item['status'], $status)) {
                    $item[$this->getData('name')]['import'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'channable/order/import',
                            [
                                'id' => $item['entity_id']
                            ]
                        ),
                        'label' => __('Retry import'),
                        'hidden' => false
                    ];
                }
            }
        }

        return $dataSource;
    }
}
