<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\Magmodules;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Heading
 *
 * @package Magmodules\Channable\Block\Adminhtml\Magmodules
 */
class Heading extends Field
{

    /**
     * Styles heading sperator.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= ' <td class="label"></td>';
        $html .= ' <td class="value">';
        $html .= '  <div class="mm-heading-channable">' . $element->getData('label') . '</div>';
        $html .= '	<div class="mm-comment-channable">';
        $html .= '   <div id="content">' . $element->getData('comment') . '</div>';
        $html .= '  </div>';
        $html .= ' </td>';
        $html .= ' <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
