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

    private $source;
    private $request;
    private $appEmulation;

    /**
     * ParentAttributes constructor.
     *
     * @param Http         $request
     * @param Emulation    $appEmulation
     * @param SourceHelper $source
     */
    public function __construct(
        Http $request,
        Emulation $appEmulation,
        SourceHelper $source
    ) {
        $this->source = $source;
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
        $source = $this->source->getAttributes('parent');
        $this->appEmulation->stopEnvironmentEmulation();

        foreach ($source as $key => $attribute) {
            if (!empty($attribute['parent_selection_disabled'])) {
                continue;
            }
            $label = str_replace('_', ' ', $attribute['label']);
            $attributes[] = [
                'value' => $key,
                'label' => ucwords($label),
            ];
        }
        return $attributes;
    }
}
