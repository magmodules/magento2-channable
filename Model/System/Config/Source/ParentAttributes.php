<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magmodules\Channable\Helper\Source as SourceHelper;
use Magento\Framework\App\Request\Http;

class ParentAttributes implements ArrayInterface
{

    protected $source;
    protected $request;

    /**
     * ParentAttributes constructor.
     * @param Http $request
     * @param SourceHelper $source
     */
    public function __construct(
        Http $request,
        SourceHelper $source
    ) {
        $this->source = $source;
        $this->request = $request;
    }
        
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = [];
        $storeId = $this->request->getParam('store');
        $source = $this->source->getAttributes($storeId, 'parent');
        foreach ($source as $key => $attribute) {
            if (!empty($attribute['parent_selection_disabled'])) {
                continue;
            }
            if (!empty($attribute['parent'])) {
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
