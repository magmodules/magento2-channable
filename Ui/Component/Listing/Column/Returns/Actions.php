<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Ui\Component\Listing\Column\Returns;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Actions
 *
 * @package Magmodules\DigitecGalaxus\Ui\Component\Listing\Column\Order
 */
class Actions extends Column
{

    const ROUTE = 'channable/returns/process';

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
                $status = !empty($item['status']) ? strtolower($item['status']) : null;
                $name = $this->getData('name');

                if ($status == 'new') {
                    $item[$name]['accept'] = [
                        'href'    => $this->context->getUrl(self::ROUTE, ['id' => $item['id'], 'type' => 'accepted']),
                        'label'   => __('Accept'),
                        'confirm' => [
                            'title'   => __('Process #${ $.$data.order_id }?'),
                            'message' => __('Are you sure you want to update this as "Accepted" and close this return? This action can not be undone!')
                        ],
                    ];
                    $item[$name]['reject'] = [
                        'href'    => $this->context->getUrl(self::ROUTE, ['id' => $item['id'], 'type' => 'rejected']),
                        'label'   => __('Reject'),
                        'confirm' => [
                            'title'   => __('Process #${ $.$data.order_id }?'),
                            'message' => __('Are you sure you want to update this as "Rejected" and close this return? This action can not be undone!')
                        ],
                    ];
                    $item[$name]['repair'] = [
                        'href'    => $this->context->getUrl(self::ROUTE, ['id' => $item['id'], 'type' => 'repaired']),
                        'label'   => __('Repair'),
                        'confirm' => [
                            'title'   => __('Process #${ $.$data.order_id }?'),
                            'message' => __('Are you sure you want to update this as "Repaired" and close this return? This action can not be undone!')
                        ],
                    ];
                    $item[$name]['exchange'] = [
                        'href'    => $this->context->getUrl(
                            self::ROUTE,
                            ['id' => $item['id'], 'type' => 'exchanged']
                        ),
                        'label'   => __('Exchange'),
                        'confirm' => [
                            'title'   => __('Process #${ $.$data.order_id }?'),
                            'message' => __('Are you sure you want to update this as "Exchanged" and close this return? This action can not be undone!')
                        ],
                    ];
                    $item[$name]['keep'] = [
                        'href'    => $this->context->getUrl(self::ROUTE, ['id' => $item['id'], 'type' => 'keeps']),
                        'label'   => __('Keep'),
                        'confirm' => [
                            'title'   => __('Process #${ $.$data.order_id }?'),
                            'message' => __('Are you sure you want to update this as "Keeps" and close this return? This action can not be undone!')
                        ],
                    ];
                    $item[$name]['cancel'] = [
                        'href'    => $this->context->getUrl(
                            self::ROUTE,
                            ['id' => $item['id'], 'type' => 'cancelled']
                        ),
                        'label'   => __('Cancel'),
                        'confirm' => [
                            'title'   => __('Process #${ $.$data.order_id }?'),
                            'message' => __('Are you sure you want to update this as "Cancelled" and close this return? This action can not be undone!')
                        ],
                    ];
                } else {
                    $item[$name]['delete'] = [
                        'href'    => $this->context->getUrl(self::ROUTE, ['id' => $item['id'], 'type' => 'delete']),
                        'label'   => __('Delete'),
                        'confirm' => [
                            'title'   => __('Delete #${ $.$data.order_id }?'),
                            'message' => __('Are you sure you want to delete this return? This action can not be undone and will not update the status on Channable!')
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
