<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Model\Config\Source;

use Magento\Customer\Model\Customer\Source\Group as CustomerGroup;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * CustomerGroups Option Source model
 */
class CustomerGroups implements OptionSourceInterface
{

    /**
     * @var CustomerGroup
     */
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
     * @return array
     */
    public function toOptionArray(): array
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
