<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\Design;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Header extends Field
{

    const MODULE_CODE = 'magento2-channable';
    const MODULE_SUPPORT_LINK = 'https://www.magmodules.eu/help/' . self::MODULE_CODE;
    const MODULE_CHANNABLE_SUPPORT_LINK = 'https://support.channable.com/hc/en-us';

    /**
     * @var string
     */
    protected $_template = 'Magmodules_Channable::system/config/fieldset/header.phtml';

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->addClass('magmodules');
        return $this->toHtml();
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink(): string
    {
        return self::MODULE_SUPPORT_LINK;
    }


    /**
     * Support link for Channable.
     *
     * @return string
     */
    public function getChannableSupportLink(): string
    {
        return self::MODULE_CHANNABLE_SUPPORT_LINK;
    }
}
