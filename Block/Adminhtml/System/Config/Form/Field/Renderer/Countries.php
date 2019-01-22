<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use Magento\Directory\Model\Config\Source\Country as CountrySource;

/**
 * Class Countries
 *
 * @package Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer
 */
class Countries extends Select
{

    /**
     * @var array
     */
    private $country = [];
    /**
     * @var CountrySource
     */
    private $countries;

    /**
     * Countries constructor.
     *
     * @param Context       $context
     * @param CountrySource $countries
     * @param array         $data
     */
    public function __construct(
        Context $context,
        CountrySource $countries,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->countries = $countries;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->getCountrySource() as $country) {
                $this->addOption($country['value'], $country['label']);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Get all countries
     *
     * @return array
     */
    private function getCountrySource()
    {
        if (!$this->country) {
            $this->country = $this->countries->toOptionArray();
        }

        return $this->country;
    }

    /**
     * Sets name for input element
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
