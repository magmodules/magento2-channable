<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\Config\Backend\Serialized;

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;

class Filters extends ArraySerialized
{

    /**
     * Uset unused fields.
     *
     * @return $this
     */
    public function beforeSave()
    {
        $data = $this->getValue();
        if (is_array($data)) {
            foreach ($data as $key => $row) {
                if (empty($row['attribute']) || empty($row['condition'])) {
                    unset($data[$key]);
                    continue;
                }
                $data[$key]['value'] = trim($row['value']);
                if (($row['condition'] != 'empty') && ($row['condition'] != 'not-empty')) {
                    if (empty($row['value'])) {
                        unset($data[$key]);
                        continue;
                    }
                }
            }
        }
        $this->setValue($data);
        return parent::beforeSave();
    }
}
