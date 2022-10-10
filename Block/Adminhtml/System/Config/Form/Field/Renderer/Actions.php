<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Store\Model\StoreManagerInterface;

class Actions extends Select
{
    /**
     * @var array
     */
    public $options = null;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Countries constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->getAllActions() as $action) {
                $this->addOption($action['value'], $action['label']);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Get all countries
     *
     * @return array
     */
    private function getAllActions(): ?array
    {
        if (!$this->options) {
            $this->options[] = ['value' => '', 'label' => ''];
            $this->options[] = $this->getFormatOptions();
            $this->options[] = $this->getPriceOptions();
        }

        return $this->options;
    }

    /**
     * @return array
     */
    private function getFormatOptions(): array
    {
        return [
            'label' => __('Formatting'),
            'value' => [
                [
                    'label' => __('Striptags'),
                    'value' => 'striptags'
                ],
                [
                    'label' => __('Round'),
                    'value' => 'round'
                ],
                [
                    'label' => __('Number'),
                    'value' => 'number'
                ],
            ],
            'optgroup-name' => __('Formatting'),
        ];
    }


    /**
     * @return array
     */
    private function getPriceOptions(): array
    {
        $priceOptions = [];

        try {
            $storeId = (int)$this->getRequest()->getParam('store');
            $availableCurrencies = $this->storeManager->getStore($storeId)->getAvailableCurrencyCodes();
        } catch (NoSuchEntityException $exception) {
            return $priceOptions;
        }

        foreach ($availableCurrencies as $currencyCode) {
            $priceOptions[] = [
                'label' => $currencyCode,
                'value' => 'currency_' . $currencyCode
            ];
        }

        return [
            'label' => __('Render in currency'),
            'value' => $priceOptions,
            'optgroup-name' => __('Render in currency'),
        ];
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
