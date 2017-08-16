<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magmodules\Channable\Helper\Order as OrderHelper;
use Magento\Backend\Block\Template\Context;

class OrderStores extends Field
{

    private $orderHelper;

    /**
     * OrderStores constructor.
     *
     * @param Context     $context
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Context $context,
        OrderHelper $orderHelper
    ) {
        $this->orderHelper = $orderHelper;
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
     * Version display in config
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {

        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label"></td>';
        $html .= '  <td class="value">' . $this->renderTabel() . '</td>';
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * Table Render
     */
    public function renderTabel()
    {
        $html = '<table>';
        $html .= ' <tr>';
        $html .= '  <td>' . __('Store') . '</td>';
        $html .= '  <td>' . __('Webhook') . '</td>';
        $html .= ' </tr>';

        $stores = $this->orderHelper->getConfigData();
        foreach ($stores as $store) {
            $html .= '<tr>';
            $html .= '  <td>' . $store['name'] . '</td>';
            $html .= '  <td>' . $store['webhook_url'] . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }
}
