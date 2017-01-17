<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magmodules\Channable\Helper\Feed as FeedHelper;

class Feeds extends Field
{

    protected $feed;
    protected $_template = 'Magmodules_Channable::system/config/fieldset/feeds.phtml';

    /**
     * Feeds constructor.
     * @param Context $context
     * @param FeedHelper $feed
     */
    public function __construct(
        Context $context,
        FeedHelper $feed
    ) {
        $this->feed = $feed;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
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
        return $this->feed->getConfigData();
    }
}
