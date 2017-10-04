<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magmodules\Channable\Helper\Source as SourceHelper;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

class Preview extends AbstractHelper
{

    /**
     * @var Source
     */
    private $sourceHelper;

    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * Preview constructor.
     *
     * @param Context   $context
     * @param Emulation $appEmulation
     * @param Source    $sourceHelper
     */
    public function __construct(
        Context $context,
        Emulation $appEmulation,
        SourceHelper $sourceHelper
    ) {
        $this->appEmulation = $appEmulation;
        $this->sourceHelper = $sourceHelper;
        parent::__construct($context);
    }

    /**
     * @param $feed
     * @param $storeId
     *
     * @return mixed|string
     */
    public function getPreviewData($feed, $storeId)
    {
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $config = $this->sourceHelper->getConfig($storeId, 'preview');
        $this->appEmulation->stopEnvironmentEmulation();

        $previewTable = $this->getPreviewTable($feed, $config);
        $feedData = $this->formatFeedArrayOutput($feed);


        return $previewTable . $feedData;
    }

    /**
     * @param $feed
     * @param $config
     *
     * @return string
     */
    public function getPreviewTable($feed, $config)
    {

        $configTable = $this->getConfigTable($feed);
        $filterTable = $this->getFilterTable($config);
        $attributeTabe = $this->getAttributeTable($config);

        $html = '<h1 style="font-size: 20px;padding: 8px;border-left: 5px solid #f8f8f8;margin: 2px;">' . __('Config Values') . '</h1>';
        $html .= '<table width="100%" cellpadding="5" cellspacing="5">';
        $html .= '  <tr>';
        $html .= '   <td style="font-weight: bold;padding: 5px;border-left: 3px solid #f8f8f8;">' . __('Config') . '</td>';
        $html .= '   <td style="font-weight: bold;padding: 5px;border-left: 3px solid #f8f8f8;">' . __('Attributes') . '</td>';
        $html .= '  </tr>';
        $html .= ' <tr>';
        $html .= '  <td width="50%" valign="top" style="background: #f8f8f8;">' . $configTable . $filterTable .'</td>';
        $html .= '  <td width="50%" valign="top" style="background: #f8f8f8;">' . $attributeTabe . '</td>';
        $html .= ' </tr>';
        $html .= '</table>';

        return $html;
    }

    /**
     * @param $config
     *
     * @return string
     */
    public function getAttributeTable($config)
    {
        $html = '';
        if (empty($config['attributes'])) {
            return $html;
        }

        $html .= '<table width="100%" cellpadding="2" cellspacing="2">';
        $html .= ' <thead>';
        $html .= '  <tr>';
        $html .= '   <td style="padding:5px;" >' . __('Title') . '</td>';
        $html .= '   <td style="padding:5px;" >' . __('Attribute') . '</td>';
        $html .= '   <td style="padding:5px;" >' . __('Fallback') . '</td>';
        $html .= '  </tr>';
        $html .= ' </thead>';
        $html .= ' <tbody>';

        foreach ($config['attributes'] as $attribute) {
            if (empty($attribute['source'])) {
                continue;
            }
            $html .= '<tr>';
            $html .= ' <td style="padding:5px;" >' . $attribute['label'] . '</td>';
            $html .= ' <td style="padding:5px;" >' . $attribute['source'] . '</td>';
            $html .= ' <td style="padding:5px;" >' . (($attribute['parent'] == 1) ? 'Parent' : 'Simple') . '</td>';
            $html .= '</tr>';
        }

        $html .= ' </tbody>';
        $html .= '</table>';
        return $html;
    }

    /**
     * @param $feed
     *
     * @return string
     */
    public function getConfigTable($feed)
    {
        $html = '';
        if (empty($feed['config'])) {
            return $html;
        }

        $html .= '<table width="100%" cellpadding="2" cellspacing="2">';
        $html .= ' <thead>';
        $html .= '  <tr>';
        $html .= '   <td width="50%;padding:5px;">' . __('Config') . '</td>';
        $html .= '   <td width="50%;padding:5px;">' . __('Value') . '</td>';
        $html .= '  </tr>';
        $html .= ' </thead>';
        $html .= ' <tbody>';

        foreach ($feed['config'] as $k => $v) {
            $html .= '<tr>';
            $html .= ' <td style="padding:5px;" >' . $k . '</td>';
            $html .= ' <td style="padding:5px;" >' . $v . '</td>';
            $html .= '</tr>';
        }

        $html .= ' </tbody>';
        $html .= '</table>';
        return $html;
    }

    /**
     * @param $config
     *
     * @return string
     */
    public function getFilterTable($config)
    {
        $html = '';
        if (empty($config['filters']['advanced'])) {
            return $html;
        }

        $html .= '<table width="100%" cellpadding="2" cellspacing="2">';
        $html .= ' <tbody>';

        foreach ($config['filters']['advanced'] as $filter) {
            $html .= '<tr>';
            $html .= ' <td width="50%">' . __('filter') . '</td>';
            $html .= ' <td width="50%">' . $filter['attribute'] . ' ' . $filter['condition'] . ' ' . $filter['value'] . '</td>';
            $html .= '</tr>';
        }

        $html .= ' </tbody>';
        $html .= '</table>';
        return $html;
    }

    /**
     * @param $feed
     *
     * @return string
     */
    public function formatFeedArrayOutput($feed)
    {
        if (empty($feed['products'])) {
            return '<h1 style="font-size: 20px;padding: 8px;border-left: 5px solid #f8f8f8;margin: 2px;">' . __('Feed Output') . '</h1>
            ' . __('No products found in current selection / page');
        }

        return '<h1 style="font-size: 20px;padding: 8px;border-left: 5px solid #f8f8f8;margin: 2px;">' . __('Feed Output') . '</h1>
            <pre>' . print_r($feed['products'], true) . '</pre>';
    }

}