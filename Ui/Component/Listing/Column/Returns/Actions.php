<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Ui\Component\Listing\Column\Returns;

use Magento\Framework\Phrase;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Prepare actions column for Returns Class
 */
class Actions extends Column
{

    private const URL = 'channable/returns/process';
    private const DELETE_URL = 'channable/returns/delete';

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
                $status = !empty($item['status']) ? strtolower($item['status']) : null;
                $name = $this->getData('name');

                if ($status == 'new') {
                    $item[$name]['accept'] = [
                        'href' => $this->context->getUrl(self::URL, ['id' => $item['entity_id'], 'type' => 'accepted']),
                        'label' => __('Accept'),
                        'confirm' => [
                            'title' => __("Process return #{$item['entity_id']}?"),
                            'message' => $this->getConfirm('Accepted')
                        ],
                    ];
                    $item[$name]['reject'] = [
                        'href' => $this->context->getUrl(self::URL, ['id' => $item['entity_id'], 'type' => 'rejected']),
                        'label' => __('Reject'),
                        'confirm' => [
                            'title' => __("Process return #{$item['entity_id']}?"),
                            'message' => $this->getConfirm('Rejected')
                        ],
                    ];
                    $item[$name]['repair'] = [
                        'href' => $this->context->getUrl(self::URL, ['id' => $item['entity_id'], 'type' => 'repaired']),
                        'label' => __('Repair'),
                        'confirm' => [
                            'title' => __("Process return #{$item['entity_id']}?"),
                            'message' => $this->getConfirm('Repaired')
                        ],
                    ];
                    $item[$name]['exchange'] = [
                        'href' => $this->context->getUrl(self::URL, ['id' => $item['entity_id'], 'type' => 'exchanged']),
                        'label' => __('Exchange'),
                        'confirm' => [
                            'title' => __("Process return #{$item['entity_id']}?"),
                            'message' => $this->getConfirm('Exchanged')
                        ],
                    ];
                    $item[$name]['keep'] = [
                        'href' => $this->context->getUrl(self::URL, ['id' => $item['entity_id'], 'type' => 'keeps']),
                        'label' => __('Keep'),
                        'confirm' => [
                            'title' => __("Process return #{$item['entity_id']}?"),
                            'message' => $this->getConfirm('Keeps')
                        ],
                    ];
                    $item[$name]['cancel'] = [
                        'href' => $this->context->getUrl(self::URL, ['id' => $item['entity_id'], 'type' => 'cancelled']),
                        'label' => __('Cancel'),
                        'confirm' => [
                            'title' => __("Process return #{$item['entity_id']}?"),
                            'message' => $this->getConfirm('Cancelled')
                        ],
                    ];
                }
                $item[$name]['delete'] = [
                    'href' => $this->context->getUrl(self::DELETE_URL, ['id' => $item['entity_id']]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __("Delete return #{$item['entity_id']}?"),
                        'message' => __(
                            'Are you sure you want to delete this return?
                                This action can not be undone and will not update the status on Channable!'
                        )
                    ],
                ];
            }
        }

        return $dataSource;
    }

    /**
     * @param $type
     * @return Phrase
     */
    private function getConfirm($type)
    {
        $msg = sprintf(
            'Are you sure you want to update this as "%s" and close this return? This action can not be undone!',
            $type
        );
        return __($msg);
    }
}
