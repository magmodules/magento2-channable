<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Customer\Model\Customer\Source\Group as CustomerGroup;
use Magento\Framework\Option\ArrayInterface;

class CustomerGroups implements ArrayInterface
{

    private $customerGroup;

    /**
     * CustomerGroups constructor.
     *
     * @param CustomerGroup $customerGroup
     */
    public function __construct(
        CustomerGroup $customerGroup
    ) {
        $this->customerGroup = $customerGroup;
    }

    /**
     * @return mixed
     */
    public function toOptionArray()
    {
        $groups = $this->customerGroup->toOptionArray();
        foreach ($groups as $key => $group) {
            if ($group['value'] == '32000') {
                unset($groups[$key]);
            }
        }
        return $groups;
    }
}
