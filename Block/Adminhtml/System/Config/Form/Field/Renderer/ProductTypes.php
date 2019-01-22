<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use Magmodules\Channable\Model\System\Config\Source\ProductTypes as ProductTypesSource;

/**
 * Class ProductTypes
 *
 * @package Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer
 */
class ProductTypes extends Select
{

    /**
     * @var ProductTypesSource
     */
    private $source;

    /**
     * ProductTypes constructor.
     *
     * @param Context            $context
     * @param ProductTypesSource $source
     * @param array              $data
     */
    public function __construct(
        Context $context,
        ProductTypesSource $source,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->source = $source;
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->source->toOptionArray() as $type) {
                $this->addOption($type['value'], $type['label']);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Sets name for input element.
     *
     * @param $value
     *
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
