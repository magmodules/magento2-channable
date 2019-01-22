<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use Magmodules\Channable\Model\System\Config\Source\Conditions as ConditionsSource;

/**
 * Class Conditions
 *
 * @package Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer
 */
class Conditions extends Select
{

    /**
     * @var ConditionsSource
     */
    private $conditions;

    /**
     * Conditions constructor.
     *
     * @param Context          $context
     * @param ConditionsSource $conditions
     * @param array            $data
     */
    public function __construct(
        Context $context,
        ConditionsSource $conditions,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->conditions = $conditions;
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->conditions->toOptionArray() as $condition) {
                $this->addOption($condition['value'], $condition['label']);
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
