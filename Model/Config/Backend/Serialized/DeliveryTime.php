<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\Config\Backend\Serialized;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

class DeliveryTime extends ArraySerialized
{

    /**
     * Reformat Shipping Prices and uset unused.
     *
     * @return $this
     */
    public function beforeSave()
    {
        $data = $this->getValue();
        if (is_array($data)) {
            foreach ($data as $key => $row) {
                if (empty($row['code'])) {
                    unset($data[$key]);
                    continue;
                }
            }
        }
        $this->setValue($data);
        return parent::beforeSave();
    }
}
