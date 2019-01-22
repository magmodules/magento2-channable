<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magmodules\Channable\Helper\Feed as FeedHelper;

/**
 * Class Feeds
 *
 * @package Magmodules\Channable\Block\Adminhtml\System\Config\Form
 */
class Feeds extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Magmodules_Channable::system/config/fieldset/feeds.phtml';
    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * Feeds constructor.
     *
     * @param Context    $context
     * @param FeedHelper $feedHelper
     */
    public function __construct(
        Context $context,
        FeedHelper $feedHelper
    ) {
        $this->feedHelper = $feedHelper;
        parent::__construct($context);
    }

    /**
     * @return null
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('magmodules');

        return $this->toHtml();
    }

    /**
     * @return array
     */
    public function getFeedData()
    {
        return $this->feedHelper->getConfigData();
    }
}
