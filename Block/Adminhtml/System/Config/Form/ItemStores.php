<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magmodules\Channable\Helper\Item as ItemHelper;
use Magento\Backend\Block\Template\Context;

/**
 * Class ItemStores
 *
 * @package Magmodules\Channable\Block\Adminhtml\System\Config\Form
 */
class ItemStores extends Field
{

    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * ItemStores constructor.
     *
     * @param Context    $context
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        Context $context,
        ItemHelper $itemHelper
    ) {
        $this->itemHelper = $itemHelper;
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
     * Display ItemStores in config
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {

        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= ' <td class="label"></td>';
        $html .= ' <td class="value">' . $this->renderTabel() . '</td>';
        $html .= ' <td></td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * Table Redner
     */
    public function renderTabel()
    {
        $html = '<table>';
        $html .= ' <tr>';
        $html .= '  <td>' . __('Store') . '</td>';
        $html .= '  <td>' . __('Enabled') . '</td>';
        $html .= '  <td>' . __('Webhook') . '</td>';
        $html .= '  <td>' . __('Items') . '</td>';
        $html .= ' </tr>';

        $stores = $this->itemHelper->getConfigData();
        foreach ($stores as $store) {
            $html .= ' <tr>';
            $html .= '  <td>' . $store['name'] . '</td>';
            if ($store['enable']) {
                $html .= '  <td>' . __('Yes') . '</td>';
            } else {
                $html .= '  <td>' . __('No') . '</td>';
            }
            if ($store['webhook']) {
                $html .= '  <td>' . __('Set') . '</td>';
            } else {
                $html .= '  <td>' . __('Not Set') . '</td>';
            }
            $html .= '  <td>' . $store['qty'] . '</td>';
            $html .= ' </tr>';
        }
        $html .= '</table>';

        return $html;
    }
}
