<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magmodules\Channable\Helper\Source as SourceHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

class ParentAttributes implements ArrayInterface
{

    private $sourceHelper;
    private $request;
    private $appEmulation;

    /**
     * ParentAttributes constructor.
     *
     * @param Http         $request
     * @param Emulation    $appEmulation
     * @param SourceHelper $sourceHelper
     */
    public function __construct(
        Http $request,
        Emulation $appEmulation,
        SourceHelper $sourceHelper
    ) {
        $this->sourceHelper = $sourceHelper;
        $this->appEmulation = $appEmulation;
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = [];
        $storeId = $this->request->getParam('store');
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $source = $this->sourceHelper->getAttributes('parent');
        $this->appEmulation->stopEnvironmentEmulation();

        foreach ($source as $key => $attribute) {
            if (empty($attribute['parent_selection_disabled']) && empty($attribute['parent'])) {
                $label = str_replace('_', ' ', $key);
                $attributes[] = [
                    'value' => $key,
                    'label' => ucwords($label),
                ];
            }
        }

        return $attributes;
    }

}
