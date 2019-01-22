<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Selftest;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Result
 *
 * @package Magmodules\Channable\Block\Adminhtml\System\Config\Form\Selftest
 */
class Result extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Magmodules_Channable::system/config/button/selftest/result.phtml';
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Result constructor.
     *
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->request = $context->getRequest();
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     *
     * @return bool
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return bool
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
